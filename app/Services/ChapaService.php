<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\AuditLog;
use App\Models\FeePayment;
use App\Models\PaymentRecord;
use App\Models\StudentFeeInvoice;
use App\Models\StudentRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChapaService
{
    public function initiateForInvoice(StudentFeeInvoice $invoice, ?string $returnRoute = null, array $returnParams = []): RedirectResponse
    {
        $invoice->loadMissing(['student', 'fee_structure.category']);

        if ($invoice->balance <= 0) {
            return back()->with('flash_danger', 'This invoice is already fully paid.');
        }

        if ($invoice->chapa_status === 'pending') {
            return back()->with('flash_danger', 'A Chapa payment is already in progress for this invoice.');
        }

        $secretKey = $this->secretKey();
        if (empty($secretKey)) {
            return back()->with('flash_danger', 'Online payment is not configured. Please pay at the school office.');
        }

        $student = $invoice->student;
        $name    = $student->name ?? 'Student';
        $parts   = explode(' ', $name, 2);
        $txRef   = 'INV-' . $invoice->id . '-' . strtoupper(Str::random(8));

        $returnRoute = $returnRoute ?: 'chapa.fee.return';
        $returnParams = array_merge(['invoice_id' => Qs::hash($invoice->id)], $returnParams);

        $payload = [
            'amount'        => $invoice->balance,
            'currency'      => 'ETB',
            'email'         => $student->email ?? 'parent@school.et',
            'first_name'    => $parts[0],
            'last_name'     => $parts[1] ?? '',
            'tx_ref'        => $txRef,
            'callback_url'  => route('chapa.webhook'),
            'return_url'    => route($returnRoute, $returnParams),
            'customization'   => [
                'title'       => substr(config('app.name', 'School'), 0, 10) . ' Fees',
                'description' => optional($invoice->fee_structure->category)->name ?? $invoice->invoice_no,
            ],
        ];

        try {
            $response = Http::withToken($secretKey)
                ->post('https://api.chapa.co/v1/transaction/initialize', $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                $invoice->update(['chapa_ref' => $txRef, 'chapa_status' => 'pending']);
                AuditLog::log('initiated', 'finance', "Chapa payment initiated for invoice #{$invoice->id}, ref: {$txRef}");

                return redirect($data['data']['checkout_url']);
            }

            if (is_array($data['message'] ?? null)) {
                $errorMessage = 'Payment gateway error: ' . json_encode($data['message']);
            } else {
                $errorMessage = 'Payment gateway error: ' . ($data['message'] ?? 'Unknown error');
            }
            \Log::error('Chapa API Error', ['response' => $data, 'status' => $response->status()]);
            return back()->with('flash_danger', $errorMessage);
        } catch (\Throwable $e) {
            \Log::error('Chapa Connection Error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('flash_danger', 'Payment gateway error: ' . $e->getMessage());
        }
    }

    public function processByTxRef(string $txRef): bool
    {
        $invoice = StudentFeeInvoice::where('chapa_ref', $txRef)->first();
        if ($invoice) {
            return $this->verifyAndApplyToInvoice($invoice, $txRef);
        }

        $pr = PaymentRecord::where('chapa_ref', $txRef)->first();
        if ($pr) {
            $invoice = FeeUnificationService::ensureInvoiceForPaymentRecord($pr->id);
            if ($invoice->chapa_ref !== $txRef) {
                $invoice->update(['chapa_ref' => $txRef, 'chapa_status' => $pr->chapa_status ?? 'pending']);
            }
            return $this->verifyAndApplyToInvoice($invoice, $txRef);
        }

        return false;
    }

    public function verifyAndApplyToInvoice(StudentFeeInvoice $invoice, string $txRef): bool
    {
        if ($invoice->chapa_status === 'success' || $invoice->balance <= 0) {
            return $invoice->chapa_status === 'success';
        }

        $secretKey = $this->secretKey();
        if (empty($secretKey)) {
            return false;
        }

        try {
            $response = Http::withToken($secretKey)
                ->get("https://api.chapa.co/v1/transaction/verify/{$txRef}");

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                DB::transaction(function () use ($invoice, $txRef) {
                    $payAmount = (float) $invoice->balance;
                    if ($payAmount <= 0) {
                        return;
                    }

                    FeePayment::create([
                        'receipt_no'      => 'REC-CH-' . strtoupper(substr($txRef, -8)),
                        'invoice_id'      => $invoice->id,
                        'student_id'      => $invoice->student_id,
                        'collected_by'    => Auth::id() ?? $invoice->student_id,
                        'amount'          => $payAmount,
                        'installment_no'  => 1,
                        'payment_method'  => 'chapa',
                        'transaction_ref' => $txRef,
                        'notes'           => 'Paid via Chapa',
                        'paid_at'         => now(),
                    ]);

                    $invoice->refresh()->syncStatus();
                    $invoice->update(['chapa_status' => 'success']);

                    if ($invoice->legacy_payment_record_id) {
                        $this->syncLegacyPaymentRecord($invoice, $payAmount, $txRef);
                    }
                });

                AuditLog::log('payment', 'finance', "Chapa payment verified for invoice #{$invoice->id}, ref: {$txRef}");
                return true;
            }

            $invoice->update(['chapa_status' => 'failed']);
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function syncLegacyPaymentRecord(StudentFeeInvoice $invoice, float $amount, string $txRef): void
    {
        $pr = PaymentRecord::find($invoice->legacy_payment_record_id);
        if (!$pr) {
            return;
        }

        $payment = $pr->payment;
        if (!$payment) {
            return;
        }

        $newPaid = (float) $pr->amt_paid + $amount;
        $balance = max(0, (float) $payment->amount - $newPaid);

        $pr->update([
            'amt_paid'     => $newPaid,
            'balance'      => $balance,
            'paid'         => $balance <= 0 ? 1 : 0,
            'chapa_status' => 'success',
        ]);
    }

    public function parentOwnsInvoice(StudentFeeInvoice $invoice): bool
    {
        if (!Auth::check() || Auth::user()->user_type !== 'parent') {
            return false;
        }

        return StudentRecord::where('user_id', $invoice->student_id)
            ->where('my_parent_id', Auth::id())
            ->where('grad', 0)
            ->exists();
    }

    protected function secretKey(): string
    {
        return config('services.chapa.secret_key', env('CHAPA_SECRET_KEY', ''));
    }
}
