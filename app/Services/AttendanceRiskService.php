<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ExamRecord;
use App\Models\MyClass;
use App\Models\StudentRecord;

class AttendanceRiskService
{
    // Ethiopian Ministry of Education minimum attendance requirement
    const MIN_REQUIRED    = 75;
    const CRITICAL_BELOW  = 65;

    // Risk factor weights (sum to 100)
    const WEIGHTS = [
        'attendance_critical'    => 30, // below 65%
        'attendance_warning'     => 15, // 65–74%
        'attendance_declining'   => 20, // recent trend down >10 pp
        'grades_below_50'        => 25, // academic average < 50
        'grades_declining'       => 15, // academic trend down >15 pp
        'consecutive_absences'   => 10, // 5+ days in a row
    ];

    protected string $year;

    public function __construct()
    {
        $this->year = Qs::getCurrentSession();
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Return risk assessments for all active students, optionally filtered by class.
     * Only students with risk_score > 0 OR attendance below the ministry threshold are returned.
     */
    public function getStudentRiskAssessments(?int $classId = null): array
    {
        $query = StudentRecord::where('grad', 0)->with(['user', 'my_class']);
        if ($classId) $query->where('my_class_id', $classId);

        $assessments = [];
        foreach ($query->get() as $sr) {
            $a = $this->assessStudent($sr);
            if ($a['risk_score'] > 0 || $a['attendance_percent'] < self::MIN_REQUIRED) {
                $assessments[] = $a;
            }
        }

        usort($assessments, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);
        return $assessments;
    }

    /**
     * Summary statistics for the dashboard cards.
     */
    public function getSummaryStats(?int $classId = null): array
    {
        $assessments = $this->getStudentRiskAssessments($classId);

        $critical = count(array_filter($assessments, fn($a) => $a['risk_level'] === 'critical'));
        $warning  = count(array_filter($assessments, fn($a) => $a['risk_level'] === 'warning'));

        $total = StudentRecord::where('grad', 0)
            ->when($classId, fn($q) => $q->where('my_class_id', $classId))
            ->count();

        // School-wide attendance average for the current session
        $sessionIds = AttendanceSession::where('year', $this->year)->pluck('id');
        $allRecords = AttendanceRecord::whereIn('session_id', $sessionIds)->get();
        $totalRec   = $allRecords->count();
        $presentRec = $allRecords->whereIn('status', ['present', 'late'])->count();
        $avgAtt     = $totalRec > 0 ? round(($presentRec / $totalRec) * 100, 1) : 100;

        return [
            'critical_count'      => $critical,
            'warning_count'       => $warning,
            'total_at_risk'       => $critical + $warning,
            'total_students'      => $total,
            'average_attendance'  => $avgAtt,
            'attendance_health'   => $avgAtt >= 85 ? 'good' : ($avgAtt >= 75 ? 'warning' : 'critical'),
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function assessStudent(StudentRecord $sr): array
    {
        $studentUserId = $sr->user_id;
        $att           = $this->attendanceData($studentUserId);
        $acad          = $this->academicData($studentUserId);

        $score   = 0;
        $factors = [];
        $recs    = [];

        // ── Attendance factors ───────────────────────────────────────────────
        if ($att['percent'] < self::CRITICAL_BELOW) {
            $score    += self::WEIGHTS['attendance_critical'];
            $factors[] = "Attendance critically low ({$att['percent']}% — min required 75%)";
            $recs[]    = 'Immediate parent conference required';
        } elseif ($att['percent'] < self::MIN_REQUIRED) {
            $score    += self::WEIGHTS['attendance_warning'];
            $factors[] = "Attendance below required 75% ({$att['percent']}%)";
            $recs[]    = 'Send attendance warning letter to parents';
        }

        if ($att['trend'] === 'declining' && $att['drop_pp'] > 10) {
            $score    += self::WEIGHTS['attendance_declining'];
            $factors[] = "Attendance declining (↓ {$att['drop_pp']} percentage points recently)";
            $recs[]    = 'Schedule check-in with student';
        }

        if ($att['consecutive_absences'] >= 5) {
            $score    += self::WEIGHTS['consecutive_absences'];
            $factors[] = "{$att['consecutive_absences']} consecutive absences";
            $recs[]    = 'Immediate home visit or phone call';
        }

        // ── Academic factors ─────────────────────────────────────────────────
        if ($acad['current_avg'] > 0 && $acad['current_avg'] < 50) {
            $score    += self::WEIGHTS['grades_below_50'];
            $factors[] = "Academic average below 50% ({$acad['current_avg']}%)";
            $recs[]    = 'Assign to academic support programme';
        }

        if ($acad['trend'] === 'declining' && $acad['drop_pp'] > 15) {
            $score    += self::WEIGHTS['grades_declining'];
            $factors[] = "Grades declining (↓ {$acad['drop_pp']} points vs previous exam)";
            $recs[]    = 'Schedule academic counselling';
        }

        $score = min(100, $score);

        return [
            'student_id'            => $sr->id,
            'student_user_id'       => $studentUserId,
            'student_name'          => $sr->user->name ?? 'Unknown',
            'class'                 => $sr->my_class->name ?? '—',
            'attendance_percent'    => $att['percent'],
            'attendance_trend'      => $att['trend'],
            'attendance_total'      => $att['total'],
            'attendance_present'    => $att['present'],
            'academic_avg'          => $acad['current_avg'],
            'academic_trend'        => $acad['trend'],
            'consecutive_absences'  => $att['consecutive_absences'],
            'risk_score'            => $score,
            'risk_level'            => $this->riskLevel($score),
            'risk_color'            => $this->riskColor($score),
            'risk_factors'          => $factors,
            'recommendations'       => array_slice($recs, 0, 2),
        ];
    }

    /**
     * Compute attendance stats for one student in the current session.
     */
    private function attendanceData(int $studentUserId): array
    {
        // All session IDs for this year
        $sessionIds = AttendanceSession::where('year', $this->year)->pluck('id');

        $records = AttendanceRecord::whereIn('session_id', $sessionIds)
            ->where('student_id', $studentUserId)
            ->with('session')
            ->orderByDesc('session_id')   // most recent first
            ->get();

        $total   = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();
        $percent = $total > 0 ? round(($present / $total) * 100, 1) : 100;

        // Trend: split into two halves
        $half     = (int) ceil($total / 2);
        $recent   = $records->take($half);
        $older    = $records->slice($half);

        $recentPct = $recent->count() > 0
            ? round(($recent->whereIn('status', ['present', 'late'])->count() / $recent->count()) * 100, 1)
            : $percent;
        $olderPct  = $older->count() > 0
            ? round(($older->whereIn('status', ['present', 'late'])->count() / $older->count()) * 100, 1)
            : $recentPct;

        $trend  = 'stable';
        $dropPp = 0;
        if ($recentPct < $olderPct - 5) {
            $trend  = 'declining';
            $dropPp = round($olderPct - $recentPct, 1);
        } elseif ($recentPct > $olderPct + 5) {
            $trend = 'improving';
        }

        // Consecutive absences from the most recent record backwards
        $consecutive = 0;
        foreach ($records as $r) {
            if ($r->status === 'absent') {
                $consecutive++;
            } else {
                break;
            }
        }

        return [
            'percent'              => $percent,
            'total'                => $total,
            'present'              => $present,
            'trend'                => $trend,
            'drop_pp'              => $dropPp,
            'consecutive_absences' => $consecutive,
        ];
    }

    /**
     * Compute academic stats for one student (uses ExamRecord.ave).
     */
    private function academicData(int $studentUserId): array
    {
        $records = ExamRecord::where('student_id', $studentUserId)
            ->where('year', $this->year)
            ->orderByDesc('id')
            ->get();

        $currentAvg  = round((float) ($records->first()->ave ?? 0), 1);
        $previousAvg = round((float) ($records->skip(1)->first()->ave ?? $currentAvg), 1);

        $trend  = 'stable';
        $dropPp = 0;
        if ($currentAvg < $previousAvg - 5) {
            $trend  = 'declining';
            $dropPp = round($previousAvg - $currentAvg, 1);
        } elseif ($currentAvg > $previousAvg + 5) {
            $trend = 'improving';
        }

        return [
            'current_avg'  => $currentAvg,
            'previous_avg' => $previousAvg,
            'trend'        => $trend,
            'drop_pp'      => $dropPp,
        ];
    }

    private function riskLevel(int $score): string
    {
        if ($score >= 50) return 'critical';
        if ($score >= 25) return 'warning';
        return 'low';
    }

    private function riskColor(int $score): string
    {
        if ($score >= 50) return 'danger';
        if ($score >= 25) return 'warning';
        return 'success';
    }
}
