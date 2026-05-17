<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #333; margin: 0; padding: 16px; }
    h2   { font-size: 13px; margin: 0 0 4px; }
    p    { margin: 0 0 10px; font-size: 8px; color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th   { background: #2c3e50; color: #fff; padding: 4px 5px; text-align: left; font-size: 7px; }
    td   { padding: 3px 5px; border-bottom: 1px solid #e0e0e0; font-size: 7px; }
    tr:nth-child(even) td { background: #f8f9fa; }
    .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 6px; font-weight: bold; }
    .badge-completed  { background: #28a745; color: #fff; }
    .badge-enrolled   { background: #6c757d; color: #fff; }
    .badge-in_progress{ background: #17a2b8; color: #fff; }
    .badge-failed     { background: #dc3545; color: #fff; }
    .badge-cancelled  { background: #343a40; color: #fff; }
    .footer { margin-top: 12px; font-size: 7px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Training Enrollments</h2>
<p>
    Status: <strong>{{ ucfirst($status) }}</strong>
    @if($search) &nbsp;|&nbsp; Search: <strong>{{ $search }}</strong> @endif
    &nbsp;|&nbsp; Total: {{ $enrollments->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr><th>#</th><th>Employee</th><th>Code</th><th>Program</th><th>Category</th><th>Start</th><th>End</th><th>Status</th><th>Score</th><th>Certificate</th></tr>
    </thead>
    <tbody>
        @forelse($enrollments as $i => $e)
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $e->employee->full_name }}</strong></td>
            <td>{{ $e->employee->employee_code }}</td>
            <td>{{ $e->program->title }}</td>
            <td>{{ $e->program->categoryLabel() }}</td>
            <td>{{ $e->start_date?->format('d M Y') ?? '—' }}</td>
            <td>{{ $e->end_date?->format('d M Y') ?? '—' }}</td>
            <td><span class="badge badge-{{ $e->status }}">{{ $e->statusLabel() }}</span></td>
            <td>{{ $e->score !== null ? $e->score.'%' : '—' }}</td>
            <td>{{ $e->certificate_number ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;color:#999;">No enrollments found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
