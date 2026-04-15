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
</style>
</head>
<body>
<h2>Attendance Report — {{ $year }}</h2>
<p class="sub">Generated: {{ now()->format('d M Y H:i') }} | Total Sessions: {{ $total_sessions }}</p>
<table>
    <thead><tr><th>Class</th><th>Total Records</th><th>Present</th><th>Attendance %</th></tr></thead>
    <tbody>
        @foreach($classes as $c)
        <tr>
            <td>{{ $c->name }}</td>
            <td>{{ $c->att_total }}</td>
            <td>{{ $c->att_present }}</td>
            <td>{{ $c->att_pct }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
