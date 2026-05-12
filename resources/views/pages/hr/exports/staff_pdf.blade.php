<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 16px; }
    h2   { font-size: 14px; margin: 0 0 4px; }
    p    { margin: 0 0 10px; font-size: 9px; color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th   { background: #2c3e50; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
    td   { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; font-size: 9px; vertical-align: middle; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
    .badge-active      { background: #28a745; color: #fff; }
    .badge-on_leave    { background: #ffc107; color: #333; }
    .badge-suspended   { background: #dc3545; color: #fff; }
    .badge-terminated  { background: #343a40; color: #fff; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Employee Report</h2>
<p>
    Status: <strong>{{ ucwords(str_replace('_',' ',$status)) }}</strong>
    @if($search) &nbsp;|&nbsp; Search: <strong>{{ $search }}</strong> @endif
    &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
    &nbsp;|&nbsp; Total: {{ $employees->count() }}
</p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Code</th>
            <th>Full Name</th>
            <th>Gender</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Department</th>
            <th>Position</th>
            <th>Type</th>
            <th>Status</th>
            <th>Salary</th>
            <th>Hire Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($employees as $i => $emp)
        @php
            $ed  = $emp->employmentDetails;
            $sal = $emp->currentSalary;
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $emp->employee_code }}</td>
            <td><strong>{{ $emp->full_name }}</strong></td>
            <td>{{ ucfirst($emp->gender ?? '—') }}</td>
            <td>{{ $emp->email ?? '—' }}</td>
            <td>{{ $emp->phone ?? '—' }}</td>
            <td>{{ $ed?->department?->name ?? '—' }}</td>
            <td>{{ $ed?->position?->name   ?? '—' }}</td>
            <td>{{ $ed ? $ed->employmentTypeLabel() : '—' }}</td>
            <td>
                <span class="badge badge-{{ $emp->status }}">
                    {{ ucwords(str_replace('_',' ',$emp->status)) }}
                </span>
            </td>
            <td>
                @if($sal)
                    {{ $sal->currency }} {{ number_format($sal->amount, 2) }}
                @else
                    —
                @endif
            </td>
            <td>{{ $ed?->hire_date ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="12" style="text-align:center;color:#999;">No employees found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
