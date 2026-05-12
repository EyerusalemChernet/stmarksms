<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 16px; }
    h2   { font-size: 14px; margin: 0 0 4px; }
    p    { margin: 0 0 10px; font-size: 9px; color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th   { background: #2c3e50; color: #fff; padding: 5px 8px; text-align: left; font-size: 9px; }
    td   { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Positions</h2>
<p>
    @if($search) Search: <strong>{{ $search }}</strong> &nbsp;|&nbsp; @endif
    Total: {{ $positions->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr><th>#</th><th>Name</th><th>Department</th><th>Description</th><th class="text-center">Employees</th></tr>
    </thead>
    <tbody>
        @forelse($positions as $i => $p)
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $p->name }}</strong></td>
            <td>{{ $p->department?->name ?? 'All Departments' }}</td>
            <td>{{ $p->description ?? '—' }}</td>
            <td style="text-align:center;">{{ $p->employee_count }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:#999;">No positions found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
