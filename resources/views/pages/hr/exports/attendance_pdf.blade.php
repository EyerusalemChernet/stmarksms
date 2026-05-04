<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 16px; }
    h2   { font-size: 14px; margin: 0 0 2px; }
    p    { margin: 0 0 10px; font-size: 9px; color: #666; }
    .summary { display: table; width: 100%; margin-bottom: 12px; }
    .sum-box { display: table-cell; text-align: center; padding: 6px; border: 1px solid #ddd; width: 16%; }
    .sum-box .num { font-size: 18px; font-weight: bold; }
    .sum-box .lbl { font-size: 8px; color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th   { background: #2c3e50; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
    td   { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
    .badge-present  { background: #28a745; color: #fff; }
    .badge-absent   { background: #dc3545; color: #fff; }
    .badge-late     { background: #ffc107; color: #333; }
    .badge-leave    { background: #17a2b8; color: #fff; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Attendance Report</h2>
<p>
    Employee: <strong>{{ $employee->full_name }}</strong> ({{ $employee->employee_code }})
    &nbsp;|&nbsp; Month: <strong>{{ $month }}</strong>
    &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>

{{-- Summary --}}
<div class="summary">
    <div class="sum-box">
        <div class="num" style="color:#28a745;">{{ $summary['present'] }}</div>
        <div class="lbl">Present</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:#ffc107;">{{ $summary['late'] }}</div>
        <div class="lbl">Late</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:#dc3545;">{{ $summary['absent'] }}</div>
        <div class="lbl">Absent</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:#17a2b8;">{{ $summary['leave'] }}</div>
        <div class="lbl">Leave</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:#6c757d;">{{ $summary['total_actual_hours'] ?? 0 }}h</div>
        <div class="lbl">Hours Worked</div>
    </div>
    <div class="sum-box">
        <div class="num" style="color:{{ $summary['attendance_rate'] >= 75 ? '#28a745' : '#dc3545' }};">
            {{ $summary['attendance_rate'] }}%
        </div>
        <div class="lbl">Rate</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Status</th>
            <th>Leave Type</th>
            <th>Sign In</th>
            <th>Sign Off</th>
            <th>Hours</th>
            <th>Overtime</th>
            <th>Late (min)</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @forelse($allRecords as $r)
        <tr>
            <td>{{ $r->date }}</td>
            <td>{{ \Carbon\Carbon::parse($r->date)->format('D') }}</td>
            <td><span class="badge badge-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
            <td>{{ ($r->status === 'leave' && $r->leave_type) ? $r->leaveTypeLabel() : '—' }}</td>
            <td>{{ $r->sign_in_time  ?? '—' }}</td>
            <td>{{ $r->sign_off_time ?? '—' }}</td>
            <td>{{ $r->actual_hours  ? $r->actual_hours.'h' : '—' }}</td>
            <td>{{ $r->overtime_hours > 0 ? '+'.$r->overtime_hours.'h' : '—' }}</td>
            <td>{{ $r->late_minutes  > 0 ? $r->late_minutes.'m' : '—' }}</td>
            <td>{{ $r->remark ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;color:#999;">No records for {{ $month }}.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
