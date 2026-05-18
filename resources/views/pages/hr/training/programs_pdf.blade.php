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
</style>
</head>
<body>
<h2>St. Mark School — Training Programs</h2>
<p>
    @if($search) Search: <strong>{{ $search }}</strong> &nbsp;|&nbsp; @endif
    Total: {{ $programs->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr><th>#</th><th>Title</th><th>Category</th><th>Provider</th><th>Hours</th><th>Cost</th><th>Mandatory</th><th>Enrolled</th><th>Completed</th></tr>
    </thead>
    <tbody>
        @forelse($programs as $i => $p)
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $p->title }}</strong></td>
            <td>{{ $p->categoryLabel() }}</td>
            <td>{{ $p->provider ?? '—' }}</td>
            <td>{{ $p->duration_hours ? $p->duration_hours.'h' : '—' }}</td>
            <td>{{ $p->cost ? 'ETB '.number_format($p->cost,2) : '—' }}</td>
            <td>{{ $p->is_mandatory ? 'Yes' : 'No' }}</td>
            <td style="text-align:center;">{{ $p->enrollments_count }}</td>
            <td style="text-align:center;">{{ $p->completed_count }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;color:#999;">No programs found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
