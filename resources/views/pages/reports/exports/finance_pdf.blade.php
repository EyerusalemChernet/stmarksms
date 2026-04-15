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
.summary { display: flex; gap: 20px; margin-top: 12px; }
.summary-box { border: 1px solid #e5e7eb; padding: 8px 16px; border-radius: 6px; text-align: center; }
</style>
</head>
<body>
<h2>Finance Report — {{ $year }}</h2>
<p class="sub">Generated: {{ now()->format('d M Y H:i') }}</p>

<table style="margin-top:12px;">
    <tr><td><strong>Total Collected</strong></td><td>ETB {{ number_format($total_collected) }}</td></tr>
    <tr><td><strong>Outstanding Balance</strong></td><td>ETB {{ number_format($total_outstanding) }}</td></tr>
    <tr><td><strong>Students Fully Paid</strong></td><td>{{ $students_paid }}</td></tr>
    <tr><td><strong>Students With Balance</strong></td><td>{{ $students_unpaid }}</td></tr>
</table>

<table style="margin-top:20px;">
    <thead><tr><th>Class</th><th>Amount Collected</th><th>Unpaid Records</th></tr></thead>
    <tbody>
        @foreach($classes as $c)
        <tr>
            <td>{{ $c->name }}</td>
            <td>ETB {{ number_format($c->paid_amount) }}</td>
            <td>{{ $c->unpaid_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
