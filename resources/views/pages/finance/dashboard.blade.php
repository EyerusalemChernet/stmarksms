@extends('layouts.master')
@section('page_title', 'Finance Dashboard')
@section('content')

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label'=>'Fees Collected',  'value'=>$total_fees_collected, 'icon'=>'bi-cash-coin',       'color'=>'#22c55e', 'bg'=>'#f0fdf4'],
        ['label'=>'Pending Fees',    'value'=>$total_pending,        'icon'=>'bi-hourglass-split',  'color'=>'#f59e0b', 'bg'=>'#fffbeb'],
        ['label'=>'Total Expenses',  'value'=>$total_expenses,       'icon'=>'bi-arrow-down-circle','color'=>'#ef4444', 'bg'=>'#fef2f2'],
        ['label'=>'Salary Paid',     'value'=>$salary_paid,          'icon'=>'bi-wallet2',          'color'=>'#8b5cf6', 'bg'=>'#f5f3ff'],
        ['label'=>'Other Income',    'value'=>$other_income,         'icon'=>'bi-arrow-up-circle',  'color'=>'#3b82f6', 'bg'=>'#eff6ff'],
        ['label'=>'Net Balance',     'value'=>$net_balance,          'icon'=>'bi-bar-chart-line',   'color'=>$net_balance>=0?'#22c55e':'#ef4444', 'bg'=>$net_balance>=0?'#f0fdf4':'#fef2f2'],
    ];
    @endphp
    @foreach($cards as $card)
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div style="width:40px;height:40px;background:{{ $card['bg'] }};border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi {{ $card['icon'] }}" style="font-size:18px;color:{{ $card['color'] }};"></i>
                    </div>
                </div>
                <div style="font-size:11px;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:.5px;">{{ $card['label'] }}</div>
                <div style="font-size:18px;font-weight:700;color:{{ $card['color'] }};">ETB {{ number_format($card['value'],2) }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    {{-- Monthly Chart --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header border-0 bg-white pt-3 pb-0 px-4">
                <h6 class="mb-0 font-weight-bold">Monthly Collection vs Expenses</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <canvas id="monthlyChart" height="90"></canvas>
            </div>
        </div>
    </div>

    {{-- Fee Status Donut --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-header border-0 bg-white pt-3 pb-0 px-4">
                <h6 class="mb-0 font-weight-bold">Invoice Status</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="statusChart" height="180"></canvas>
                <div class="d-flex gap-3 mt-3" style="font-size:12px;">
                    <span><span style="display:inline-block;width:10px;height:10px;background:#22c55e;border-radius:50%;margin-right:4px;"></span>Paid ({{ $status_counts['paid'] }})</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:50%;margin-right:4px;"></span>Partial ({{ $status_counts['partial'] }})</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:50%;margin-right:4px;"></span>Unpaid ({{ $status_counts['unpaid'] }})</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Payments --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-header border-0 bg-white pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 font-weight-bold">Recent Payments</h6>
        <a href="{{ route('fees.payments') }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead style="background:#f8fafc;">
                    <tr><th class="px-4 py-3">Receipt</th><th>Student</th><th>Fee Type</th><th>Amount</th><th>Method</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($recent_payments as $pmt)
                    <tr>
                        <td class="px-4"><code style="font-size:11px;">{{ $pmt->receipt_no }}</code></td>
                        <td>{{ $pmt->student->name ?? '-' }}</td>
                        <td><span class="badge" style="background:#eff6ff;color:#3b82f6;font-weight:500;">{{ $pmt->invoice->fee_structure->category->name ?? '-' }}</span></td>
                        <td class="text-success font-weight-bold">ETB {{ number_format($pmt->amount,2) }}</td>
                        <td>{{ ucwords(str_replace('_',' ',$pmt->payment_method)) }}</td>
                        <td class="text-muted">{{ $pmt->paid_at ? $pmt->paid_at->format('d M Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Fees Collected', data: @json($monthly_collection), backgroundColor: 'rgba(34,197,94,0.8)', borderRadius: 6 },
            { label: 'Expenses',       data: @json($monthly_expenses),   backgroundColor: 'rgba(239,68,68,0.7)',  borderRadius: 6 }
        ]
    },
    options: { responsive:true, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true, ticks:{callback:v=>'ETB '+v.toLocaleString()}}} }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Paid','Partial','Unpaid'],
        datasets: [{ data: [{{ $status_counts['paid'] }},{{ $status_counts['partial'] }},{{ $status_counts['unpaid'] }}], backgroundColor: ['#22c55e','#f59e0b','#ef4444'], borderWidth: 0 }]
    },
    options: { responsive:true, cutout:'70%', plugins:{legend:{display:false}} }
});
</script>
@endsection
