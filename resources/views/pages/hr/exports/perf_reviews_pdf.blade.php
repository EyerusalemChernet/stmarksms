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
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
    .badge-success { background: #28a745; color: #fff; }
    .badge-info    { background: #17a2b8; color: #fff; }
    .badge-warning { background: #ffc107; color: #333; }
    .badge-danger  { background: #dc3545; color: #fff; }
    .badge-secondary { background: #6c757d; color: #fff; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Performance Reviews</h2>
<p>
    Period: <strong>{{ $period ?: 'All' }}</strong>
    @if($search) &nbsp;|&nbsp; Search: <strong>{{ $search }}</strong> @endif
    &nbsp;|&nbsp; Total: {{ $reviews->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr>
            <th>#</th><th>Employee</th><th>Code</th><th>Period</th>
            <th class="text-center">Score / 10</th><th class="text-center">Grade</th>
            <th>Reviewer</th><th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reviews as $i => $r)
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $r->employee->full_name }}</strong></td>
            <td>{{ $r->employee->employee_code }}</td>
            <td>{{ $r->period }}</td>
            <td style="text-align:center;"><strong>{{ number_format($r->overall_score, 2) }}</strong></td>
            <td style="text-align:center;">
                <span class="badge badge-{{ $r->gradeBadgeClass() }}">{{ $r->gradeLabel() }}</span>
            </td>
            <td>{{ $r->reviewer?->name ?? '—' }}</td>
            <td>{{ $r->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#999;">No reviews found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
