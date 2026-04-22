<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\Mark;
use App\Models\MyClass;
use App\Models\StudentRecord;
use App\Models\Subject;

class PerformanceAnalysisService
{
    protected string $year;
    protected ?int   $currentExamId;
    protected ?int   $previousExamId;

    public function __construct()
    {
        $this->year           = Qs::getCurrentSession();
        $this->currentExamId  = $this->resolveCurrentExamId();
        $this->previousExamId = $this->resolvePreviousExamId();
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Students whose current average is below $minScore OR dropped by more
     * than $threshold % compared to the previous exam.
     */
    public function getAtRiskStudents(int $threshold = 15, int $minScore = 50): array
    {
        if (!$this->currentExamId) return [];

        // Load current exam records with student + class info
        $current = ExamRecord::where('exam_id', $this->currentExamId)
            ->where('year', $this->year)
            ->with(['student', 'my_class'])
            ->get()
            ->keyBy('student_id');

        // Load previous exam records for comparison
        $previous = $this->previousExamId
            ? ExamRecord::where('exam_id', $this->previousExamId)
                ->whereIn('student_id', $current->keys())
                ->get()
                ->keyBy('student_id')
            : collect();

        $atRisk = [];

        foreach ($current as $studentId => $exr) {
            $currentAvg  = (float) ($exr->ave ?? 0);
            $previousAvg = (float) ($previous[$studentId]->ave ?? 0);

            $drop = $previousAvg > 0
                ? round((($previousAvg - $currentAvg) / $previousAvg) * 100, 1)
                : 0;

            if ($currentAvg < $minScore || $drop > $threshold) {
                $level    = $this->riskLevel($currentAvg, $drop);
                $atRisk[] = [
                    'student_id'   => $studentId,
                    'student_name' => $exr->student->name ?? 'Unknown',
                    'class'        => $exr->my_class->name ?? '—',
                    'current_avg'  => round($currentAvg, 1),
                    'previous_avg' => round($previousAvg, 1),
                    'drop_percent' => $drop,
                    'risk_level'   => $level,
                    'risk_color'   => $this->riskColor($level),
                ];
            }
        }

        usort($atRisk, fn($a, $b) =>
            ['critical' => 3, 'warning' => 2, 'low' => 1][$b['risk_level']]
            <=>
            ['critical' => 3, 'warning' => 2, 'low' => 1][$a['risk_level']]
        );

        return $atRisk;
    }

    /**
     * Per-class performance summary for the current exam.
     */
    public function getClassOverview(): array
    {
        if (!$this->currentExamId) return [];

        $overview = [];

        foreach (MyClass::orderBy('name')->get() as $class) {
            $records = ExamRecord::where('exam_id', $this->currentExamId)
                ->where('my_class_id', $class->id)
                ->where('year', $this->year)
                ->get();

            if ($records->isEmpty()) continue;

            $avgScore      = round($records->avg('ave') ?? 0, 1);
            $belowFifty    = $records->filter(fn($r) => ($r->ave ?? 0) < 50)->count();
            $studentCount  = $records->count();

            // Best and worst subjects by average tex score for this class/exam
            $subjectAvgs = Mark::where('exam_id', $this->currentExamId)
                ->where('my_class_id', $class->id)
                ->where('year', $this->year)
                ->get()
                ->groupBy('subject_id')
                ->map(fn($rows) => round($rows->avg('tex' . $this->currentExamTerm()) ?? 0, 1));

            $bestId  = $subjectAvgs->sortDesc()->keys()->first();
            $worstId = $subjectAvgs->sort()->keys()->first();

            $overview[] = [
                'class_id'          => $class->id,
                'class_name'        => $class->name,
                'student_count'     => $studentCount,
                'average'           => $avgScore,
                'students_below_50' => $belowFifty,
                'best_subject'      => $bestId  ? (Subject::find($bestId)->name  ?? '—') : '—',
                'best_subject_avg'  => $bestId  ? ($subjectAvgs[$bestId]  ?? 0)  : 0,
                'worst_subject'     => $worstId ? (Subject::find($worstId)->name ?? '—') : '—',
                'worst_subject_avg' => $worstId ? ($subjectAvgs[$worstId] ?? 0)  : 0,
                'health'            => $avgScore >= 60 ? 'good' : ($avgScore >= 45 ? 'warning' : 'critical'),
            ];
        }

        return $overview;
    }

    /**
     * Subjects whose current class average is below $threshold or declining.
     */
    public function getSubjectAlerts(int $threshold = 50): array
    {
        if (!$this->currentExamId) return [];

        $tex     = 'tex' . $this->currentExamTerm();
        $alerts  = [];

        foreach (Subject::orderBy('name')->get() as $subject) {
            $currentAvg = round(
                Mark::where('subject_id', $subject->id)
                    ->where('exam_id', $this->currentExamId)
                    ->where('year', $this->year)
                    ->avg($tex) ?? 0,
                1
            );

            $previousAvg = 0;
            if ($this->previousExamId) {
                $prevTex     = 'tex' . $this->previousExamTerm();
                $previousAvg = round(
                    Mark::where('subject_id', $subject->id)
                        ->where('exam_id', $this->previousExamId)
                        ->avg($prevTex) ?? 0,
                    1
                );
            }

            $trend = $this->trend($currentAvg, $previousAvg);

            if ($currentAvg > 0 && ($currentAvg < $threshold || $trend === 'declining')) {
                $alerts[] = [
                    'subject_id'   => $subject->id,
                    'subject_name' => $subject->name,
                    'current_avg'  => $currentAvg,
                    'previous_avg' => $previousAvg,
                    'trend'        => $trend,
                    'trend_icon'   => $this->trendIcon($trend),
                    'alert_level'  => $currentAvg < 40 ? 'critical' : 'warning',
                ];
            }
        }

        return $alerts;
    }

    /**
     * Top $limit students by average in the current exam.
     */
    public function getTopPerformers(int $limit = 5): array
    {
        if (!$this->currentExamId) return [];

        return ExamRecord::where('exam_id', $this->currentExamId)
            ->where('year', $this->year)
            ->where('ave', '>', 0)
            ->with(['student', 'my_class'])
            ->orderByDesc('ave')
            ->take($limit)
            ->get()
            ->map(fn($r) => [
                'student_name' => $r->student->name ?? 'Unknown',
                'class'        => $r->my_class->name ?? '—',
                'average'      => round($r->ave, 1),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Students who improved the most vs. the previous exam.
     */
    public function getMostImproved(int $limit = 5): array
    {
        if (!$this->currentExamId || !$this->previousExamId) return [];

        $current  = ExamRecord::where('exam_id', $this->currentExamId)
            ->where('year', $this->year)
            ->with(['student', 'my_class'])
            ->get()
            ->keyBy('student_id');

        $previous = ExamRecord::where('exam_id', $this->previousExamId)
            ->whereIn('student_id', $current->keys())
            ->get()
            ->keyBy('student_id');

        $improved = [];
        foreach ($current as $sid => $exr) {
            $curr = (float) ($exr->ave ?? 0);
            $prev = (float) ($previous[$sid]->ave ?? 0);
            $diff = round($curr - $prev, 1);
            if ($diff > 5 && $curr > 0) {
                $improved[] = [
                    'student_name' => $exr->student->name ?? 'Unknown',
                    'class'        => $exr->my_class->name ?? '—',
                    'current_avg'  => round($curr, 1),
                    'previous_avg' => round($prev, 1),
                    'improvement'  => $diff,
                ];
            }
        }

        usort($improved, fn($a, $b) => $b['improvement'] <=> $a['improvement']);
        return array_slice($improved, 0, $limit);
    }

    /**
     * Four headline numbers for the summary cards.
     */
    public function getSummaryStats(): array
    {
        $atRisk    = $this->getAtRiskStudents();
        $overview  = $this->getClassOverview();
        $top       = $this->getTopPerformers(1);
        $schoolAvg = count($overview)
            ? round(array_sum(array_column($overview, 'average')) / count($overview), 1)
            : 0;

        return [
            'at_risk_count'   => count($atRisk),
            'classes_at_risk' => count(array_filter($overview, fn($c) => $c['health'] === 'critical')),
            'school_average'  => $schoolAvg,
            'top_performer'   => $top[0] ?? null,
            'current_exam'    => $this->currentExamId
                ? (Exam::find($this->currentExamId)->name ?? '—') . ' (' . $this->year . ')'
                : 'No exam data',
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function resolveCurrentExamId(): ?int
    {
        // Most recent exam in the current session
        $exam = Exam::where('year', $this->year)->orderByDesc('term')->first();
        return $exam?->id;
    }

    private function resolvePreviousExamId(): ?int
    {
        if (!$this->currentExamId) return null;
        $current = Exam::find($this->currentExamId);
        if (!$current) return null;

        // Previous term in same year, or last term of previous year
        $prev = Exam::where('year', $this->year)
            ->where('term', '<', $current->term)
            ->orderByDesc('term')
            ->first();

        if (!$prev) {
            [$y1] = explode('-', $this->year);
            $prevYear = ($y1 - 1) . '-' . $y1;
            $prev = Exam::where('year', $prevYear)->orderByDesc('term')->first();
        }

        return $prev?->id;
    }

    private function currentExamTerm(): int
    {
        return $this->currentExamId
            ? (Exam::find($this->currentExamId)->term ?? 1)
            : 1;
    }

    private function previousExamTerm(): int
    {
        return $this->previousExamId
            ? (Exam::find($this->previousExamId)->term ?? 1)
            : 1;
    }

    private function riskLevel(float $avg, float $drop): string
    {
        if ($avg < 40 || $drop > 25) return 'critical';
        if ($avg < 50 || $drop > 15) return 'warning';
        return 'low';
    }

    private function riskColor(string $level): string
    {
        return ['critical' => 'danger', 'warning' => 'warning', 'low' => 'success'][$level] ?? 'secondary';
    }

    private function trend(float $current, float $previous): string
    {
        if ($previous == 0) return 'stable';
        $pct = (($current - $previous) / $previous) * 100;
        if ($pct > 5)  return 'improving';
        if ($pct < -5) return 'declining';
        return 'stable';
    }

    private function trendIcon(string $trend): string
    {
        return ['improving' => '↑', 'declining' => '↓', 'stable' => '→'][$trend] ?? '→';
    }
}
