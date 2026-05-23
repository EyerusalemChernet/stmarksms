@extends('layouts.master')
@section('page_title', 'Pay School Fees')
@section('content')

<div class="content-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-credit-card mr-2"></i>
                            Pay School Fees - {{ $sr->user->name }}
                        </h6>
                    </div>
                    
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Student Information</h6>
                                <p><strong>Name:</strong> {{ $sr->user->name }}</p>
                                <p><strong>Class:</strong> {{ $sr->my_class->name }}</p>
                                <p><strong>Admission No:</strong> {{ $sr->adm_no }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Fee Information</h6>
                                <p><strong>Category:</strong> {{ $invoice->fee_structure->category->name }}</p>
                                <p><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</p>
                                <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>Original Amount</strong></td>
                                    <td class="text-right">ETB {{ number_format($invoice->original_amount, 2) }}</td>
                                </tr>
                                @if($invoice->discount > 0)
                                <tr class="text-success">
                                    <td><strong>Discount Applied</strong></td>
                                    <td class="text-right">- ETB {{ number_format($invoice->discount, 2) }}</td>
                                </tr>
                                @endif
                                @if($invoice->fine > 0)
                                <tr class="text-warning">
                                    <td><strong>Late Fee/Penalty</strong></td>
                                    <td class="text-right">+ ETB {{ number_format($invoice->fine, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Net Amount</strong></td>
                                    <td class="text-right">ETB {{ number_format($invoice->net_amount, 2) }}</td>
                                </tr>
                                @if($invoice->amount_paid > 0)
                                <tr class="text-info">
                                    <td><strong>Amount Paid</strong></td>
                                    <td class="text-right">ETB {{ number_format($invoice->amount_paid, 2) }}</td>
                                </tr>
                                @endif
                                <tr class="table-primary">
                                    <td><strong>Balance Due</strong></td>
                                    <td class="text-right"><strong>ETB {{ number_format($invoice->balance, 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>

                        @if($invoice->balance > 0)
                        <div class="text-center">
                            <form action="{{ route('parent.fee.chapa', Qs::hash($invoice->id)) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-credit-card mr-2"></i>
                                    Pay ETB {{ number_format($invoice->balance, 2) }} with Chapa
                                </button>
                            </form>
                            <p class="text-muted mt-2">
                                <small>Secure online payment powered by Chapa</small>
                            </p>
                        </div>
                        @else
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle mr-2"></i>
                            This invoice is fully paid
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Payment History</h6>
                    </div>
                    <div class="card-body">
                        @if($invoice->payments->count() > 0)
                        @foreach($invoice->payments as $payment)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <div>
                                <div class="font-weight-semibold">ETB {{ number_format($payment->amount, 2) }}</div>
                                <small class="text-muted">{{ $payment->paid_at->format('M d, Y') }}</small>
                                <br>
                                <small class="text-muted">{{ ucfirst($payment->payment_method) }}</small>
                            </div>
                            <div>
                                <a href="{{ route('parent.receipt', Qs::hash($payment->id)) }}" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-download"></i> Receipt
                                </a>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <p class="text-muted">No payments made yet</p>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Payment Methods</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-primary">
                                <i class="bi bi-credit-card mr-2"></i>Online Payment
                            </h6>
                            <p class="small text-muted">Pay securely online using Chapa. Accepts bank transfers, mobile money, and cards.</p>
                        </div>
                        <div>
                            <h6 class="text-secondary">
                                <i class="bi bi-building mr-2"></i>Office Payment
                            </h6>
                            <p class="small text-muted">Visit the school finance office during business hours for cash or check payments.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection