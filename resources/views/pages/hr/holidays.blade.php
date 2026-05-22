@extends('layouts.master')
@section('page_title', 'Ethiopian Public Holidays')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-calendar-event mr-2"></i>Ethiopian Public Holidays</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left mr-1"></i>Dashboard
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle mr-1"></i>
    Holidays are excluded from attendance working-day counts and payroll absence deductions.
    Use <strong>Auto-Seed</strong> to populate standard Ethiopian public holidays, then add or remove as needed.
</div>

<div class="row">

    {{-- ── Left: Year selector + Add form ──────────────────────────────────── --}}
    <div class="col-md-4">

        {{-- Year selector --}}
        <div class="card mb-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Select Year</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.holidays') }}" method="GET" class="form-inline mb-3">
                    <input type="number" name="year" value="{{ $year }}" min="2020" max="2099"
                           class="form-control form-control-sm mr-2" style="width:90px;">
                    <button type="submit" class="btn btn-sm btn-primary">View</button>
                </form>

                {{-- Auto-seed --}}
                <form action="{{ route('hr.holidays.seed') }}" method="POST">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <button type="submit" class="btn btn-sm btn-success btn-block"
                            onclick="return confirm('Auto-seed all standard Ethiopian holidays for {{ $year }}?\nExisting holidays will not be duplicated.')">
                        <i class="bi bi-magic mr-1"></i>Auto-Seed {{ $year }} Holidays
                    </button>
                </form>
                <small class="text-muted d-block mt-1">
                    Seeds: Genna, Timkat, Adwa, Good Friday, Fasika, Labour Day,
                    Patriots' Day, Derg Downfall, Eid al-Fitr, Eid al-Adha,
                    Enkutatash, Meskel, Mawlid.
                </small>
            </div>
        </div>

        {{-- Add custom holiday --}}
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add Holiday</h6></div>
            <div class="card-body">
                <form action="{{ route('hr.holidays.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold small">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="{{ old('date', $year.'-01-01') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="{{ old('name') }}" placeholder="e.g. School Founding Day" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Type</label>
                        <select name="type" class="form-control form-control-sm">
                            <option value="public">Public (National)</option>
                            <option value="religious">Religious</option>
                            <option value="school">School-specific</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Notes</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                               value="{{ old('notes') }}" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary btn-block">
                        <i class="bi bi-plus-circle mr-1"></i>Add Holiday
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Right: Holiday list ───────────────────────────────────────────────── --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    Holidays for {{ $year }}
                    <span class="badge badge-secondary ml-1">{{ $holidays->count() }}</span>
                </h6>
                @if($holidays->count() > 0)
                <small class="text-muted">{{ $holidays->count() }} day(s) excluded from working days</small>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $h)
                        <tr class="{{ $h->date->isPast() ? 'text-muted' : '' }}">
                            <td class="font-weight-bold">{{ $h->date->format('d M Y') }}</td>
                            <td class="text-muted small">{{ $h->date->format('l') }}</td>
                            <td>{{ $h->name }}</td>
                            <td>
                                <span class="badge badge-{{ $h->typeBadgeClass() }}">
                                    {{ ucfirst($h->type) }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('hr.holidays.destroy', $h->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger"
                                            onclick="return confirm('Remove {{ $h->name }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No holidays for {{ $year }}.
                                Click <strong>Auto-Seed {{ $year }} Holidays</strong> to populate.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Preview of calculated holidays (not yet seeded) --}}
        @if($holidays->count() === 0)
        <div class="card mt-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0 text-muted">
                    <i class="bi bi-eye mr-1"></i>Preview — Calculated Holidays for {{ $year }}
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Date</th><th>Day</th><th>Name</th><th>Type</th></tr>
                    </thead>
                    <tbody>
                        @foreach($preview as $p)
                        <tr>
                            <td>{{ $p['date']->format('d M Y') }}</td>
                            <td class="text-muted small">{{ $p['date']->format('l') }}</td>
                            <td>{{ $p['name'] }}</td>
                            <td><span class="badge badge-{{ $p['type'] === 'public' ? 'primary' : ($p['type'] === 'religious' ? 'warning' : 'info') }}">{{ ucfirst($p['type']) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
