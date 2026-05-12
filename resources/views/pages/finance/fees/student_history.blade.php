@extends('layouts.master')
@section('page_title', 'Student Fee History')
@section('content')

{{-- Summary --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center" style="gap:16px;">
                    <img src="{{ $student->photo }}" class="rounded-circle" style="width:52px;height:52px;object-fit:cover;">
                    <div>
                        <h5 class="mb-0">{{ $student->name }}</h5>
                        <small class="text-muted">{{ $student->email }}</small>
                    </div>
                    <div class="ml-auto d-flex" style="gap:16px;">
                        <div class="text-center">
                            <div style="font-size:11px;color:#94a3b8;">TOTAL DUE</div>
                            <div style="font-weight:700;font-size:18px;">ETB {{ number_format($total_due,2) }}</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:11px;color:#94a3b8;">TOTAL PAID</div>
                            <div style="font-weight:700;font-size:18px;color:#22c55e;">ETB {{ number_format($total_paid,2) }}</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:11px;color:#94a3b8;">BALANCE</div>
                            <div style="font-weight:700;font-size:18px;color:{{ $balance > 0 ? '#ef4444' : '#22c55e' }};">ETB {{ number_format($balance,2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($invoices as $inv)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold">{{ $inv->fee_structure->category->name ?? '-' }}</span>
            <span class="text-muted ml-2">{{ $inv->fee_structure->my_class->name ?? '' }} — {{ $inv->session }}</span>
            <code class="ml-2" style="font-size:11px;">{{ $inv->invoice_no }}</code>
        </div>
        <div class="d-flex align-items-center" style="gap:8px;">
            @php $badge = ['unpaid'=>'danger','partial'=>'warning','paid'=>'success'][$inv->status] @endphp
            <span class="badge badge-{{ $badge }}">{{ ucfirst($inv->status) }}</span>
            <a href="{{ route('fees.invoice', $inv->id) }}" class="btn btn-xs btn-primary">View</a>
        </div>
    </div>
    <div class="card-body py-2">
        <div class="row text-center">
            <div class="col"><small class="text-muted d-block">Original</small>ETB {{ number_format($inv->original_amount,2) }}</div>
            <div class="col"><small class="text-muted d-block">Discount</small><span class="text-success">- ETB {{ number_format($inv->discount,2) }}</span></div>
            <div class="col"><small class="text-muted d-block">Fine</small><span class="text-danger">+ ETB {{ number_format($inv->fine,2) }}</span></div>
            <div class="col"><small class="text-muted d-block">Net</small><strong>ETB {{ number_format($inv->net_amount,2) }}</strong></div>
            <div class="col"><small class="text-muted d-block">Paid</small><span class="text-success">ETB {{ number_format($inv->amount_paid,2) }}</span></div>
            <div class="col"><small class="text-muted d-block">Balance</small><span class="text-danger">ETB {{ number_format($inv->balance,2) }}</span></div>
        </div>
        @if($inv->payments->count())
        <hr class="my-2">
        <div style="font-size:12px;">
            @foreach($inv->payments as $pmt)
            <span class="badge badge-light border mr-1">
                <i class="bi bi-check-circle text-success mr-1"></i>
                ETB {{ number_format($pmt->amount,2) }} — {{ ucwords(str_replace('_',' ',$pmt->payment_method)) }} — {{ $pmt->paid_at ? $pmt->paid_at->format('d M Y') : '' }}
                <a href="{{ route('fees.receipt', $pmt->id) }}" class="ml-1" target="_blank"><i class="bi bi-printer"></i></a>
            </span>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endforeach

@if($invoices->isEmpty())
<div class="alert alert-info">No fee records found for this student.</div>
@endif

<a href="{{ route('fees.invoices') }}" class="btn btn-light"><i class="bi bi-arrow-left mr-1"></i>Back</a>
@endsection
