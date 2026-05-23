<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Repositories\ExamRepo;

class PromotionInsightService
{
    /**
     * Build academic/context summary for a promotion row (display + optional storage).
     */
    public static function forStudent(int $studentId, string $session, string $status, ?int $passMark = null): array
    {
        $passMark = $passMark ?? (int) (Qs::getSetting('custom_pass_mark') ?: 50);
        $avg      = ExamRepo::getSessionAverage($studentId, $session);
        $attPct   = RulesEngine::getAttendancePercentage($studentId, $session);
        $unpaid   = RulesEngine::hasUnpaidFees($studentId);
        $blocked  = RulesEngine::isResultBlocked($studentId, $session);

        $reasons = [];
        $flags   = [];

        if ($avg === null) {
            $reasons[] = 'No exam marks recorded for this session';
            $flags[]   = 'no_marks';
        } elseif ($avg < $passMark) {
            $reasons[] = sprintf('Session average %.1f%% is below pass mark (%d%%)', $avg, $passMark);
            $flags[]   = 'below_pass';
        } else {
            $reasons[] = sprintf('Session average %.1f%% meets pass mark (%d%%)', $avg, $passMark);
        }

        if ($attPct < 75) {
            $reasons[] = sprintf('Attendance %.1f%% (below 75%% threshold)', $attPct);
            $flags[]   = 'low_attendance';
        }

        if ($unpaid) {
            $reasons[] = 'Outstanding fee balance';
            $flags[]   = 'unpaid_fees';
        }

        if ($blocked) {
            $reasons[] = 'Results blocked by school rules (fees or attendance)';
            $flags[]   = 'blocked';
        }

        $summary = match ($status) {
            'P' => 'Promoted to next class/section',
            'G' => 'Marked as graduated',
            'D' => self::notPromotedSummary($avg, $passMark, $flags),
            default => 'Promotion recorded',
        };

        if ($status === 'D' && empty(array_intersect($flags, ['below_pass', 'no_marks', 'low_attendance', 'unpaid_fees', 'blocked']))) {
            $reasons[] = 'Manual decision — academic metrics did not automatically block promotion';
        }

        return [
            'session_average' => $avg,
            'attendance_pct'  => $attPct,
            'has_unpaid_fees' => $unpaid,
            'results_blocked' => $blocked,
            'pass_mark'       => $passMark,
            'summary'         => $summary,
            'reasons'         => $reasons,
            'flags'           => $flags,
        ];
    }

    protected static function notPromotedSummary(?float $avg, int $passMark, array $flags): string
    {
        if (in_array('no_marks', $flags, true)) {
            return 'Not promoted — no marks on file for this session';
        }
        if (in_array('below_pass', $flags, true)) {
            return sprintf('Not promoted — average below %d%% pass mark', $passMark);
        }
        if (in_array('blocked', $flags, true) || in_array('unpaid_fees', $flags, true)) {
            return 'Not promoted — blocked by fees or attendance rules';
        }
        if (in_array('low_attendance', $flags, true)) {
            return 'Not promoted — attendance below required level';
        }

        return 'Not promoted — review report card for full details';
    }
}
