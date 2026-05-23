<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\Setting;
use App\Models\StudentFeeInvoice;
use Carbon\Carbon;

class PenaltyService
{
    public const SETTING_ENABLED          = 'late_fee_enabled';
    public const SETTING_GRACE_DAYS       = 'late_fee_grace_days';
    public const SETTING_TYPE             = 'late_fee_type';
    public const SETTING_AMOUNT           = 'late_fee_amount';
    public const SETTING_DEFAULT_DUE_DAYS = 'late_fee_default_due_days';
    public const SETTING_NOTIFY_AFTER     = 'late_fee_notify_after_days';
    public const SETTING_FREQUENCY        = 'late_fee_penalty_frequency';

    public static function isEnabled(): bool
    {
        return (bool) (int) (Qs::getSetting(self::SETTING_ENABLED) ?: 1);
    }

    public static function getGraceDays(): int
    {
        return (int) (Qs::getSetting(self::SETTING_GRACE_DAYS) ?: 7);
    }

    public static function getType(): string
    {
        $type = Qs::getSetting(self::SETTING_TYPE) ?: 'percent';

        return in_array($type, ['percent', 'fixed'], true) ? $type : 'percent';
    }

    public static function getAmount(): float
    {
        return (float) (Qs::getSetting(self::SETTING_AMOUNT) ?: 5);
    }

    public static function getDefaultDueDays(): int
    {
        return max(1, (int) (Qs::getSetting(self::SETTING_DEFAULT_DUE_DAYS) ?: 30));
    }

    public static function getNotifyAfterDays(): int
    {
        return max(0, (int) (Qs::getSetting(self::SETTING_NOTIFY_AFTER) ?: 7));
    }

    public static function getPenaltyFrequency(): string
    {
        $freq = Qs::getSetting(self::SETTING_FREQUENCY) ?: 'weekly';
        return in_array($freq, ['once', 'daily', 'weekly'], true) ? $freq : 'weekly';
    }

    public static function getRules(): array
    {
        $rules = [
            'enabled'           => self::isEnabled(),
            'grace_days'        => self::getGraceDays(),
            'type'              => self::getType(),
            'amount'            => self::getAmount(),
            'default_due_days'  => self::getDefaultDueDays(),
            'notify_after_days' => self::getNotifyAfterDays(),
            'frequency'         => self::getPenaltyFrequency(),
        ];

        $rules['description'] = $rules['enabled']
            ? ($rules['type'] === 'percent'
                ? $rules['amount'] . '% after grace period'
                : 'ETB ' . number_format($rules['amount'], 2) . ' fixed after grace period')
            : 'Penalties are currently disabled';

        $rules['frequency_label'] = match ($rules['frequency']) {
            'once'   => 'Once per invoice',
            'daily'  => 'Daily reminder',
            default  => 'Weekly reminder',
        };

        return $rules;
    }

    public static function setRules(
        bool $enabled,
        int $graceDays,
        string $type,
        float $amount,
        int $defaultDueDays,
        int $notifyAfterDays,
        string $frequency
    ): void {
        Setting::updateOrCreate(['type' => self::SETTING_ENABLED], ['description' => $enabled ? '1' : '0']);
        Setting::updateOrCreate(['type' => self::SETTING_GRACE_DAYS], ['description' => (string) $graceDays]);
        Setting::updateOrCreate(['type' => self::SETTING_TYPE], ['description' => $type]);
        Setting::updateOrCreate(['type' => self::SETTING_AMOUNT], ['description' => (string) $amount]);
        Setting::updateOrCreate(['type' => self::SETTING_DEFAULT_DUE_DAYS], ['description' => (string) max(1, $defaultDueDays)]);
        Setting::updateOrCreate(['type' => self::SETTING_NOTIFY_AFTER], ['description' => (string) max(0, $notifyAfterDays)]);
        Setting::updateOrCreate(['type' => self::SETTING_FREQUENCY], ['description' => $frequency]);
    }

    public static function calculateFine(StudentFeeInvoice $invoice): float
    {
        if (!self::isEnabled() || $invoice->status === 'paid' || !$invoice->due_date) {
            return 0;
        }

        $dueWithGrace = Carbon::parse($invoice->due_date)->addDays(self::getGraceDays());
        if (now()->lte($dueWithGrace)) {
            return 0;
        }

        $base = (float) $invoice->original_amount - (float) $invoice->discount;

        if (self::getType() === 'fixed') {
            return round(self::getAmount(), 2);
        }

        return round($base * (self::getAmount() / 100), 2);
    }

    /**
     * Ensure overdue penalty is saved on the invoice (updates balance parents see).
     */
    public static function syncPenaltyForInvoice(StudentFeeInvoice $invoice): void
    {
        if ($invoice->status === 'paid') {
            return;
        }

        $fine = self::calculateFine($invoice);
        if ($fine <= 0 && (float) $invoice->fine <= 0) {
            return;
        }

        if ($fine > 0) {
            $invoice->fine        = $fine;
            $invoice->fine_reason = 'Late payment penalty';
            $invoice->net_amount  = max(0, $invoice->original_amount - $invoice->discount + $fine);
            $invoice->save();
            $invoice->syncStatus();
        }
    }

    public static function isOverdue(StudentFeeInvoice $invoice): bool
    {
        return self::calculateFine($invoice) > 0;
    }

    public static function penaltySummaryForParent(): array
    {
        return self::getRules();
    }

    public static function applyToOverdueInvoices(): int
    {
        $count = 0;

        StudentFeeInvoice::whereIn('status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->chunkById(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    $fine = self::calculateFine($invoice);
                    if ($fine <= 0) {
                        continue;
                    }

                    $invoice->fine        = $fine;
                    $invoice->fine_reason = 'Late payment penalty (auto)';
                    $invoice->net_amount  = max(0, $invoice->original_amount - $invoice->discount + $fine);
                    $invoice->save();
                    $invoice->syncStatus();
                    $count++;
                }
            });

        return $count;
    }
}
