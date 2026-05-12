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
    td   { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; font-size: 8px; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
    .text-center { text-align: center; }
    .text-success { color: #28a745; }
    .text-danger  { color: #dc3545; }
</style>
</head>
<body>
<h2>St. Mark School — Attendance Summary</h2>
<p>
    Month: <strong>{{ $month }}</strong>
    @if($search) &nbsp;|&nbsp; Search: <strong>{{ $search }}</strong> @endif
    &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr>
            <th>#</th><th>Employee</th><th>Code</th><th>Department</th>
            <th class="text-center">Present</th><th class="text-center">Late</th>
            <th class="text-center">Absent</th><th class="text-center">Leave</th>
            <th class="text-center">Rate %</th><th class="text-center">Hours</th><th class="text-center">OT (h)</th>
        </tr>
    </thead>
    <tbody>
        @php $row = 0; @endphp
        @foreach($employees as $emp)
        @php $s = $monthlySummary->get($emp->id); if(!$s) continue; $row++; @endphp
        <tr>
            <td>{{ $row }}</td>
            <td><strong>{{ $emp->full_name }}</strong></td>
            <td>{{ $emp->employee_code }}</td>
            <td>{{ $emp->employmentDetails?->department?->name ?? '—' }}</td>
            <td class="text-center">{{ $s['present'] }}</td>
            <td class="text-center">{{ $s['late'] }}</td>
            <td class="text-center">{{ $s['absent'] }}</td>
            <td class="text-center">{{ $s['leave'] }}</td>
            <td class="text-center {{ $s['attendance_rate'] >= 75 ? 'text-success' : 'text-danger' }}">
                <strong>{{ $s['attendance_rate'] }}%</strong>
            </td>
            <td class="text-center">{{ $s['actual_hours'] ?? 0 }}h</td>
            <td class="text-center">{{ $s['overtime_hours'] ?? 0 }}h</td>
        </tr>
        @endforeach
        @if($row === 0)
        <tr><td colspan="11" style="text-align:center;color:#999;">No attendance records for {{ $month }}.</td></tr>
        @endif
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
