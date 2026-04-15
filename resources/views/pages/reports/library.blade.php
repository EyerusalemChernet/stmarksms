@extends('layouts.master')
@section('page_title', 'Library Reports')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-bookshelf mr-2"></i>Library Reports</h5>
    <div>
        <a href="{{ route('reports.library', ['export'=>'pdf']) }}" class="btn btn-sm btn-danger mr-1"><i class="bi bi-file-pdf mr-1"></i>PDF</a>
        <a href="{{ route('reports.library', ['export'=>'csv']) }}" class="btn btn-sm btn-success mr-1"><i class="bi bi-file-spreadsheet mr-1"></i>CSV</a>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Most Borrowed Books</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>#</th><th>Book</th><th>Times</th></tr></thead>
                    <tbody>
                        @forelse($most_borrowed as $i => $br)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $br->book->name ?? '—' }}</td>
                            <td><span class="badge badge-primary">{{ $br->borrow_count }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Overdue Books</h6>
                @if($overdue->count())
                    <span class="badge badge-danger">{{ $overdue->count() }} overdue</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Book</th><th>Borrower</th><th>Issued</th><th>Days</th></tr></thead>
                    <tbody>
                        @forelse($overdue as $br)
                        <tr>
                            <td>{{ $br->book->name ?? '—' }}</td>
                            <td>{{ $br->user->name ?? '—' }}</td>
                            <td>{{ $br->issued_at ? \Carbon\Carbon::parse($br->issued_at)->format('d M Y') : '—' }}</td>
                            <td>
                                @if($br->issued_at)
                                    @php $days = \Carbon\Carbon::parse($br->issued_at)->diffInDays(now()); @endphp
                                    <span class="badge badge-danger">{{ $days }}d</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-muted text-center">No overdue books.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Recent Borrowing History</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Book</th><th>Borrower</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($history as $br)
                        <tr>
                            <td>{{ $br->book->name ?? '—' }}</td>
                            <td>{{ $br->user->name ?? '—' }}</td>
                            <td>
                                <span class="badge badge-{{ $br->status === 'returned' ? 'success' : 'warning' }}">{{ ucfirst($br->status) }}</span>
                            </td>
                            <td class="small text-muted">{{ $br->updated_at->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-muted text-center">No history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
