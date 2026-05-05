@extends('layouts.master')
@section('page_title', 'Finance Reports')
@section('content')

{{-- Summary Cards --}}
<div class="row mb-4">
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #22c55e !important;">
            <div class="card-body">
                <div class="text-muted" style="font-size:12px;">GROSS INCOME</div>
                <div style="font-size:24px;font-weight:700;color:#22c55e;">ETB {{ number_format($gross_income,2) }}</div>
                <div style="font-size:11px;color:#94a3b8;">Fees + Transport + Other Income</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ef4444 !important;">
            <div class="card-body">
                <div class="text-muted" style="font-size:12px;">GROSS EXPENSES</div>
                <div style="font-size:24px;font-weight:700;color:#ef4444;">ETB {{ number_format($gross_expense,2) }}</div>
                <div style="font-size:11px;color:#94a3b8;">Expenses + Payroll</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid {{ $net_balance >= 0 ? '#3b82f6' : '#f97316' }} !important;">
            <div class="card-body">
                <div class="text-muted" style="font-size:12px;">NET BALANCE</div>
                <div style="font-size:24px;font-weight:700;color:{{ $net_balance >= 0 ? '#3b82f6' : '#f97316' }};">ETB {{ number_format($net_balance,2) }}</div>
                <div style="font-size:11px;color:#94a3b8;">{{ $net_balance >= 0 ? 'Surplus' : 'Deficit' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <i class="bi bi-mortarboard text-primary" style="font-size:28px;"></i>
                <div class="text-muted mt-1" style="font-size:11px;">STUDENT FEES</div>
                <div style="font-weight:700;">ETB {{ number_format($total_student_fees,2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <i class="bi bi-bus-front text-info" style="font-size:28px;"></i>
                <div class="text-muted mt-1" style="font-size:11px;">TRANSPORT FEES</div>
                <div style="font-weight:700;">ETB {{ number_format($total_transport_fees,2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <i class="bi bi-wallet2 text-warning" style="font-size:28px;"></i>
                <div class="text-muted mt-1" style="font-size:11px;">PAYROLL PAID</div>
                <div style="font-weight:700;">ETB {{ number_format($total_payroll,2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <i class="bi bi-arrow-up-circle text-success" style="font-size:28px;"></i>
                <div class="text-muted mt-1" style="font-size:11px;">OTHER INCOME</div>
                <div style="font-weight:700;">ETB {{ number_format($total_income,2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Chart --}}
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-bar-chart-line mr-2"></i>Monthly Income vs Expenses ({{ $year }})</h6></div>
    <div class="card-body">
        <canvas id="financeChart" height="80"></canvas>
    </div>
</div>

{{-- Recent Transactions --}}
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 text-success"><i class="bi bi-arrow-up-circle mr-2"></i>Recent Income</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($recent_incomes as $inc)
                        <tr>
                            <td>{{ $inc->title }}</td>
                            <td>{{ $inc->category->name ?? '-' }}</td>
                            <td class="text-success">{{ number_format($inc->amount,2) }}</td>
                            <td>{{ $inc->income_date }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 text-danger"><i class="bi bi-arrow-down-circle mr-2"></i>Recent Expenses</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($recent_expenses as $exp)
                        <tr>
                            <td>{{ $exp->title }}</td>
                            <td>{{ $exp->category->name ?? '-' }}</td>
                            <td class="text-danger">{{ number_format($exp->amount,2) }}</td>
                            <td>{{ $exp->expense_date }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
new Chart(document.getElementById('financeChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Income', data: @json($monthly_income), backgroundColor: 'rgba(34,197,94,0.7)', borderRadius: 4 },
            { label: 'Expenses', data: @json($monthly_expenses), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 4 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endsection
