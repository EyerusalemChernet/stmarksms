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
    .badge-open    { background: #28a745; color: #fff; }
    .badge-on_hold { background: #ffc107; color: #333; }
    .badge-closed  { background: #6c757d; color: #fff; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Job Postings</h2>
<p>
    @if($search) Search: <strong>{{ $search }}</strong> &nbsp;|&nbsp; @endif
    Total: {{ $postings->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr>
            <th>#</th><th>Title</th><th>Department</th><th>Type</th>
            <th class="text-center">Vacancies</th><th class="text-center">Applications</th>
            <th>Deadline</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($postings as $i => $p)
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $p->title }}</strong></td>
            <td>{{ $p->department?->name ?? '—' }}</td>
            <td>{{ ucwords(str_replace('_',' ',$p->employment_type)) }}</td>
            <td style="text-align:center;">{{ $p->vacancies }}</td>
            <td style="text-align:center;">{{ $p->applications_count }}</td>
            <td>{{ $p->deadline?->format('d M Y') ?? '—' }}</td>
            <td><span class="badge badge-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#999;">No job postings found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
