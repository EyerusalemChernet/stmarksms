<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; margin: 0; padding: 16px; }
    h2   { font-size: 14px; margin: 0 0 4px; }
    p    { margin: 0 0 10px; font-size: 9px; color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th   { background: #2c3e50; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; }
    td   { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; font-size: 8px; text-align: center; }
    td:first-child, td:nth-child(2), td:nth-child(3) { text-align: left; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .avail { font-weight: bold; color: #28a745; }
    .zero  { font-weight: bold; color: #dc3545; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Leave Balances {{ $year }}</h2>
<p>
    @if($search) Search: <strong>{{ $search }}</strong> &nbsp;|&nbsp; @endif
    Total Employees: {{ $employees->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<p style="font-size:8px;color:#888;">Format: available / entitled days</p>
<table>
    <thead>
        <tr>
            <th>#</th><th>Employee</th><th>Department</th>
            <th>Annual</th><th>Sick</th><th>Maternity</th><th>Paternity</th><th>Unpaid</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $i => $emp)
        @php $b = $allBalances->get($emp->id, collect()); @endphp
        <tr>
            <td style="text-align:left;">{{ $i+1 }}</td>
            <td><strong>{{ $emp->full_name }}</strong></td>
            <td>{{ $emp->employmentDetails?->department?->name ?? '—' }}</td>
            @foreach(['annual','sick','maternity','paternity','unpaid'] as $type)
            @php $bal = $b->get($type); @endphp
            <td>
                @if($bal)
                    <span class="{{ $bal->available > 0 ? 'avail' : 'zero' }}">{{ $bal->available }}</span>
                    <span style="color:#999;">/ {{ $bal->entitled }}</span>
                @else
                    <span style="color:#ccc;">—</span>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
