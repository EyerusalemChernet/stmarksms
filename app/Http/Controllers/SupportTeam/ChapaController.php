<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Repositories\PaymentRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChapaController extends Controller
{
    protected $pay;

    public function __construct(PaymentRepo $pay)
    {
        $this->middleware('auth');
        $this->pay = $pay;
    }

    /**
     * Initiate a Chapa payment for a payment record.
     */
    public function initiate(Request $req, $pr_id)
    {
        $pr = PaymentRecord::with(['payment', 'student.user'])->findOrFail($pr_id);

        $balance = $pr->payment->amount - $pr->amt_paid;
        if ($balance <= 0) {
            return back()->with('flash_danger', 'This payment is already fully settled.');
        }

        $txRef = 'SMS-' . strtoupper(Str::random(12));
        $student = $pr->student->user ?? null;

        $payload = [
            'amount'           => $balance,
            'currency'         => 'ETB',
            'email'            => $student->email ?? 'parent@school.et',
            'first_name'       => $student ? explode(' ', $student->name)[0] : 'Student',
            'last_name'        => $student ? (explode(' ', $student->name)[1] ?? '') : '',
            'tx_ref'           => $txRef,
            'callback_url'     => route('chapa.callback'),
            'return_url'       => route('chapa.return', $pr_id),
            'customization'    => [
                'title'       => 'School Fee Payment',
                'description' => $pr->payment->title ?? 'Fee',
            ],
        ];

        $secretKey = config('services.chapa.secret_key', env('CHAPA_SECRET_KEY', ''));

        if (empty($secretKey)) {
            // Fallback: no Chapa key configured — redirect to manual payment
            return back()->with('flash_danger', 'Online payment is not configured. Please pay at the school office.');
        }

        try {
            $response = Http::withToken($secretKey)
                ->post('https://api.chapa.co/v1/transaction/initialize', $payload);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                // Store the tx_ref on the payment record
                $pr->update(['chapa_ref' => $txRef, 'chapa_status' => 'pending']);
                AuditLog::log('initiated', 'payments', "Chapa payment initiated for PR#{$pr_id}, ref: {$txRef}");
                return redirect($data['data']['checkout_url']);
            }

            return back()->with('flash_danger', 'Payment gateway error: ' . ($data['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            return back()->with('flash_danger', 'Could not connect to payment gateway. Please try again or pay at the office.');
        }
    }

    /**
     * Chapa webhook callback (server-to-server).
     */
    public function callback(Request $req)
    {
        $txRef = $req->input('trx_ref') ?? $req->input('tx_ref');
        if (!$txRef) return response('Missing tx_ref', 400);

        $this->processPayment($txRef);
        return response('OK', 200);
    }

    /**
     * Return URL after Chapa redirect.
     */
    public function returnUrl(Request $req, $pr_id)
    {
        $pr = PaymentRecord::findOrFail($pr_id);

        if ($pr->chapa_ref) {
            $this->processPayment($pr->chapa_ref);
            $pr->refresh();
        }

        if ($pr->chapa_status === 'success') {
            return redirect()->route('payments.invoice', $pr->student_id)
                ->with('flash_success', 'Payment completed successfully via Chapa.');
        }

        return redirect()->route('payments.invoice', $pr->student_id)
            ->with('flash_danger', 'Payment could not be verified. Please contact the school office.');
    }

    /**
     * Verify and apply a Chapa payment by tx_ref.
     */
    protected function processPayment(string $txRef): void
    {
        $pr = PaymentRecord::where('chapa_ref', $txRef)->first();
        if (!$pr || $pr->chapa_status === 'success') return;

        $secretKey = config('services.chapa.secret_key', env('CHAPA_SECRET_KEY', ''));
        if (empty($secretKey)) return;

        try {
            $response = Http::withToken($secretKey)
                ->get("https://api.chapa.co/v1/transaction/verify/{$txRef}");

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                $amount = $data['data']['amount'] ?? 0;
                $payment = $pr->payment;
                $newPaid = $pr->amt_paid + $amount;
                $balance = $payment->amount - $newPaid;

                $pr->update([
                    'amt_paid'     => $newPaid,
                    'balance'      => max(0, $balance),
                    'paid'         => $balance <= 0 ? 1 : 0,
                    'chapa_status' => 'success',
                ]);

                Receipt::create([
                    'pr_id'          => $pr->id,
                    'amt_paid'       => $amount,
                    'balance'        => max(0, $balance),
                    'year'           => Qs::getCurrentSession(),
                    'payment_method' => 'chapa',
                    'transaction_ref' => $txRef,
                    'payment_status' => 'completed',
                ]);

                AuditLog::log('payment', 'payments', "Chapa payment verified for PR#{$pr->id}, amount: {$amount}");
            } else {
                $pr->update(['chapa_status' => 'failed']);
            }
        } catch (\Exception $e) {
            // Silent fail — will retry on next callback
        }
    }
}
