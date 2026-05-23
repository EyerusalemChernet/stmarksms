<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Message;
use App\Models\StudentFeeInvoice;
use App\Models\StudentRecord;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ParentOverdueFeeNotifier
{
    /**
     * Notify parents of unpaid invoices past the configured day threshold.
     *
     * @return array{messages: int, announcements: int, penalties: int}
     */
    public static function run(): array
    {
        if (!PenaltyService::isEnabled()) {
            return ['messages' => 0, 'announcements' => 0, 'penalties' => 0];
        }

        $notifyAfter = PenaltyService::getNotifyAfterDays();
        if ($notifyAfter < 0) {
            return ['messages' => 0, 'announcements' => 0, 'penalties' => 0];
        }

        $systemUserId = User::where('user_type', 'super_admin')->value('id')
            ?? User::where('user_type', 'admin')->value('id');

        $stats = ['messages' => 0, 'announcements' => 0, 'penalties' => 0];

        StudentFeeInvoice::with(['student'])
            ->where('status', 'unpaid')
            ->where('balance', '>', 0)
            ->whereNotNull('due_date')
            ->chunkById(100, function ($invoices) use ($notifyAfter, $systemUserId, &$stats) {
                foreach ($invoices as $invoice) {
                    if (!self::isPastNotifyThreshold($invoice, $notifyAfter)) {
                        continue;
                    }

                    if (!self::shouldNotifyByFrequency($invoice)) {
                        continue;
                    }

                    PenaltyService::syncPenaltyForInvoice($invoice);
                    if ($invoice->fine > 0) {
                        $stats['penalties']++;
                    }

                    $parentId = StudentRecord::where('user_id', $invoice->student_id)->value('my_parent_id');
                    if ($parentId) {
                        $studentName = $invoice->student->name ?? 'Student';
                        $dueStr      = Carbon::parse($invoice->due_date)->format('d M Y');
                        $body        = "Fee invoice {$invoice->invoice_no} for {$studentName} was due on {$dueStr}. "
                            . 'Outstanding balance: ETB ' . number_format((float) $invoice->balance, 2) . '. '
                            . 'Please pay as soon as possible to avoid further penalties.';

                        Message::create([
                            'sender_id'   => $systemUserId,
                            'receiver_id' => $parentId,
                            'subject'     => 'Overdue school fee — ' . $invoice->invoice_no,
                            'body'        => $body,
                            'read'        => false,
                            'archived'    => false,
                        ]);
                        $stats['messages']++;
                    }

                    if (Schema::hasColumn('student_fee_invoices', 'overdue_notified_at')) {
                        $invoice->overdue_notified_at = now();
                        $invoice->save();
                    }
                }
            });

        return $stats;
    }

    public static function isPastNotifyThreshold(StudentFeeInvoice $invoice, ?int $notifyAfter = null): bool
    {
        $notifyAfter = $notifyAfter ?? PenaltyService::getNotifyAfterDays();
        $due         = Carbon::parse($invoice->due_date)->startOfDay();
        $today       = now()->startOfDay();

        if ($today->lt($due)) {
            return false;
        }

        $daysUnpaid = $due->diffInDays($today);

        return $daysUnpaid >= $notifyAfter;
    }

    public static function shouldNotifyByFrequency(StudentFeeInvoice $invoice): bool
    {
        $frequency = PenaltyService::getPenaltyFrequency();
        $last      = $invoice->overdue_notified_at ?? null;

        if ($frequency === 'once') {
            return $last === null;
        }

        if (!$last) {
            return true;
        }

        $lastAt = Carbon::parse($last);

        if ($frequency === 'daily') {
            return $lastAt->lt(now()->subDay());
        }

        if ($frequency === 'weekly') {
            return $lastAt->lt(now()->subWeek());
        }

        return $last === null;
    }

    public static function postParentsPolicyAnnouncement(?int $authorId): int
    {
        if (!$authorId) {
            return 0;
        }

        $rules = PenaltyService::getRules();

        Announcement::create([
            'author_id' => $authorId,
            'title'     => 'Overdue fee payment reminder',
            'body'      => "There are overdue student fee invoice(s). Payments are required "
                . $rules['notify_after_days'] . ' day(s) after the due date. '
                . 'Penalty policy: ' . ($rules['description'] ?? '') . ' '
                . "Please check your child's fees page for details.",
            'audience'  => 'parents',
            'active'    => true,
        ]);

        return 1;
    }
}
