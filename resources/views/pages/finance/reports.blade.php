@extends('layouts.master')
@section('page_title', 'Finance Reports')
@section('content')

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    @php
    $summary = [
        ['label'=>'Fees Collected', 'value'=>$total_fees_collected, 'color'=>'#22c55e'],
        ['label'=>'Pending Fees',   'value'=>$total_pending,        'color'=>'#f59e0b'],
        ['label'=>'Other Income',   'value'=>$total_income,         'color'=>'#3b82f6'],
        ['label'=>'Expenses',       'value'=>$total_expenses,       'color'=>'#ef4444'],
        ['label'=>'Salary Paid',    'value'=>$salary_paid,          'color'=>'#8b5cf6'],
        ['label'=>'Net Balance',    'value'=>$net_balance,          'color'=>$net_balance>=0?'#22c55e':'#ef4444'],
    ];
    @endphp
    @foreach($summary as $s)
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
            <div class="card-body py-3">
                <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">{{ $s['label'] }}</div>
                <div style="font-size:16px;font-weight:700;color:{{ $s['color'] }};">ETB {{ number_format($s['value'],2) }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    {{-- Monthly Summary Chart --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header border-0 bg-white pt-3 pb-0 px-4"><h6 class="mb-0 font-weight-bold">Monthly Finance Summary — {{ $session }}</h6></div>
            <div class="card-body px-4 pb-4"><canvas id="monthlyChart" height="100"></canvas></div>
        </div>
    </div>

    {{-- Daily Collection (last 30 days) --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header border-0 bg-white pt-3 pb-0 px-4"><h6 class="mb-0 font-weight-bold">Daily Collection (30 days)</h6></div>
            <div class="card-body px-4 pb-4"><canvas id="dailyChart" height="180"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Payroll Summary --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header border-0 bg-white pt-3 pb-0 px-4"><h6 class="mb-0 font-weight-bold">Payroll Summary</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead style="background:#f8fafc;"><tr><th class="px-4 py-2">Staff</th><th>Months</th><th>Total Salary</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($payroll_summary as $pr)
                        <tr>
                            <td class="px-4">{{ $pr->staff->name ?? '-' }}</td>
                            <td>{{ $pr->months }}</td>
                            <td class="font-weight-bold">ETB {{ number_format($pr->total,2) }}</td>
                            <td><span class="badge badge-{{ $pr->status==='paid'?'success':'warning' }}">{{ ucfirst($pr->status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No payroll data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pending by Class --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header border-0 bg-white pt-3 pb-0 px-4"><h6 class="mb-0 font-weight-bold">Pending Fees by Class</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead style="background:#f8fafc;"><tr><th class="px-4 py-2">Class</th><th>Students</th><th>Pending Amount</th></tr></thead>
                    <tbody>
                        @forelse($pending_by_class as $p)
                        <tr>
                            <td class="px-4">{{ $p->fee_structure->my_class->name ?? '-' }}</td>
                            <td>{{ $p->cnt }}</td>
                            <td class="text-danger font-weight-bold">ETB {{ number_format($p->total,2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No pending fees.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const monthly = @json($monthly);

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Fees',     data: monthly.map(m=>m.fees),     backgroundColor: 'rgba(34,197,94,0.8)',  borderRadius:5 },
            { label: 'Expenses', data: monthly.map(m=>m.expenses), backgroundColor: 'rgba(239,68,68,0.7)',  borderRadius:5 },
            { label: 'Income',   data: monthly.map(m=>m.income),   backgroundColor: 'rgba(59,130,246,0.7)', borderRadius:5 }
        ]
    },
    options: { responsive:true, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true}} }
});

const daily = @json($daily_collection);
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: daily.map(d=>d.day),
        datasets: [{ label:'Daily Collection', data: daily.map(d=>d.total), borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,0.1)', fill:true, tension:0.4, pointRadius:3 }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});
</script>
@endsection
