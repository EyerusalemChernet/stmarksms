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
<h2>Academic Report — {{ $year }}</h2>
<p class="sub">Generated: {{ now()->format('d M Y H:i') }}</p>
<table>
    <thead><tr><th>Class</th><th>Students</th><th>Average Score</th></tr></thead>
    <tbody>
        @foreach($classes as $c)
        <tr>
            <td>{{ $c->name }}</td>
            <td>{{ $c->student_count }}</td>
            <td>{{ $c->avg_score }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<table style="margin-top:20px;">
    <thead><tr><th>#</th><th>Student</th><th>Average Score</th></tr></thead>
    <tbody>
        @foreach($topStudents as $i => $exr)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $exr->student->name ?? '—' }}</td>
            <td>{{ round($exr->overall_avg, 1) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
