@extends('layouts.master')
@section('page_title', 'Marks Progress — ' . $exam->name)
@section('content')

@php
    $statusMeta = [
        'complete'    => ['bg'=>'#d1fae5','color'=>'#065f46','border'=>'#a7f3d0','icon'=>'bi-check-circle-fill','label'=>'Complete'],
        'partial'     => ['bg'=>'#fef3c7','color'=>'#92400e','border'=>'#fde68a','icon'=>'bi-hourglass-split',  'label'=>'Partial'],
        'not_started' => ['bg'=>'#fee2e2','color'=>'#991b1b','border'=>'#fecaca','icon'=>'bi-x-circle-fill',    'label'=>'Not Started'],
    ];
@endphp

{{-- ── Page Header ──────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <a href="{{ route('exams.index') }}" style="color:#94a3b8;font-size:13px;text-decoration:none;display:flex;align-items:center;gap:4px;">
                <i class="bi bi-chevron-left"></i> Exams
            </a>
            <span style="color:#cbd5e1;">/</span>
            <span style="color:#64748b;font-size:13px;">{{ $exam->name }}</span>
        </div>
        <h4 style="font-size:20px;font-weight:800;color:#1e293b;margin:0 0 4px;">Marks Progress Dashboard</h4>
        <div style="font-size:13px;color:#64748b;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span><i class="bi bi-calendar3 mr-1"></i>{{ $exam->year }}</span>
            <span><i class="bi bi-bookmark mr-1"></i>Semester {{ $exam->term }}</span>
            @if($exam->start_date && $exam->end_date)
            <span><i class="bi bi-clock mr-1"></i>{{ $exam->start_date->format('d M') }} – {{ $exam->end_date->format('d M Y') }}</span>
            @endif
            @php $badge = $exam->statusBadge(); @endphp
            <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;">
                {{ $badge['label'] }}
            </span>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('marks.index') }}" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(79,70,229,.3);">
            <i class="bi bi-pencil-square"></i> Enter Marks
        </a>
        <a href="{{ route('marks.tabulation') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-table"></i> Tabulation
        </a>
    </div>
</div>

