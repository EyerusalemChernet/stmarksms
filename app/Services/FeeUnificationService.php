<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\StudentFeeInvoice;
use App\Models\StudentRecord;
use Illuminate\Support\Str;

class FeeUnificationService
{
    public static function hasUnpaidFees(int $studentId): bool
    {
        return StudentFeeInvoice::where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('balance', '>', 0)
            ->exists();
    }

    public static function countUnpaidInvoices(int $studentId): int
    {
        return StudentFeeInvoice::where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('balance', '>', 0)
            ->count();
    }

    public static function ensureInvoiceForPaymentRecord(int $paymentRecordId): StudentFeeInvoice
    {
        $pr = PaymentRecord::with(['payment', 'student', 'receipt'])->findOrFail($paymentRecordId);

        if ($pr->migrated_to_invoice_id) {
            return StudentFeeInvoice::findOrFail($pr->migrated_to_invoice_id);
        }

        return (new self())->migratePaymentRecord($pr);
    }

    public function migratePaymentRecord(PaymentRecord $pr): StudentFeeInvoice
    {
        $existing = StudentFeeInvoice::where('legacy_payment_record_id', $pr->id)->first();
        if ($existing) {
            $this->linkPaymentRecord($pr, $existing);
            return $existing;
        }

        $pr->loadMissing(['payment', 'student', 'receipt']);
        $payment = $pr->payment;
        if (!$payment) {
            throw new \RuntimeException("Payment record #{$pr->id} has no linked fee type.");
        }

        $structure = $this->ensureStructureForLegacyPayment($payment, (int) $pr->student_id);
        $session   = $pr->year ?: $payment->year ?: Qs::getCurrentSession();
        $amount    = (float) $payment->amount;

        $invoice = StudentFeeInvoice::create([
            'invoice_no'                => 'INV-L' . str_pad((string) $pr->id, 6, '0', STR_PAD_LEFT),
            'student_id'                => $pr->student_id,
            'fee_structure_id'          => $structure->id,
            'session'                   => $session,
            'original_amount'           => $amount,
            'discount'                  => 0,
            'fine'                      => 0,
            'net_amount'                => $amount,
            'amount_paid'               => 0,
            'balance'                   => $amount,
            'status'                    => 'unpaid',
            'due_date'                  => now()->addDays(30)->toDateString(),
            'legacy_payment_record_id'  => $pr->id,
            'chapa_ref'                 => $pr->chapa_ref,
            'chapa_status'              => $pr->chapa_status,
        ]);

        $this->importLegacyReceipts($invoice, $pr);
        $invoice->syncStatus();
        $this->linkPaymentRecord($pr, $invoice);

        return $invoice->fresh(['payments', 'fee_structure.category', 'student']);
    }

    public function migrateAllLegacyRecords(): array
    {
        $stats = ['migrated' => 0, 'skipped' => 0, 'errors' => 0];

        PaymentRecord::with(['payment', 'receipt'])
            ->whereNull('migrated_to_invoice_id')
            ->orderBy('id')
            ->chunk(100, function ($records) use (&$stats) {
                foreach ($records as $pr) {
                    try {
                        if (StudentFeeInvoice::where('legacy_payment_record_id', $pr->id)->exists()) {
                            $stats['skipped']++;
                            continue;
                        }
                        $this->migratePaymentRecord($pr);
                        $stats['migrated']++;
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                    }
                }
            });

        return $stats;
    }

    protected function ensureStructureForLegacyPayment(Payment $payment, int $studentUserId): FeeStructure
    {
        $session = $payment->year ?: Qs::getCurrentSession();
        $sr      = StudentRecord::where('user_id', $studentUserId)->first();
        $classId = $payment->my_class_id ?: ($sr->my_class_id ?? null);

        if (!$classId) {
            throw new \RuntimeException('Cannot migrate fee: student class is unknown.');
        }

        $code = 'LEG' . substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($payment->title)), 0, 6);
        if (strlen($code) < 3) {
            $code = 'LEGFEE';
        }
        $code = substr($code, 0, 10);

        $cat = FeeCategory::firstOrCreate(
            ['code' => $code],
            [
                'name'        => $payment->title,
                'description' => 'Migrated from legacy school payments',
                'active'      => true,
            ]
        );

        $structure = FeeStructure::firstOrCreate(
            [
                'fee_category_id' => $cat->id,
                'my_class_id'     => $classId,
                'session'         => $session,
            ],
            [
                'amount'        => $payment->amount,
                'installments'  => 1,
                'active'        => true,
            ]
        );

        if ((float) $structure->amount !== (float) $payment->amount) {
            $structure->update(['amount' => $payment->amount]);
        }

        return $structure;
    }

    protected function importLegacyReceipts(StudentFeeInvoice $invoice, PaymentRecord $pr): void
    {
        $installment = 0;
        foreach ($pr->receipt as $receipt) {
            $installment++;
            $ref = $receipt->transaction_ref ?? ('LEG-R' . $receipt->id);

            if (FeePayment::where('transaction_ref', $ref)->exists()) {
                continue;
            }

            FeePayment::create([
                'receipt_no'       => 'REC-L' . str_pad((string) $receipt->id, 6, '0', STR_PAD_LEFT),
                'invoice_id'       => $invoice->id,
                'student_id'       => $invoice->student_id,
                'collected_by'     => auth()->id() ?? $invoice->student_id,
                'amount'           => $receipt->amt_paid,
                'installment_no'   => $installment,
                'payment_method'   => $this->mapPaymentMethod($receipt->payment_method ?? 'cash'),
                'transaction_ref'  => $ref,
                'notes'            => 'Migrated from legacy payment record #' . $pr->id,
                'paid_at'          => $receipt->created_at ?? now(),
            ]);
        }
    }

    protected function mapPaymentMethod(?string $method): string
    {
        $allowed = ['cash', 'bank_transfer', 'mobile_money', 'chapa'];
        return in_array($method, $allowed, true) ? $method : 'cash';
    }

    protected function linkPaymentRecord(PaymentRecord $pr, StudentFeeInvoice $invoice): void
    {
        $pr->update(['migrated_to_invoice_id' => $invoice->id]);
    }
}
