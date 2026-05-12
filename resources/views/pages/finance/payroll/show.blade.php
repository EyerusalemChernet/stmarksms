@extends('layouts.master')
@section('page_title','Payslip')
@section('content')
@php $months = ['','January','February','March','April','May','June','July','August','September','October','November','December']; @endphp
<div class="d-flex justify-content-end mb-3 d-print-none">
  <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer mr-1"></i>Print</button>
  <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm ml-2"><i class="bi bi-arrow-left mr-1"></i>Back</a>
  @if(!$payroll->voided)
  <form action="{{ route('payroll.void',$payroll->id) }}" method="POST" class="d-inline ml-2" onsubmit="return confirm('Void this payroll?')">@csrf
    <button class="btn btn-danger btn-sm"><i class="bi bi-x-circle mr-1"></i>Void</button>
  </form>
  @endif
</div>

<div class="card mx-auto" style="max-width:650px;" id="payslip-card">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <h5 style="font-weight:800;color:#1e293b;">{{ \App\Helpers\Qs::getSetting('system_name') ?? config('app.name') }}</h5>
      <div style="margin:10px 0;border-top:2px solid #1e293b;border-bottom:2px solid #1e293b;padding:4px 0;">
        <strong style="font-size:15px;letter-spacing:2px;">PAYSLIP</strong>
      </div>
      <div style="font-size:13px;color:#64748b;">{{ $months[$payroll->month] }} {{ $payroll->year }}</div>
    </div>

    <div class="row mb-3">
      <div class="col-6">
        <table style="font-size:13px;width:100%">
          <tr><td style="color:#64748b;padding:2px 0">Staff</td><td><strong>{{ $payroll->staff->name ?? '-' }}</strong></td></tr>
          <tr><td style="color:#64748b;padding:2px 0">Role</td><td>{{ ucfirst(str_replace('_',' ',$payroll->staff->user_type ?? '')) }}</td></tr>
          <tr><td style="color:#64748b;padding:2px 0">Processed</td><td>{{ $payroll->processed_at ? $payroll->processed_at->format('d M Y') : '-' }}</td></tr>
        </table>
      </div>
      <div class="col-6 text-right">
        @if($payroll->voided)<span class="badge badge-danger" style="font-size:14px;">VOIDED</span>@endif
      </div>
    </div>

    <table class="table table-sm" style="font-size:13px;">
      <thead style="background:#f1f5f9;"><tr><th>Description</th><th class="text-right">Amount (ETB)</th></tr></thead>
      <tbody>
        <tr><td>Basic Salary</td><td class="text-right">{{ number_format($payroll->basic_salary,2) }}</td></tr>
        <tr><td>Housing Allowance</td><td class="text-right">{{ number_format($payroll->housing_allowance,2) }}</td></tr>
        <tr><td>Transport Allowance</td><td class="text-right">{{ number_format($payroll->transport_allowance,2) }}</td></tr>
        <tr><td>Other Allowances</td><td class="text-right">{{ number_format($payroll->other_allowances,2) }}</td></tr>
        @if($payroll->bonus > 0)<tr><td>Bonus</td><td class="text-right">{{ number_format($payroll->bonus,2) }}</td></tr>@endif
        <tr style="border-top:1px solid #cbd5e1"><td><strong>Gross Salary</strong></td><td class="text-right"><strong>{{ number_format($payroll->gross_salary,2) }}</strong></td></tr>
        <tr><td class="text-danger">Income Tax</td><td class="text-right text-danger">- {{ number_format($payroll->income_tax,2) }}</td></tr>
        <tr><td class="text-danger">Loan Repayment</td><td class="text-right text-danger">- {{ number_format($payroll->loan_repayment,2) }}</td></tr>
        @if($payroll->absence_deduction > 0)<tr><td class="text-danger">Absence Deduction ({{ $payroll->absence_days }} days)</td><td class="text-right text-danger">- {{ number_format($payroll->absence_deduction,2) }}</td></tr>@endif
        <tr style="border-top:1px solid #cbd5e1"><td><strong>Total Deductions</strong></td><td class="text-right text-danger"><strong>- {{ number_format($payroll->total_deductions,2) }}</strong></td></tr>
      </tbody>
    </table>

    <div style="background:#f0fdf4;border-radius:8px;padding:16px;margin-top:8px;text-align:center;">
      <div style="font-size:12px;color:#64748b;">NET SALARY</div>
      <div style="font-size:28px;font-weight:800;color:#22c55e;">ETB {{ number_format($payroll->net_salary,2) }}</div>
    </div>

    <div class="text-center mt-4 pt-3" style="border-top:1px dashed #cbd5e1;font-size:11px;color:#94a3b8;">
      Computer-generated payslip. No signature required.
    </div>
  </div>
</div>
@endsection
@section('scripts')
<style>
@media print {
  .d-print-none { display:none !important; }
  .sidebar-main, .navbar { display:none !important; }
  #payslip-card { box-shadow:none !important; border:none !important; max-width:100% !important; }
  body { background:white !important; }
}
</style>
@endsection