{{-- ── Overall Progress Card ────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#1e1b4b,#312e81);border-radius:16px;padding:24px 28px;margin-bottom:24px;position:relative;overflow:hidden;box-shadow:0 8px 32px rgba(30,27,75,.4);">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-60px;right:100px;width:150px;height:150px;background:rgba(255,255,255,.03);border-radius:50%;"></div>
    <div style="position:relative;z-index:1;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <div style="color:rgba(255,255,255,.6);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Overall Completion</div>
                <div style="display:flex;align-items:baseline;gap:8px;">
                    <span style="color:#fff;font-size:48px;font-weight:800;line-height:1;">{{ $overallPct }}<span style="font-size:24px;">%</span></span>
                    <span style="color:rgba(255,255,255,.5);font-size:14px;">{{ $completeCells }} / {{ $totalCells }} subjects complete</span>
                </div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                @php
                $totals = ['complete'=>0,'partial'=>0,'not_started'=>0];
                foreach($matrix as $cd) {
                    $totals['complete']    += $cd['complete'];
                    $totals['partial']     += $cd['partial'];
                    $totals['not_started'] += $cd['not_started'];
                }
                @endphp
                @foreach(['complete'=>['#10b981','Complete'],'partial'=>['#f59e0b','Partial'],'not_started'=>['#ef4444','Not Started']] as $key=>[$col,$lbl])
                <div style="text-align:center;">
                    <div style="color:{{ $col }};font-size:28px;font-weight:800;line-height:1;">{{ $totals[$key] }}</div>
                    <div style="color:rgba(255,255,255,.5);font-size:11px;font-weight:500;margin-top:3px;">{{ $lbl }}</div>
                </div>
                @endforeach
            </div>
        </div>
        {{-- Progress bar --}}
        <div style="margin-top:20px;">
            <div style="background:rgba(255,255,255,.1);border-radius:20px;height:8px;overflow:hidden;">
                <div style="background:linear-gradient(90deg,#10b981,#34d399);height:8px;width:{{ $overallPct }}%;border-radius:20px;transition:width .6s ease;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:6px;">
                <span style="color:rgba(255,255,255,.4);font-size:11px;">0%</span>
                <span style="color:rgba(255,255,255,.4);font-size:11px;">100%</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Legend ───────────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
    @foreach($statusMeta as $key => $m)
    <div style="background:{{ $m['bg'] }};border:1px solid {{ $m['border'] }};border-radius:8px;padding:6px 14px;display:flex;align-items:center;gap:6px;">
        <i class="bi {{ $m['icon'] }}" style="color:{{ $m['color'] }};font-size:13px;"></i>
        <span style="font-size:12px;font-weight:600;color:{{ $m['color'] }};">{{ $m['label'] }}</span>
    </div>
    @endforeach
    <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:6px 14px;display:flex;align-items:center;gap:6px;margin-left:auto;">
        <i class="bi bi-funnel" style="color:#64748b;font-size:13px;"></i>
        <select id="filter-status" onchange="filterByStatus(this.value)"
                style="border:none;background:transparent;font-size:12px;font-weight:600;color:#475569;cursor:pointer;outline:none;">
            <option value="all">Show All</option>
            <option value="not_started">Show Not Started Only</option>
            <option value="partial">Show Partial Only</option>
            <option value="complete">Show Complete Only</option>
        </select>
    </div>
</div>

{{-- ── Class Sections ───────────────────────────────────────────────────── --}}
@if(empty($matrix))
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:60px 20px;text-align:center;">
    <i class="bi bi-journal-x" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:16px;"></i>
    <h5 style="color:#475569;font-weight:600;margin-bottom:8px;">No subjects found</h5>
    <p style="color:#94a3b8;font-size:13px;margin:0;">
        @if($isTeacher)
            You have no subjects assigned for this exam's classes.
        @else
            No classes or subjects are configured yet.
        @endif
    </p>
</div>
@else

@foreach($matrix as $classData)
@php
    $class = $classData['class'];
    $classPct = $classData['total'] > 0 ? round(($classData['complete'] / $classData['total']) * 100) : 0;
    $classColor = $classPct === 100 ? '#10b981' : ($classPct >= 50 ? '#f59e0b' : '#ef4444');
@endphp

<div class="class-block" style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:20px;">

    {{-- Class header --}}
    <div style="padding:16px 20px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;cursor:pointer;"
         onclick="toggleClass('class-{{ $class->id }}')">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="color:#fff;font-size:13px;font-weight:800;">{{ strtoupper(substr($class->name, 0, 2)) }}</span>
            </div>
            <div>
                <div style="font-weight:700;font-size:15px;color:#1e293b;">{{ $class->name }}</div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">
                    {{ count($classData['sections']) }} section(s) ·
                    {{ $classData['total'] }} subject slot(s) ·
                    <span style="color:{{ $classColor }};font-weight:600;">{{ $classPct }}% complete</span>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            {{-- Mini progress bar --}}
            <div style="width:120px;">
                <div style="background:#e2e8f0;border-radius:20px;height:6px;overflow:hidden;">
                    <div style="background:{{ $classColor }};height:6px;width:{{ $classPct }}%;border-radius:20px;"></div>
                </div>
            </div>
            {{-- Status counts --}}
            <div style="display:flex;gap:6px;">
                @if($classData['complete'] > 0)
                <span style="background:#d1fae5;color:#065f46;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">✓ {{ $classData['complete'] }}</span>
                @endif
                @if($classData['partial'] > 0)
                <span style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">⏳ {{ $classData['partial'] }}</span>
                @endif
                @if($classData['not_started'] > 0)
                <span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">✗ {{ $classData['not_started'] }}</span>
                @endif
            </div>
            <i class="bi bi-chevron-down" id="chevron-class-{{ $class->id }}" style="color:#94a3b8;font-size:14px;transition:transform .2s;"></i>
        </div>
    </div>

    {{-- Sections --}}
    <div id="class-{{ $class->id }}">
        @foreach($classData['sections'] as $sectionData)
        @php $section = $sectionData['section']; @endphp

        <div style="border-bottom:1px solid #f8fafc;">
            {{-- Section label --}}
            <div style="padding:10px 20px 6px;background:#fafbfc;display:flex;align-items:center;gap:8px;">
                <span style="background:#ede9fe;color:#4f46e5;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;">
                    Section {{ $section->name }}
                </span>
                <span style="font-size:11px;color:#94a3b8;">{{ $sectionData['student_count'] }} students</span>
                @if($section->teacher)
                <span style="font-size:11px;color:#94a3b8;">· Homeroom: {{ $section->teacher->name }}</span>
                @endif
            </div>

            {{-- Subject grid --}}
            <div style="padding:10px 20px 14px;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;">
                @foreach($sectionData['subjects'] as $subData)
                @php
                    $sub    = $subData['subject'];
                    $status = $subData['status'];
                    $m      = $statusMeta[$status];
                    $manageUrl = route('marks.manage', [
                        $exam->id,
                        $class->id,
                        $section->id,
                        $sub->id,
                    ]);
                @endphp
                <div class="subject-cell" data-status="{{ $status }}"
                     style="background:{{ $m['bg'] }};border:1.5px solid {{ $m['border'] }};border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;transition:all .15s;"
                     onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">

                    {{-- Status icon --}}
                    <div style="flex-shrink:0;">
                        <i class="bi {{ $m['icon'] }}" style="font-size:20px;color:{{ $m['color'] }};"></i>
                    </div>

                    {{-- Subject info --}}
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:13px;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $sub->name }}
                        </div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;display:flex;align-items:center;gap:6px;">
                            @if($sub->teacher)
                            <span>{{ explode(' ', $sub->teacher->name)[0] }}</span>
                            @endif
                            @if($status !== 'not_started')
                            <span style="color:{{ $m['color'] }};font-weight:600;">
                                {{ $subData['entered'] }}/{{ $subData['student_count'] }} entered
                            </span>
                            @endif
                        </div>
                        @if($status !== 'not_started')
                        {{-- Mini progress bar --}}
                        <div style="background:rgba(0,0,0,.08);border-radius:20px;height:3px;margin-top:6px;overflow:hidden;">
                            <div style="background:{{ $m['color'] }};height:3px;width:{{ $subData['pct'] }}%;border-radius:20px;"></div>
                        </div>
                        @endif
                    </div>

                    {{-- Action button --}}
                    <a href="{{ $manageUrl }}"
                       style="flex-shrink:0;background:{{ $status === 'complete' ? 'rgba(16,185,129,.15)' : ($status === 'partial' ? 'rgba(245,158,11,.15)' : 'rgba(79,70,229,.12)') }};border:1px solid {{ $status === 'complete' ? '#a7f3d0' : ($status === 'partial' ? '#fde68a' : '#c4b5fd') }};color:{{ $status === 'complete' ? '#065f46' : ($status === 'partial' ? '#92400e' : '#4f46e5') }};border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;"
                       title="{{ $status === 'complete' ? 'Review marks' : ($status === 'partial' ? 'Continue entering' : 'Start entering marks') }}">
                        @if($status === 'complete')
                            <i class="bi bi-eye"></i>
                        @elseif($status === 'partial')
                            <i class="bi bi-pencil"></i>
                        @else
                            <i class="bi bi-plus"></i>
                        @endif
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endif

@endsection

@section('scripts')
<script>
function toggleClass(id) {
    var el = document.getElementById(id);
    var chevron = document.getElementById('chevron-' + id);
    if (el.style.display === 'none') {
        el.style.display = '';
        chevron.style.transform = '';
    } else {
        el.style.display = 'none';
        chevron.style.transform = 'rotate(-90deg)';
    }
}

function filterByStatus(status) {
    document.querySelectorAll('.subject-cell').forEach(function(cell) {
        if (status === 'all' || cell.dataset.status === status) {
            cell.style.display = '';
        } else {
            cell.style.display = 'none';
        }
    });
}
</script>
@endsection
