@extends('layouts.master')
@section('page_title', 'Marks')
@section('content')

@php
    $currentSession = \App\Helpers\Qs::getCurrentSession();
    $currentExams   = \App\Models\Exam::where('year', $currentSession)->orderBy('term')->get();
@endphp

{{-- ── Header ───────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h4 style="font-size:20px;font-weight:800;color:#1e293b;margin:0 0 4px;">Marks & Exams</h4>
        <p style="font-size:13px;color:#64748b;margin:0;">Session: <strong>{{ $currentSession }}</strong></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if(\App\Helpers\Qs::userIsTeamSA())
        <a href="{{ route('marks.batch_fix') }}" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-wrench-adjustable"></i> Batch Fix
        </a>
        <a href="{{ route('marks.insights') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-graph-up-arrow"></i> Smart Insights
        </a>
        <a href="{{ route('marks.tabulation') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-table"></i> Tabulation
        </a>
        @endif
        <a href="{{ route('marks.bulk') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-file-earmark-text"></i> Marksheet
        </a>
    </div>
</div>

{{-- ── Current Session Exam Cards ───────────────────────────────────────── --}}
@if($currentExams->isNotEmpty())
<div style="margin-bottom:28px;">
    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;">
        Current Session Exams — Click to view progress
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;">
        @foreach($currentExams as $ex)
        @php
            $badge = $ex->statusBadge();
            // Quick completion count
            $tex = 'tex'.$ex->term;
            $enteredCount = \App\Models\Mark::where('exam_id', $ex->id)
                ->where('year', $currentSession)
                ->where($tex, '>', 0)
                ->distinct('subject_id')
                ->count('subject_id');
            $totalSubjects = \App\Models\Subject::count();
        @endphp
        <a href="{{ route('marks.progress', $ex->id) }}" style="text-decoration:none;">
            <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:all .15s;"
                 onmouseover="this.style.borderColor='#4f46e5';this.style.boxShadow='0 4px 16px rgba(79,70,229,.15)'"
                 onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 4px rgba(0,0,0,.05)'">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="color:#fff;font-size:14px;font-weight:800;">S{{ $ex->term }}</span>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:15px;color:#1e293b;">{{ $ex->name }}</div>
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">Semester {{ $ex->term }} · {{ $ex->year }}</div>
                        </div>
                    </div>
                    <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                        {{ $badge['label'] }}
                    </span>
                </div>
                @if($ex->start_date && $ex->end_date)
                <div style="font-size:12px;color:#94a3b8;margin-bottom:12px;">
                    <i class="bi bi-calendar3 mr-1"></i>
                    {{ $ex->start_date->format('d M') }} – {{ $ex->end_date->format('d M Y') }}
                </div>
                @endif
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:12px;color:#64748b;">
                        <i class="bi bi-bar-chart-steps mr-1"></i>View marks progress
                    </span>
                    <i class="bi bi-arrow-right" style="color:#4f46e5;font-size:14px;"></i>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@else
<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:20px;flex-shrink:0;"></i>
    <div>
        <div style="font-weight:600;font-size:13px;color:#92400e;">No exams created for {{ $currentSession }}</div>
        <div style="font-size:12px;color:#a16207;margin-top:2px;">
            @if(\App\Helpers\Qs::userIsTeamSA())
            <a href="{{ route('exams.index') }}" style="color:#92400e;font-weight:700;">Create Semester 1 and Semester 2 exams first →</a>
            @else
            Ask your administrator to create the semester exams.
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── Quick Entry (manual selector) ──────────────────────────────────────── --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;">
        <div style="background:#dbeafe;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-pencil-square" style="color:#3b82f6;font-size:14px;"></i>
        </div>
        <div>
            <span style="font-weight:700;font-size:14px;color:#1e293b;">Quick Entry</span>
            <span style="font-size:12px;color:#94a3b8;margin-left:8px;">Select exam + class + section + subject to go directly to mark entry</span>
        </div>
    </div>
    <div style="padding:20px;">
        @if($currentExams->isNotEmpty())
            @include('pages.support_team.marks.selector')
        @else
            <div style="text-align:center;padding:30px 20px;">
                <i class="bi bi-journal-x" style="font-size:40px;color:#cbd5e1;"></i>
                <div style="color:#94a3b8;font-size:14px;margin-top:12px;">
                    No exams available for the current session ({{ $currentSession }}).
                    @if(\App\Helpers\Qs::userIsTeamSA())
                    <br><a href="{{ route('exams.index') }}" style="color:#4f46e5;font-weight:600;">Create exams first →</a>
                    @else
                    <br>Please contact the administrator to create exams.
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ── How It Works — helps new users understand the flow ─────────────────── --}}
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:20px 24px;margin-top:20px;">
    <div style="font-weight:700;font-size:14px;color:#1e293b;margin-bottom:12px;">
        <i class="bi bi-lightbulb mr-1" style="color:#eab308;"></i> How to Enter Marks
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <div style="display:flex;gap:10px;align-items:flex-start;">
            <div style="background:#dbeafe;color:#3b82f6;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;">1</div>
            <div>
                <div style="font-weight:600;font-size:13px;color:#1e293b;">Open Manage Marks</div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">Choose exam, class, section, and subject — use <strong>Bulk Insert</strong> on the marks grid to paste many rows at once</div>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;">
            <div style="background:#d1fae5;color:#059669;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;">2</div>
            <div>
                <div style="font-weight:600;font-size:13px;color:#1e293b;">Enter Marks</div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">Fill in Assessment (30), Mid Exam (20), and Final Exam (50) for each student</div>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;">
            <div style="background:#fef3c7;color:#d97706;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;">3</div>
            <div>
                <div style="font-weight:600;font-size:13px;color:#1e293b;">Auto Calculations</div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">Grades, positions, and totals are calculated automatically on save</div>
            </div>
        </div>
    </div>
</div>

@endsection
