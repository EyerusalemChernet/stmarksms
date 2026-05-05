@extends('layouts.master')
@section('page_title', 'Payments')
@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius:12px;border-left:4px solid #22c55e !important;">
            <div class="card-body py-3">
                <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;">Today's Collection</div>
                <div style="font-size:22px;font-weight:700;color:#22c55e;">ETB {{ number_format($total_today,2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius:12px;border-left:4px solid #3b82f6 !important;">
            <div class="card-body py-3">
                <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;">This Month</div>
                <div style="font-size:22px;font-weight:700;color:#3b82f6;">ETB {{ number_format($total_month,2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-header border-0 bg-white pt-3 pb-2 px-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" placeholder="From"></div>
            <div class="col-auto"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" placeholder="To"></div>
            <div class="col-auto">
                <select name="method" class="form-control form-control-sm">
                    <option value="">All Methods</option>
                    <option value="cash" {{ request('method')=='cash'?'selected':'' }}>Cash</option>
                    <option value="bank_transfer" {{ request('method')=='bank_transfer'?'selected':'' }}>Bank Transfer</option>
                    <option value="mobile_money" {{ request('method')=='mobile_money'?'selected':'' }}>Mobile Money</option>
                </select>
            </div>
            <div class="col-auto"><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search student..."></div>
            <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button></div>
            @if(request()->hasAny(['date_from','date_to','method','search']))
            <div class="col-auto"><a href="{{ route('fees.payments') }}" class="btn btn-light btn-sm">Clear</a></div>
            @endif
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead style="background:#f8fafc;">
                    <tr class="px-4">
                        <th class="px-4 py-3">#</th><th>Receipt No</th><th>Student</th><th>Fee Type</th>
                        <th>Amount</th><th>Method</th><th>Installment</th><th>Collected By</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $i => $pmt)
                    <tr>
                        <td class="px-4">{{ $payments->firstItem() + $i }}</td>
                        <td><code style="font-size:11px;">{{ $pmt->receipt_no }}</code></td>
                        <td>{{ $pmt->student->name ?? '-' }}</td>
                        <td><span class="badge" style="background:#eff6ff;color:#3b82f6;">{{ $pmt->invoice->fee_structure->category->name ?? '-' }}</span></td>
                        <td class="text-success font-weight-bold">ETB {{ number_format($pmt->amount,2) }}</td>
                        <td>{{ ucwords(str_replace('_',' ',$pmt->payment_method)) }}</td>
                        <td class="text-center">{{ $pmt->installment_no }}</td>
                        <td>{{ $pmt->collector->name ?? '-' }}</td>
                        <td class="text-muted">{{ $pmt->paid_at ? $pmt->paid_at->format('d M Y H:i') : '-' }}</td>
                        <td><a href="{{ route('fees.receipt', $pmt->id) }}" class="btn btn-xs btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-5">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="px-4 py-3">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
