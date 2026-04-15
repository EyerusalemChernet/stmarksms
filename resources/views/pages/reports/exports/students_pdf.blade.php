<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
h2 { text-align: center; margin-bottom: 4px; }
p.sub { text-align: center; color: #666; margin-top: 0; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th { background: #2563eb; color: #fff; padding: 6px 8px; text-align: left; }
td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
tr:nth-child(even) td { background: #f9fafb; }
.badge { padding: 2px 6px; border-radius: 4px; font-size: 11px; }
.badge-success { background: #16a34a; color: #fff; }
.badge-warning { background: #d97706; color: #fff; }
.badge-info    { background: #0891b2; color: #fff; }
</style>
</head>
<body>
<h2>Student Report — {{ $year }}</h2>
<p class="sub">Generated: {{ now()->format('d M Y H:i') }}</p>

<table>
    <thead><tr><th>Class</th><th>Active Students</th><th>% of Total</th></tr></thead>
    <tbody>
        @foreach($classes as $c)
        <tr>
            <td>{{ $c->name }}</td>
            <td>{{ $c->active_count }}</td>
            <td>{{ $allStudents->count() > 0 ? round(($c->active_count / $allStudents->count()) * 100, 1) : 0 }}%</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;"><td>Total</td><td>{{ $allStudents->count() }}</td><td>100%</td></tr>
    </tbody>
</table>

<table style="margin-top:20px;">
    <thead><tr><th colspan="2">Gender Breakdown</th></tr></thead>
    <tbody>
        <tr><td>Male</td><td>{{ $male }}</td></tr>
        <tr><td>Female</td><td>{{ $female }}</td></tr>
    </tbody>
</table>

<table style="margin-top:20px;">
    <thead><tr><th colspan="2">Promotion Statistics ({{ $year }})</th></tr></thead>
    <tbody>
        <tr><td>Promoted</td><td>{{ $promotions->get('P', 0) }}</td></tr>
        <tr><td>Not Promoted</td><td>{{ $promotions->get('D', 0) }}</td></tr>
        <tr><td>Graduated</td><td>{{ $promotions->get('G', 0) }}</td></tr>
    </tbody>
</table>
</body>
</html>
