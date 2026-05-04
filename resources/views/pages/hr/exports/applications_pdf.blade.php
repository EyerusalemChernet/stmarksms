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
    .badge-applied     { background: #6c757d; color: #fff; }
    .badge-shortlisted { background: #17a2b8; color: #fff; }
    .badge-interviewed { background: #ffc107; color: #333; }
    .badge-hired       { background: #28a745; color: #fff; }
    .badge-rejected    { background: #dc3545; color: #fff; }
    .footer { margin-top: 12px; font-size: 8px; color: #999; text-align: right; }
</style>
</head>
<body>
<h2>St. Mark School — Job Applications</h2>
<p>
    Status: <strong>{{ ucfirst($status) }}</strong>
    @if($search) &nbsp;|&nbsp; Search: <strong>{{ $search }}</strong> @endif
    &nbsp;|&nbsp; Total: {{ $applications->count() }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
</p>
<table>
    <thead>
        <tr>
            <th>#</th><th>Applicant</th><th>Email / Phone</th><th>Job Posting</th>
            <th>Applied</th><th>Interview</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($applications as $i => $a)
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $a->full_name }}</strong></td>
            <td>{{ $a->email ?? $a->phone ?? '—' }}</td>
            <td>{{ $a->jobPosting?->title ?? '—' }}</td>
            <td>{{ $a->applied_at->format('d M Y') }}</td>
            <td>{{ $a->interview_date?->format('d M Y') ?? '—' }}</td>
            <td><span class="badge badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:#999;">No applications found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer">Printed by {{ auth()->user()->name ?? 'System' }} on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
