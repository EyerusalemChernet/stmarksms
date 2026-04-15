@extends('layouts.master')
@section('page_title', 'Reports & Analytics')
@section('breadcrumb')
    <span class="breadcrumb-item active">Reports</span>
@endsection
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-bar-chart-line mr-2"></i>Reports & Analytics — {{ $year }}</h5>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <a href="{{ route('reports.students') }}" class="card card-body text-center text-decoration-none h-100" style="border-left:4px solid #2563eb;">
            <i class="bi bi-people-fill" style="font-size:2.5rem;color:#2563eb;"></i>
            <h5 class="mt-2 mb-1">Student Reports</h5>
            <p class="text-muted small mb-0">Students per class, gender breakdown, promotion statistics</p>
            <div class="mt-2">
                <span class="badge badge-light text-primary">PDF</span>
                <span class="badge badge-light text-success">CSV</span>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('reports.attendance') }}" class="card card-body text-center text-decoration-none h-100" style="border-left:4px solid #16a34a;">
            <i class="bi bi-clipboard-check-fill" style="font-size:2.5rem;color:#16a34a;"></i>
            <h5 class="mt-2 mb-1">Attendance Reports</h5>
            <p class="text-muted small mb-0">Attendance rates per class, session summaries</p>
            <div class="mt-2">
                <span class="badge badge-light text-primary">PDF</span>
                <span class="badge badge-light text-success">CSV</span>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="{{ route('reports.academic') }}" class="card card-body text-center text-decoration-none h-100" style="border-left:4px solid #d97706;">
            <i class="bi bi-journal-check" style="font-size:2.5rem;color:#d97706;"></i>
            <h5 class="mt-2 mb-1">Academic Reports</h5>
            <p class="text-muted small mb-0">Exam performance, class averages, top students</p>
            <div class="mt-2">
                <span class="badge badge-light text-primary">PDF</span>
                <span class="badge badge-light text-success">CSV</span>
            </div>
        </a>
    </div>
    @if(Qs::userIsTeamAccount())
    <div class="col-md-4 mb-3">
        <a href="{{ route('reports.finance') }}" class="card card-body text-center text-decoration-none h-100" style="border-left:4px solid #dc2626;">
            <i class="bi bi-cash-stack" style="font-size:2.5rem;color:#dc2626;"></i>
            <h5 class="mt-2 mb-1">Finance Reports</h5>
            <p class="text-muted small mb-0">Fees collected, outstanding balances, per-class payments</p>
            <div class="mt-2">
                <span class="badge badge-light text-primary">PDF</span>
                <span class="badge badge-light text-success">CSV</span>
            </div>
        </a>
    </div>
    @endif
    <div class="col-md-4 mb-3">
        <a href="{{ route('reports.library') }}" class="card card-body text-center text-decoration-none h-100" style="border-left:4px solid #0891b2;">
            <i class="bi bi-bookshelf" style="font-size:2.5rem;color:#0891b2;"></i>
            <h5 class="mt-2 mb-1">Library Reports</h5>
            <p class="text-muted small mb-0">Most borrowed books, overdue items, borrowing history</p>
            <div class="mt-2">
                <span class="badge badge-light text-primary">PDF</span>
                <span class="badge badge-light text-success">CSV</span>
            </div>
        </a>
    </div>
</div>
@endsection
