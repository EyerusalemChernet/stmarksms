@extends('layouts.master')
@section('page_title', 'Exams')
@section('content')

@php
    $semestersCreated = $session_exams ?? [];
    $sem1Done = in_array(1, $semestersCreated);
    $sem2Done = in_array(2, $semestersCreated);
@endphp

{{-- ── Page Header ──────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Exam Management</h5>
        <div style="font-size:13px;color:#64748b;">
            Academic Session:
            <span style="background:#ede9fe;color:#4f46e5;font-weight:600;padding:2px 10px;border-radius:20px;margin-left:4px;">
                {{ $current_session }}
            </span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('marks.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-journal-check"></i> Enter Marks
        </a>
        <a href="{{ route('marks.tabulation') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-table"></i> Tabulation
        </a>
    </div>
</div>

{{-- ── Session Progress ─────────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;border-radius:14px;padding:20px 24px;margin-bottom:24px;">
    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;">
        Session {{ $current_session }} — Semester Progress
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        @foreach([1 => 'Semester 1', 2 => 'Semester 2'] as $term => $label)
        @php
            $done = in_array($term, $semestersCreated);
            $exam = $exams->where('term', $term)->where('year', $current_session)->first();
        @endphp
        <div style="background:#fff;border:1.5px solid {{ $done ? '#a7f3d0' : '#e2e8f0' }};border-radius:10px;padding:16px;display:flex;align-items:center;gap:14px;">
            <div style="background:{{ $done ? '#d1fae5' : '#f1f5f9' }};border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-{{ $done ? 'check-circle-fill' : 'circle' }}" style="font-size:22px;color:{{ $done ? '#10b981' : '#cbd5e1' }};"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:14px;color:#1e293b;">{{ $label }}</div>
                @if($exam)
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $exam->name }}</div>
                    @if($exam->start_date && $exam->end_date)
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                        {{ $exam->start_date->format('d M') }} – {{ $exam->end_date->format('d M Y') }}
                    </div>
                    @endif
                @else
                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Not created yet</div>
                @endif
            </div>
            @if($exam)
            @php $badge = $exam->statusBadge(); @endphp
            <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                {{ $badge['label'] }}
            </span>
            @endif
        </div>
        @endforeach
    </div>
</div>

<div class="row">

    {{-- ── Exam List ────────────────────────────────────────────────────── --}}
    <div class="col-lg-7 mb-4">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="background:#ede9fe;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-journal-check" style="color:#4f46e5;font-size:14px;"></i>
                    </div>
                    <span style="font-weight:700;font-size:14px;color:#1e293b;">All Exams</span>
                </div>
                <span style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
                    {{ $exams->count() }} total
                </span>
            </div>

            @if($exams->isEmpty())
            <div style="padding:48px 20px;text-align:center;">
                <i class="bi bi-journal-x" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
                <p style="color:#94a3b8;font-size:13px;margin:0;">No exams created yet. Use the form to add one.</p>
            </div>
            @else
            @foreach($exams->groupBy('year')->sortKeysDesc() as $year => $yearExams)
            <div style="padding:10px 20px 4px;background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.6px;">
                    Session {{ $year }}
                </span>
            </div>
            @foreach($yearExams->sortBy('term') as $ex)
            @php $badge = $ex->statusBadge(); @endphp
            <div style="padding:14px 20px;border-bottom:1px solid #f8fafc;display:flex;align-items:center;gap:14px;"
                 onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                {{-- Semester pill --}}
                <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span style="color:#fff;font-size:11px;font-weight:800;text-align:center;line-height:1.2;">S{{ $ex->term }}</span>
                </div>

                {{-- Info --}}
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:14px;color:#1e293b;">{{ $ex->name }}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span><i class="bi bi-calendar3 mr-1"></i>Semester {{ $ex->term }}</span>
                        @if($ex->start_date && $ex->end_date)
                        <span><i class="bi bi-clock mr-1"></i>{{ $ex->start_date->format('d M') }} – {{ $ex->end_date->format('d M Y') }}</span>
                        @endif
                        @if($ex->description)
                        <span class="text-muted" style="font-size:11px;">{{ \Illuminate\Support\Str::limit($ex->description, 50) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                    {{ $badge['label'] }}
                </span>

                {{-- Actions --}}
                @if(Qs::userIsTeamSA())
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <a href="{{ route('marks.progress', $ex->id) }}"
                       style="background:#ede9fe;border:1px solid #c4b5fd;color:#4f46e5;border-radius:7px;padding:5px 10px;font-size:12px;text-decoration:none;white-space:nowrap;"
                       title="View marks progress for this exam">
                        <i class="bi bi-bar-chart-steps mr-1"></i>Progress
                    </a>
                    <a href="{{ route('exams.edit', $ex->id) }}"
                       style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:7px;padding:5px 10px;font-size:12px;text-decoration:none;">
                        <i class="bi bi-pencil"></i>
                    </a>
                    @if(Qs::userIsSuperAdmin())
                    <form method="post" action="{{ route('exams.destroy', $ex->id) }}" class="d-inline">
                        @csrf @method('delete')
                        <button onclick="return confirm('Delete this exam? All marks for this exam will also be deleted.')"
                                style="background:#fee2e2;border:1px solid #fecaca;color:#ef4444;border-radius:7px;padding:5px 10px;font-size:12px;cursor:pointer;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
            @endforeach
            @endif
        </div>
    </div>

    {{-- ── Add Exam Form ────────────────────────────────────────────────── --}}
    @if(Qs::userIsTeamSA())
    <div class="col-lg-5 mb-4">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);position:sticky;top:20px;">
            <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:16px 20px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-plus-circle-fill" style="font-size:18px;color:#fff;"></i>
                    <span style="font-weight:700;font-size:15px;color:#fff;">Create New Exam</span>
                </div>
                <div style="color:rgba(255,255,255,.75);font-size:12px;margin-top:4px;">
                    Session: <strong style="color:#fff;">{{ $current_session }}</strong>
                    @if($sem1Done && $sem2Done)
                    &nbsp;·&nbsp; <span style="color:#fde68a;">Both semesters created</span>
                    @elseif($sem1Done)
                    &nbsp;·&nbsp; <span style="color:#a7f3d0;">Semester 1 ✓</span>
                    @elseif($sem2Done)
                    &nbsp;·&nbsp; <span style="color:#a7f3d0;">Semester 2 ✓</span>
                    @endif
                </div>
            </div>

            @if($sem1Done && $sem2Done)
            <div style="padding:24px 20px;text-align:center;">
                <i class="bi bi-check-circle-fill" style="font-size:36px;color:#10b981;display:block;margin-bottom:12px;"></i>
                <p style="font-weight:600;color:#1e293b;margin-bottom:6px;">Both semesters are set up!</p>
                <p style="font-size:13px;color:#64748b;margin:0;">Edit existing exams using the pencil icon, or delete one to recreate it.</p>
            </div>
            @else
            <div style="padding:20px;">
                @if(session('flash_success'))
                <div style="background:#d1fae5;border:1px solid #a7f3d0;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#065f46;">
                    <i class="bi bi-check-circle mr-1"></i>{{ session('flash_success') }}
                </div>
                @endif
                @if(session('flash_danger'))
                <div style="background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#991b1b;">
                    <i class="bi bi-exclamation-circle mr-1"></i>{{ session('flash_danger') }}
                </div>
                @endif

                <form method="post" action="{{ route('exams.store') }}">
                    @csrf

                    {{-- Name --}}
                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">
                            Exam Name <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="e.g. First Semester Examination 2024"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
                    </div>

                    {{-- Semester --}}
                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">
                            Semester <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                            @foreach([1 => 'Semester 1', 2 => 'Semester 2'] as $val => $lbl)
                            @php $alreadyExists = in_array($val, $semestersCreated); @endphp
                            <label style="
                                display:flex;align-items:center;gap:8px;
                                border:1.5px solid {{ old('term') == $val ? '#4f46e5' : '#e2e8f0' }};
                                border-radius:8px;padding:10px 12px;cursor:{{ $alreadyExists ? 'not-allowed' : 'pointer' }};
                                background:{{ $alreadyExists ? '#f8fafc' : '#fff' }};
                                opacity:{{ $alreadyExists ? '.5' : '1' }};
                            ">
                                <input type="radio" name="term" value="{{ $val }}"
                                       {{ old('term') == $val ? 'checked' : '' }}
                                       {{ $alreadyExists ? 'disabled' : '' }}
                                       style="accent-color:#4f46e5;">
                                <span style="font-size:13px;font-weight:600;color:#1e293b;">{{ $lbl }}</span>
                                @if($alreadyExists)
                                <span style="margin-left:auto;background:#d1fae5;color:#065f46;font-size:10px;font-weight:600;padding:1px 6px;border-radius:20px;">Done</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                   style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                   style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Status</label>
                        <select name="status" style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;background:#fff;outline:none;">
                            <option value="upcoming" {{ old('status','upcoming') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="ongoing"  {{ old('status') === 'ongoing'   ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed"{{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled"{{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div style="margin-bottom:18px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Notes (optional)</label>
                        <textarea name="description" rows="2"
                                  placeholder="e.g. Covers chapters 1–8. Results due by end of month."
                                  style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;resize:none;">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit" style="width:100%;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:8px;padding:11px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(79,70,229,.3);">
                        <i class="bi bi-plus-circle mr-1"></i>Create Exam
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

@endsection
