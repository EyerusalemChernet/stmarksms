@extends('layouts.master')
@section('page_title', 'Term & Semester Setup')
@section('content')

@php
    $totalTerms = $semesters_per_year * $terms_per_semester;
@endphp

<style>
.ts-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.05); margin-bottom:20px; }
.ts-card-header { padding:14px 20px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
.ts-card-body { padding:20px; }
.term-grid { display:grid; gap:14px; }
.term-row { border:1.5px solid #e2e8f0; border-radius:10px; padding:16px 18px; display:flex; align-items:center; gap:16px; transition:border-color .15s; }
.term-row:hover { border-color:#c4b5fd; }
.term-row.has-exam { border-color:#a7f3d0; background:#f0fdf4; }
.term-pill { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight:800; font-size:13px; color:#fff; }
.sem-badge { font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; }
.action-btn { padding:5px 12px; border-radius:7px; font-size:12px; font-weight:600; text-decoration:none; border:1px solid; display:inline-flex; align-items:center; gap:5px; cursor:pointer; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-4" style="flex-wrap:wrap;gap:12px;">
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Term & Semester Setup</h5>
        <div style="font-size:13px;color:#64748b;">
            Session:
            <span style="background:#ede9fe;color:#4f46e5;font-weight:600;padding:2px 10px;border-radius:20px;margin-left:4px;">
                {{ $current_session }}
            </span>
            &nbsp;·&nbsp;
            <span style="color:#64748b;">{{ $semesters_per_year }} semesters × {{ $terms_per_semester }} terms = {{ $totalTerms }} terms total</span>
        </div>
    </div>
    <a href="{{ route('marks.index') }}"
       style="background:#4f46e5;color:#fff;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-pencil-square"></i> Enter Marks
    </a>
</div>

<div class="row">

    {{-- ── LEFT: Semester / Term grid ─────────────────────────────────── --}}
    <div class="col-lg-8">

        @for($sem = 1; $sem <= $semesters_per_year; $sem++)
        <div class="ts-card">
            <div class="ts-card-header">
                <div class="d-flex align-items-center" style="gap:10px;">
                    <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-calendar2-week" style="color:#fff;font-size:14px;"></i>
                    </div>
                    <strong style="font-size:15px;color:#1e293b;">Semester {{ $sem }}</strong>
                </div>
                @php
                    $semTerms = collect();
                    for($t = (($sem-1)*$terms_per_semester)+1; $t <= $sem*$terms_per_semester; $t++) {
                        if(isset($exams[$t])) $semTerms->push($exams[$t]);
                    }
                    $semDone = $semTerms->count() === $terms_per_semester;
                @endphp
                <span class="sem-badge" style="background:{{ $semDone ? '#d1fae5' : '#f1f5f9' }};color:{{ $semDone ? '#065f46' : '#64748b' }};">
                    {{ $semTerms->count() }}/{{ $terms_per_semester }} terms set up
                </span>
            </div>
            <div class="ts-card-body">
                <div class="term-grid">
                @for($t = (($sem-1)*$terms_per_semester)+1; $t <= $sem*$terms_per_semester; $t++)
                @php
                    $termNum = $t;
                    $exam = $exams[$termNum] ?? null;
                    $colors = ['#4f46e5','#7c3aed','#0ea5e9','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6'];
                    $color = $colors[($termNum - 1) % count($colors)];
                @endphp
                <div class="term-row {{ $exam ? 'has-exam' : '' }}">
                    {{-- Pill --}}
                    <div class="term-pill" style="background:{{ $exam ? '#10b981' : $color }};">
                        T{{ $termNum }}
                    </div>

                    {{-- Info --}}
                    <div style="flex:1;min-width:0;">
                        @if($exam)
                            <div style="font-weight:700;font-size:14px;color:#1e293b;">{{ $exam->name }}</div>
                            <div style="font-size:12px;color:#64748b;margin-top:3px;display:flex;gap:12px;flex-wrap:wrap;">
                                <span><i class="bi bi-calendar3 mr-1"></i>Term {{ $termNum }} of Semester {{ $sem }}</span>
                                @if($exam->start_date && $exam->end_date)
                                <span><i class="bi bi-clock mr-1"></i>{{ $exam->start_date->format('d M') }} – {{ $exam->end_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        @else
                            <div style="font-weight:600;font-size:14px;color:#94a3b8;">Term {{ $termNum }} — Not created</div>
                            <div style="font-size:12px;color:#cbd5e1;margin-top:3px;">Semester {{ $sem }}, Term {{ (($termNum-1) % $terms_per_semester) + 1 }}</div>
                        @endif
                    </div>

                    {{-- Status badge --}}
                    @if($exam)
                    @php $badge = $exam->statusBadge(); @endphp
                    <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                        {{ $badge['label'] }}
                    </span>
                    @endif

                    {{-- Actions --}}
                    <div class="d-flex" style="gap:6px;flex-shrink:0;">
                        @if($exam)
                            {{-- Enter Marks --}}
                            <a href="{{ route('marks.index') }}"
                               class="action-btn"
                               style="background:#ede9fe;border-color:#c4b5fd;color:#4f46e5;">
                                <i class="bi bi-pencil-square"></i> Marks
                            </a>
                            {{-- Progress --}}
                            <a href="{{ route('marks.progress', $exam->id) }}"
                               class="action-btn"
                               style="background:#f1f5f9;border-color:#e2e8f0;color:#475569;">
                                <i class="bi bi-bar-chart-steps"></i>
                            </a>
                            @if(Qs::userIsTeamSA())
                            {{-- Edit --}}
                            <a href="{{ route('exams.edit', $exam->id) }}"
                               class="action-btn"
                               style="background:#f1f5f9;border-color:#e2e8f0;color:#475569;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(Qs::userIsSuperAdmin())
                            <form method="POST" action="{{ route('exams.destroy', $exam->id) }}" class="d-inline"
                                  onsubmit="return confirm('Delete Term {{ $termNum }}? All marks for this term will also be deleted.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn" style="background:#fee2e2;border-color:#fecaca;color:#ef4444;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        @else
                            {{-- Create this term --}}
                            @if(Qs::userIsTeamSA())
                            <button type="button"
                                    onclick="openCreateModal({{ $termNum }}, {{ $sem }})"
                                    class="action-btn"
                                    style="background:#4f46e5;border-color:#4f46e5;color:#fff;">
                                <i class="bi bi-plus-circle"></i> Create
                            </button>
                            @endif
                        @endif
                    </div>
                </div>
                @endfor
                </div>
            </div>
        </div>
        @endfor

    </div>

    {{-- ── RIGHT: Settings + Auto-Promotion ──────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Structure Settings --}}
        @if(Qs::userIsSuperAdmin())
        <div class="ts-card mb-4">
            <div class="ts-card-header">
                <div class="d-flex align-items-center" style="gap:8px;">
                    <i class="bi bi-sliders" style="color:#4f46e5;font-size:16px;"></i>
                    <strong style="font-size:14px;color:#1e293b;">Structure Settings</strong>
                </div>
            </div>
            <div class="ts-card-body">
                <form method="POST" action="{{ route('term_setup.settings') }}">
                    @csrf
                    @if(session('flash_success'))
                    <div class="alert alert-success py-2 mb-3" style="font-size:13px;">{{ session('flash_success') }}</div>
                    @endif

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">Semesters per Year</label>
                        <select name="semesters_per_year" class="form-control form-control-sm">
                            @foreach([1,2,3,4] as $n)
                            <option value="{{ $n }}" {{ $semesters_per_year == $n ? 'selected' : '' }}>{{ $n }} semester{{ $n > 1 ? 's' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">Terms per Semester</label>
                        <select name="terms_per_semester" class="form-control form-control-sm">
                            @foreach([1,2,3,4] as $n)
                            <option value="{{ $n }}" {{ $terms_per_semester == $n ? 'selected' : '' }}>{{ $n }} term{{ $n > 1 ? 's' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size:11px;">Default: 2 semesters × 2 terms = 4 terms/year</small>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">Minimum Average to Pass (%)</label>
                        <input type="number" name="promotion_min_average" min="0" max="100"
                               value="{{ $promotion_min_avg }}" class="form-control form-control-sm">
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">Promotion Mode</label>
                        <select name="promotion_mode" class="form-control form-control-sm">
                            <option value="auto"   {{ $promotion_mode === 'auto'   ? 'selected' : '' }}>Auto (based on average)</option>
                            <option value="manual" {{ $promotion_mode === 'manual' ? 'selected' : '' }}>Manual (admin decides each student)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm btn-block">
                        <i class="bi bi-save mr-1"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Auto-Promotion --}}
        @if(Qs::userIsSuperAdmin())
        <div class="ts-card">
            <div class="ts-card-header">
                <div class="d-flex align-items-center" style="gap:8px;">
                    <i class="bi bi-arrow-up-circle" style="color:#10b981;font-size:16px;"></i>
                    <strong style="font-size:14px;color:#1e293b;">Auto-Promotion</strong>
                </div>
                <span style="background:#d1fae5;color:#065f46;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;">
                    {{ $promotion_mode === 'auto' ? 'Active' : 'Manual mode' }}
                </span>
            </div>
            <div class="ts-card-body">
                <div class="alert alert-info border-0 mb-3" style="font-size:12px;border-radius:8px;">
                    <i class="bi bi-info-circle mr-1"></i>
                    Automatically promotes all students whose session average meets the minimum threshold.
                    Students below the threshold are held back. Students with no marks are skipped.
                </div>

                <form method="POST" action="{{ route('term_setup.auto_promote') }}"
                      onsubmit="return confirm('Run auto-promotion for session {{ $current_session }}?\n\nThis will move ALL students to the next session based on their average marks.\n\nThis action can be reversed from the Manage Promotions page.')">
                    @csrf

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">
                            Minimum Average to Promote (%)
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="min_average" min="0" max="100"
                                   value="{{ $promotion_min_avg }}"
                                   class="form-control">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <small class="text-muted" style="font-size:11px;">Students at or above this average will be promoted</small>
                    </div>

                    <input type="hidden" name="promotion_mode" value="{{ $promotion_mode }}">

                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="bi bi-arrow-up-circle mr-1"></i>
                        Run Auto-Promotion for {{ $current_session }}
                    </button>
                </form>

                <hr style="border-color:#e2e8f0;margin:16px 0;">

                <div class="d-flex" style="gap:8px;">
                    <a href="{{ route('students.promotion') }}" class="btn btn-outline-primary btn-sm flex-fill">
                        <i class="bi bi-person-check mr-1"></i>Manual Promote
                    </a>
                    <a href="{{ route('students.promotion_manage') }}" class="btn btn-outline-secondary btn-sm flex-fill">
                        <i class="bi bi-list-check mr-1"></i>Manage
                    </a>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ── Create Term Modal ──────────────────────────────────────────────────── --}}
<div id="create-term-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 style="font-weight:700;margin:0;" id="modal-title">Create Term</h6>
            <button onclick="closeModal()" style="background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="{{ route('exams.store') }}">
            @csrf
            <input type="hidden" name="term" id="modal-term">

            <div class="form-group mb-3">
                <label style="font-size:12px;font-weight:600;color:#475569;">Exam / Term Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="modal-name" required class="form-control"
                       placeholder="e.g. First Semester — Term 1">
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#475569;">End Date</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label style="font-size:12px;font-weight:600;color:#475569;">Status</label>
                <select name="status" class="form-control">
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="form-group mb-4">
                <label style="font-size:12px;font-weight:600;color:#475569;">Notes (optional)</label>
                <textarea name="description" rows="2" class="form-control" placeholder="Optional notes for teachers"></textarea>
            </div>

            <div class="d-flex" style="gap:8px;">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-plus-circle mr-1"></i>Create Term
                </button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
var semesterNames = {
    @for($sem = 1; $sem <= $semesters_per_year; $sem++)
    {{ $sem }}: 'Semester {{ $sem }}',
    @endfor
};

function openCreateModal(termNum, semNum) {
    document.getElementById('modal-term').value = termNum;
    document.getElementById('modal-title').textContent = 'Create Term ' + termNum + ' (Semester ' + semNum + ')';
    document.getElementById('modal-name').value = 'Semester ' + semNum + ' — Term ' + termNum;
    document.getElementById('create-term-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('create-term-modal').style.display = 'none';
}

document.getElementById('create-term-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
