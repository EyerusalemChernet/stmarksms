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
<h2>Library Report</h2>
<p class="sub">Generated: {{ now()->format('d M Y H:i') }}</p>

<h4>Most Borrowed Books</h4>
<table>
    <thead><tr><th>#</th><th>Book</th><th>Times Borrowed</th></tr></thead>
    <tbody>
        @foreach($mostBorrowed as $i => $br)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $br->book->name ?? '—' }}</td>
            <td>{{ $br->borrow_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h4 style="margin-top:20px;">Overdue Books ({{ $overdue->count() }})</h4>
<table>
    <thead><tr><th>Book</th><th>Borrower</th><th>Issued</th></tr></thead>
    <tbody>
        @forelse($overdue as $br)
        <tr>
            <td>{{ $br->book->name ?? '—' }}</td>
            <td>{{ $br->user->name ?? '—' }}</td>
            <td>{{ $br->issued_at ? \Carbon\Carbon::parse($br->issued_at)->format('d M Y') : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No overdue books.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
