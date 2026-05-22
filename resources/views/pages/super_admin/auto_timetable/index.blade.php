@extends('layouts.master')
@section('page_title', 'Automatic Timetable Generator')
@section('content')

<style>
.auto-tt-wrap { display:flex; gap:0; min-height:calc(100vh - 180px); }
.auto-tt-sidebar { width:340px; flex-shrink:0; border-right:1px solid #e5e7eb; background:#fafbfc; overflow-y:auto; max-height:calc(100vh - 140px); }
.auto-tt-main { flex:1; padding:16px 20px; overflow:auto; background:#fff; }
.auto-tt-sidebar .card { border:0; border-radius:0; box-shadow:none; margin:0; }
.auto-tt-sidebar .card-header { background:transparent; border-bottom:1px solid #eee; padding:12px 16px; cursor:pointer; }
.auto-tt-preview-grid { width:100%; border-collapse:collapse; font-size:13px; }
.auto-tt-preview-grid th, .auto-tt-preview-grid td { border:1px solid #e0e0e0; padding:6px 8px; vertical-align:top; text-align:center; }
.auto-tt-preview-grid th { background:#f5f5f5; font-weight:600; }
.auto-tt-preview-grid .period-col { text-align:left; min-width:100px; background:#fafafa; font-size:12px; }
.auto-tt-cell-card { border-radius:6px; padding:6px 8px; min-height:52px; cursor:pointer; text-align:left; }
.auto-tt-cell-card .subj { font-weight:600; font-size:12px; display:block; }
.auto-tt-cell-card .tchr { font-size:11px; color:#555; }
.auto-tt-cell-empty { min-height:52px; border:1px dashed #ddd; border-radius:6px; cursor:pointer; background:#fafafa; }
.auto-tt-editable { cursor:pointer; }
.auto-tt-editable:hover { opacity:0.92; box-shadow:inset 0 0 0 1px rgba(25,118,210,0.35); }
.auto-tt-cell-break { background:#f0f0f0; color:#888; font-size:11px; font-style:italic; }
.auto-tt-rate-bar { background:#e8f5e9; border:1px solid #c8e6c9; border-radius:8px; padding:12px 16px; margin-bottom:16px; }
.day-toggle.active { background:#1976d2 !important; color:#fff !important; border-color:#1976d2 !important; }
.plan-row { border:1px solid #eee; border-radius:6px; padding:10px; margin-bottom:8px; background:#fff; }
.plan-table-header { font-size:11px; font-weight:600; color:#666; margin-bottom:6px; }
.plan-row .btn-remove-plan-row { padding:0.35rem 0.55rem; line-height:1; }
.auto-tt-toolbar { background:#f5f7fa; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; margin-bottom:12px; }
.auto-tt-cell-card.swap-pick, .auto-tt-cell-empty.swap-pick { outline:3px solid #ff9800; outline-offset:-2px; }
.auto-tt-customize-hint { font-size:12px; color:#666; }
.timing-modal-row { display:flex; align-items:center; gap:8px; padding:10px 0; border-bottom:1px solid #f0f0f0; flex-wrap:wrap; }
.timing-modal-row .timing-label { min-width:72px; font-weight:600; font-size:13px; }
.timing-modal-row.break-row { background:#f9fafb; border-radius:6px; padding:10px; margin:6px 0; }
.timing-modal-row input[type="time"] { max-width:120px; }
.btn-add-break-after { border-style:dashed !important; white-space:nowrap; font-size:12px; }
.class-section-picker { max-height:280px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
.class-picker-block { border-bottom:1px solid #eee; }
.class-picker-block:last-child { border-bottom:0; }
.class-picker-header { display:flex; align-items:center; gap:8px; padding:10px 12px; cursor:pointer; user-select:none; background:#f8f9fa; }
.class-picker-header:hover { background:#f0f4f8; }
.class-picker-header .chevron { margin-left:auto; transition:transform .2s; color:#888; }
.class-picker-block.open .class-picker-header .chevron { transform:rotate(90deg); }
.sections-list { display:none; padding:4px 12px 12px 28px; }
.class-picker-block.open .sections-list { display:block; }
.section-pick-row { display:flex; align-items:center; gap:8px; padding:4px 0; font-size:13px; }
.section-pick-row input { margin:0; }
</style>

<div class="auto-tt-wrap">
    <aside class="auto-tt-sidebar">
        <div class="card">
            <div class="card-header" data-toggle="collapse" data-target="#cfg-basic">
                <h6 class="mb-0 font-weight-semibold"><i class="bi bi-sliders mr-1"></i> Basic Timetable Information</h6>
            </div>
            <div id="cfg-basic" class="collapse show">
                <div class="card-body">
                    <div class="form-group">
                        <label class="font-weight-semibold">Plan name</label>
                        <input type="text" id="plan-name" class="form-control" value="Timetable Plan" placeholder="Timetable Plan">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-semibold">Select Class &amp; Sections <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">Click a class to expand its sections. Check the class row to select every section in that class.</p>
                        <div class="class-section-picker" id="class-section-picker">
                            @forelse($classes as $class)
                                @php $classSections = $class->section; @endphp
                                @if($classSections->isNotEmpty())
                                <div class="class-picker-block" data-class-id="{{ $class->id }}">
                                    <div class="class-picker-header">
                                        <input type="checkbox" class="class-select-all" data-class-id="{{ $class->id }}" title="Select all sections in {{ $class->name }}">
                                        <span class="font-weight-semibold">{{ $class->name }}</span>
                                        <small class="text-muted">({{ $classSections->count() }} section{{ $classSections->count() !== 1 ? 's' : '' }})</small>
                                        <i class="bi bi-chevron-right chevron"></i>
                                    </div>
                                    <div class="sections-list">
                                        @foreach($classSections as $sec)
                                            <label class="section-pick-row mb-0">
                                                <input type="checkbox" class="section-cb" value="{{ $sec->id }}" data-class-id="{{ $class->id }}">
                                                <span>{{ $sec->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @empty
                                <p class="text-muted small p-3 mb-0">No classes with active sections found.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-6 form-group">
                            <label class="font-weight-semibold small">School start</label>
                            <input type="time" id="school-start" class="form-control" value="08:00">
                        </div>
                        <div class="col-6 form-group">
                            <label class="font-weight-semibold small">School end</label>
                            <input type="time" id="school-end" class="form-control" value="14:00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">No. of Periods</label>
                        <select id="period-count" class="form-control">
                            @for($i = 4; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $i === 8 ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold d-block">School days</label>
                        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d)
                            <button type="button" class="btn btn-sm btn-outline-secondary day-toggle {{ in_array($d, ['Monday','Tuesday','Wednesday','Thursday','Friday']) ? 'active' : '' }} mb-1 mr-1" data-day="{{ $d }}">{{ substr($d,0,3) }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" data-toggle="collapse" data-target="#cfg-timings">
                <h6 class="mb-0 font-weight-semibold"><i class="bi bi-clock mr-1"></i> Custom Timings &amp; Breaks</h6>
            </div>
            <div id="cfg-timings" class="collapse show">
                <div class="card-body">
                    <p class="text-muted small mb-2" id="slots-hint">Set school hours and period count, then customize each period and add breaks in the popup.</p>
                    <p class="small mb-2" id="timings-summary"><strong>0</strong> period(s), <strong>0</strong> break(s)</p>
                    <div id="custom-slots-list" class="mb-2 small text-muted"></div>
                    <button type="button" class="btn btn-primary btn-block btn-sm" id="btn-open-timings">
                        <i class="bi bi-clock-history mr-1"></i> Customize Timings &amp; Breaks
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" data-toggle="collapse" data-target="#cfg-plan">
                <h6 class="mb-0 font-weight-semibold"><i class="bi bi-journal-text mr-1"></i> Timetable Planning</h6>
            </div>
            <div id="cfg-plan" class="collapse show">
                <div class="card-body">
                    <p class="text-muted small">All class subjects load automatically with department teachers. Open to review before generating.</p>
                    <button type="button" class="btn btn-outline-info btn-block btn-sm" id="btn-open-plan" disabled>
                        <i class="bi bi-pencil-square mr-1"></i> Edit Timetable Plan
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3 border-top bg-white" style="position:sticky;bottom:0;">
            <button type="button" class="btn btn-primary btn-block" id="btn-preview">
                <i class="bi bi-gear mr-1"></i> Generate Timetable
            </button>
            <button type="button" class="btn btn-success btn-block mt-2" id="btn-save" disabled>
                <i class="bi bi-save mr-1"></i> Save Timetable
            </button>
        </div>
    </aside>

    <main class="auto-tt-main">
        <div id="preview-empty" class="text-center text-muted py-5">
            <i class="bi bi-calendar-week" style="font-size:48px;opacity:.3;"></i>
            <p class="mt-3">Configure sections and planning, then click <strong>Generate Timetable</strong> for a live preview.</p>
        </div>
        <div id="preview-area" style="display:none;">
            <div class="auto-tt-rate-bar" id="rate-bar">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong id="rate-text">0% Placement Rate</strong>
                    <span id="rate-msg" class="small text-muted"></span>
                </div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-success" id="rate-progress" style="width:0%"></div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-light btn-sm" id="nav-prev"><i class="bi bi-chevron-left"></i></button>
                <h5 class="mb-0" id="nav-label">—</h5>
                <button type="button" class="btn btn-light btn-sm" id="nav-next"><i class="bi bi-chevron-right"></i></button>
            </div>
            <p class="text-center text-muted small mb-2" id="nav-counter"></p>
            <div id="customize-toolbar" class="auto-tt-toolbar" style="display:none;">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <span class="auto-tt-customize-hint" id="customize-hint">Click a period to edit, add, or remove a subject.</span>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-primary active" data-mode="edit" id="btn-mode-edit"><i class="bi bi-pencil mr-1"></i> Edit</button>
                        <button type="button" class="btn btn-outline-primary" data-mode="swap" id="btn-mode-swap"><i class="bi bi-arrow-left-right mr-1"></i> Swap periods</button>
                        <button type="button" class="btn btn-outline-secondary" data-mode="view" id="btn-mode-view">Done</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive" id="grid-container"></div>
        </div>
    </main>
</div>

{{-- Plan modal --}}
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Timetable Plan</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Each row uses teachers from the subject's assigned department (Manage Subjects), not the section homeroom teacher. Adjust teacher or weekly frequency as needed.</p>
                <ul class="nav nav-tabs" id="plan-tabs"></ul>
                <div class="tab-content pt-3" id="plan-tab-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

{{-- Custom timings modal --}}
<div class="modal fade" id="timingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Custom Timings</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span id="timings-modal-count" class="font-weight-semibold">0 Period(s), 0 Break(s)</span>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-regenerate-periods">Regenerate Periods</button>
                </div>
                <div id="timings-modal-rows" style="max-height:420px;overflow-y:auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-save-timings">Save Timings</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit cell modal --}}
<div class="modal fade" id="cellModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Period</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" id="cell-day">
                <input type="hidden" id="cell-slot-index">
                <input type="hidden" id="cell-section-idx">
                <div class="form-group">
                    <label>Subject</label>
                    <select id="cell-subject" class="form-control"></select>
                </div>
                <div class="form-group">
                    <label>Teacher (department)</label>
                    <select id="cell-teacher" class="form-control"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cell-clear">Clear</button>
                <button type="button" class="btn btn-primary" id="cell-save">Apply</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    var routes = {
        buildSlots: @json(route('auto_timetable.build_slots')),
        preview: @json(route('auto_timetable.preview')),
        generate: @json(route('auto_timetable.generate')),
        loadSaved: @json(route('auto_timetable.load_saved')),
        savePreview: @json(route('auto_timetable.save_preview')),
        swapCells: @json(route('auto_timetable.swap_cells')),
        updateCell: @json(route('auto_timetable.update_cell')),
        subjects: @json(url('super_admin/auto-timetable/sections/__ID__/subjects'))
    };
    var savedSectionIds = @json($savedSectionIds ?? []);

    var state = {
        slots: [],
        breaks: [],
        plans: {},
        preview: null,
        navIndex: 0,
        saved: false,
        customMode: 'view',
        swapPick: null
    };

    function csrf() { return $('meta[name="csrf-token"]').attr('content'); }
    function selectedDays() {
        return $('.day-toggle.active').map(function () { return $(this).data('day'); }).get();
    }
    function selectedSections() {
        return $('.section-cb:checked').map(function () { return $(this).val(); }).get();
    }

    function syncClassSelectAll(classId) {
        var $secs = $('.section-cb[data-class-id="' + classId + '"]');
        var allChecked = $secs.length > 0 && $secs.length === $secs.filter(':checked').length;
        $('.class-select-all[data-class-id="' + classId + '"]').prop('checked', allChecked);
    }

    function setSectionsChecked(sectionIds) {
        $('.section-cb').prop('checked', false);
        $('.class-select-all').prop('checked', false);
        sectionIds.forEach(function (id) {
            $('.section-cb[value="' + id + '"]').prop('checked', true);
        });
        $('.class-picker-block').each(function () {
            syncClassSelectAll($(this).data('class-id'));
        });
    }

    $(document).on('click', '.class-picker-header', function (e) {
        if ($(e.target).is('input')) { return; }
        $(this).closest('.class-picker-block').toggleClass('open');
    });

    $(document).on('change', '.class-select-all', function () {
        var classId = $(this).data('class-id');
        var checked = $(this).is(':checked');
        $('.section-cb[data-class-id="' + classId + '"]').prop('checked', checked);
        $(this).closest('.class-picker-block').addClass('open');
        onSectionsSelectionChanged();
    });

    $(document).on('change', '.section-cb', function () {
        syncClassSelectAll($(this).data('class-id'));
        $(this).closest('.class-picker-block').addClass('open');
        onSectionsSelectionChanged();
    });

    function onSectionsSelectionChanged() {
        var ids = selectedSections();
        if (!ids.length) {
            $('#preview-empty').show();
            $('#preview-area').hide();
            state.preview = null;
            state.saved = false;
            return;
        }
        loadSavedTimetables(ids, function (loaded) {
            if (!loaded) {
                $('#preview-empty').show();
                $('#preview-area').hide();
                state.preview = null;
                state.saved = false;
            }
        });
    }

    function formatTimeInput(val) {
        var p = val.split(':');
        var h = parseInt(p[0], 10);
        var m = p[1] || '00';
        var mer = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12 || 12;
        return h12 + ':' + m + ' ' + mer;
    }

    function time12ToInput(t) {
        if (!t) return '08:00';
        var m = t.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!m) return '08:00';
        var h = parseInt(m[1], 10);
        var min = m[2];
        if (m[3].toUpperCase() === 'PM' && h < 12) h += 12;
        if (m[3].toUpperCase() === 'AM' && h === 12) h = 0;
        return String(h).padStart(2, '0') + ':' + min;
    }

    function slotCounts() {
        var periods = 0, breaks = 0;
        state.slots.forEach(function (s) {
            if (s.type === 'break') breaks++; else periods++;
        });
        return { periods: periods, breaks: breaks };
    }

    function updateTimingsSummary() {
        var c = slotCounts();
        $('#timings-summary').html('<strong>' + c.periods + '</strong> period(s), <strong>' + c.breaks + '</strong> break(s)');
        $('#timings-modal-count').text(c.periods + ' Period(s), ' + c.breaks + ' Break(s)');
        $('#btn-regenerate-periods').text('Regenerate ' + $('#period-count').val() + ' Periods');
    }

    function buildSlotsFromServer(done) {
        var breaks = state.slots.filter(function (s) { return s.type === 'break'; }).map(function (b) {
            return { label: b.label || 'Break', from: b.time_from, to: b.time_to };
        });
        $.post(routes.buildSlots, {
            _token: csrf(),
            school_start: formatTimeInput($('#school-start').val()),
            school_end: formatTimeInput($('#school-end').val()),
            period_count: $('#period-count').val(),
            breaks: breaks
        }, function (res) {
            if (res.ok) {
                state.slots = res.slots;
                renderSlotsList();
                if (typeof done === 'function') done();
            }
        });
    }

    function buildPeriodRow(slot, idx, periodNum) {
        var $row = $('<div class="timing-modal-row" data-idx="' + idx + '"></div>');
        $row.append(
            '<span class="timing-label">' + (slot.label || ('Period ' + periodNum)) + '</span>' +
            '<input type="text" class="form-control form-control-sm slot-label" style="max-width:140px" value="' + (slot.label || '') + '" placeholder="Label">' +
            '<input type="time" class="form-control form-control-sm slot-from" value="' + time12ToInput(slot.time_from) + '">' +
            '<span>–</span>' +
            '<input type="time" class="form-control form-control-sm slot-to" value="' + time12ToInput(slot.time_to) + '">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary btn-add-break-after ml-auto" data-after="' + idx + '">Add Break + After Period ' + periodNum + '</button>'
        );
        return $row;
    }

    function buildBreakRow(slot, idx) {
        var $row = $('<div class="timing-modal-row break-row" data-idx="' + idx + '"></div>');
        $row.append(
            '<span class="timing-label text-muted">Break</span>' +
            '<input type="text" class="form-control form-control-sm slot-label" style="max-width:140px" value="' + (slot.label || 'Break') + '">' +
            '<input type="time" class="form-control form-control-sm slot-from" value="' + time12ToInput(slot.time_from) + '">' +
            '<span>–</span>' +
            '<input type="time" class="form-control form-control-sm slot-to" value="' + time12ToInput(slot.time_to) + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-slot ml-auto">&times; Remove</button>'
        );
        return $row;
    }

    function renderTimingsModal() {
        var $wrap = $('#timings-modal-rows').empty();
        var periodNum = 0;
        state.slots.forEach(function (slot, idx) {
            if (slot.type === 'break') {
                $wrap.append(buildBreakRow(slot, idx));
                return;
            }
            periodNum++;
            $wrap.append(buildPeriodRow(slot, idx, periodNum));
        });
        updateTimingsSummary();
    }

    function readSlotsFromModal() {
        var slots = [];
        $('#timings-modal-rows .timing-modal-row').each(function (i) {
            var isBreak = $(this).hasClass('break-row');
            slots.push({
                type: isBreak ? 'break' : 'period',
                label: $(this).find('.slot-label').val() || (isBreak ? 'Break' : ('Period ' + (i + 1))),
                time_from: formatTimeInput($(this).find('.slot-from').val()),
                time_to: formatTimeInput($(this).find('.slot-to').val()),
                sort_order: i
            });
        });
        return slots;
    }

    $('#btn-open-timings').on('click', function () {
        if (!state.slots.length) {
            buildSlotsFromServer(function () {
                renderTimingsModal();
                $('#timingsModal').modal('show');
            });
        } else {
            renderTimingsModal();
            $('#timingsModal').modal('show');
        }
    });

    $('#btn-regenerate-periods').on('click', function () {
        buildSlotsFromServer(function () { renderTimingsModal(); });
    });

    $(document).on('click', '.btn-add-break-after', function () {
        var fromRow = $(this).closest('.timing-modal-row');
        var fromVal = fromRow.find('.slot-to').val() || '10:30';
        var toParts = fromVal.split(':');
        var endH = parseInt(toParts[0], 10);
        var endM = parseInt(toParts[1] || 0, 10) + 15;
        if (endM >= 60) { endH++; endM -= 60; }
        var toVal = String(endH).padStart(2, '0') + ':' + String(endM).padStart(2, '0');
        fromRow.after(buildBreakRow({ label: 'Break', time_from: formatTimeInput(fromVal), time_to: formatTimeInput(toVal) }, -1));
        updateTimingsSummary();
    });

    $(document).on('click', '.btn-remove-slot', function () {
        $(this).closest('.timing-modal-row').remove();
        updateTimingsSummary();
    });

    $('#btn-save-timings').on('click', function () {
        state.slots = readSlotsFromModal();
        renderSlotsList();
        $('#timingsModal').modal('hide');
        flash({ msg: 'Timings saved.', type: 'success' });
    });

    function preloadPlansForSections(callback) {
        var ids = selectedSections();
        if (!ids.length) { if (callback) callback(); return; }
        var pending = ids.length;
        ids.forEach(function (sectionId) {
            if (state.plans[sectionId] && state.plans[sectionId].length) {
                if (--pending === 0 && callback) callback();
                return;
            }
            $.get(routes.subjects.replace('__ID__', sectionId), function (res) {
                if (res.ok && res.default_plan && res.default_plan.length) {
                    state.plans[sectionId] = res.default_plan;
                }
                if (--pending === 0 && callback) callback();
            });
        });
    }

    function renderSlotsList() {
        var $l = $('#custom-slots-list').empty();
        updateTimingsSummary();
        if (!state.slots.length) {
            $l.html('<span class="text-muted">Open Customize to set period timings.</span>');
            return;
        }
        state.slots.slice(0, 4).forEach(function (s) {
            var badge = s.type === 'break' ? 'secondary' : 'primary';
            $l.append('<div class="mb-1"><span class="badge badge-' + badge + '">' + (s.label || s.type) + '</span> ' + s.time_from + ' – ' + s.time_to + '</div>');
        });
        if (state.slots.length > 4) {
            $l.append('<div class="text-muted">+' + (state.slots.length - 4) + ' more…</div>');
        }
    }

    $('.day-toggle').on('click', function () {
        $(this).toggleClass('active');
    });

    $('#section-ids').on('change', function () {
        $('#btn-open-plan').prop('disabled', !selectedSections().length);
        preloadPlansForSections();
    });

    $('#btn-open-plan').on('click', function () {
        openPlanModal();
    });

    function openPlanModal() {
        var ids = selectedSections();
        var $tabs = $('#plan-tabs').empty();
        var $content = $('#plan-tab-content').empty();
        ids.forEach(function (sid, idx) {
            var label = $('#section-ids option[value="' + sid + '"]').text();
            $tabs.append('<li class="nav-item"><a class="nav-link ' + (idx === 0 ? 'active' : '') + '" data-toggle="tab" href="#plan-sec-' + sid + '">' + label + '</a></li>');
            var $pane = $('<div class="tab-pane fade ' + (idx === 0 ? 'show active' : '') + '" id="plan-sec-' + sid + '"></div>');
            $content.append($pane);
            loadPlanSection(sid, $pane);
        });
        $('#planModal').modal('show');
    }

    function loadPlanSection(sectionId, $pane) {
        $pane.html('<p class="text-muted">Loading subjects…</p>');
        var url = routes.subjects.replace('__ID__', sectionId);
        $.get(url, function (res) {
            if (!res.ok) return;
            window._planSubjectsCache = res.subjects;
            var rows = state.plans[sectionId] || [];
            var html = '<button type="button" class="btn btn-sm btn-outline-primary mb-2 btn-add-plan-row" data-section="' + sectionId + '"><i class="bi bi-plus"></i> Add subject row</button><div class="plan-rows" data-section="' + sectionId + '"></div>';
            $pane.html(html);
            var $rows = $pane.find('.plan-rows');
            $rows.data('subjects', res.subjects);
            if (rows.length) {
                rows.forEach(function (r) { addPlanRow(sectionId, $rows, null, r); });
            } else if (res.default_plan && res.default_plan.length) {
                res.default_plan.forEach(function (r) { addPlanRow(sectionId, $rows, null, r); });
            } else {
                res.subjects.forEach(function (s) {
                    addPlanRow(sectionId, $rows, s, {
                        subject_id: s.id,
                        teacher_id: s.default_teacher_id,
                        times_per_week: 5
                    });
                });
            }
            collectPlans();
            $pane.on('click', '.btn-add-plan-row', function () {
                addPlanRow(sectionId, $rows, res.subjects[0] || null);
            });
        });
    }

    function confirmRemovePlanRow($row) {
        var subName = $row.find('.plan-subject option:selected').text() || 'this subject';
        swal({
            title: 'Remove from plan?',
            text: 'Remove ' + subName + ' from the timetable plan?',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (ok) {
            if (ok) {
                $row.remove();
                collectPlans();
            }
        });
    }

    function addPlanRow(sectionId, $container, defaultSubject, existing) {
        var subjects = $container.data('subjects') || window._planSubjectsCache || [];
        var sid = existing ? existing.subject_id : (defaultSubject ? defaultSubject.id : '');
        var tid = existing ? existing.teacher_id : (defaultSubject ? (defaultSubject.default_teacher_id || '') : '');
        var times = existing ? existing.times_per_week : 5;
        var duration = existing && existing.duration ? existing.duration : 'single';
        var $row = $('<div class="plan-row"></div>');
        var subOpts = subjects.map(function (s) {
            return '<option value="' + s.id + '" ' + (String(s.id) === String(sid) ? 'selected' : '') + '>' + s.name + '</option>';
        }).join('');
        var timesOpts = '';
        for (var t = 1; t <= 10; t++) {
            timesOpts += '<option value="' + t + '" ' + (parseInt(times, 10) === t ? 'selected' : '') + '>' + t + '</option>';
        }
        $row.html(
            '<div class="form-row align-items-end">' +
            '<div class="col-md-3"><label class="small d-block mb-1">Teacher</label><select class="form-control form-control-sm plan-teacher"></select></div>' +
            '<div class="col-md-3"><label class="small d-block mb-1">Subject</label><select class="form-control form-control-sm plan-subject">' + subOpts + '</select></div>' +
            '<div class="col-md-2"><label class="small d-block mb-1">Times per week</label><select class="form-control form-control-sm plan-times">' + timesOpts + '</select></div>' +
            '<div class="col-md-3"><label class="small d-block mb-1">Duration</label><select class="form-control form-control-sm plan-duration">' +
            '<option value="single" ' + (duration === 'single' ? 'selected' : '') + '>Single Period</option>' +
            '<option value="double" ' + (duration === 'double' ? 'selected' : '') + '>Double Period</option>' +
            '</select></div>' +
            '<div class="col-md-1 text-right"><label class="small d-block mb-1">&nbsp;</label>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-plan-row" title="Remove row"><i class="icon-trash"></i></button></div></div>'
        );
        $container.append($row);
        var $sub = $row.find('.plan-subject');
        var $tch = $row.find('.plan-teacher');
        function fillTeachers() {
            var sub = subjects.find(function (s) { return String(s.id) === String($sub.val()); });
            $tch.empty();
            if (!sub || !sub.teachers.length) {
                $tch.append('<option value="">— No teachers in department —</option>');
                return;
            }
            var pickId = tid || sub.default_teacher_id || (sub.teachers[0] ? sub.teachers[0].id : '');
            sub.teachers.forEach(function (t) {
                $tch.append('<option value="' + t.id + '" ' + (String(t.id) === String(pickId) ? 'selected' : '') + '>' + t.name + '</option>');
            });
        }
        $sub.on('change', function () { tid = ''; fillTeachers(); });
        fillTeachers();
        $row.find('.btn-remove-plan-row').on('click', function () { confirmRemovePlanRow($row); });
        $sub.add($tch).add($row.find('.plan-times, .plan-duration')).on('change', collectPlans);
    }

    function collectPlans() {
        $('.plan-rows').each(function () {
            var sid = $(this).data('section');
            var items = [];
            $(this).find('.plan-row').each(function () {
                var sub = $(this).find('.plan-subject').val();
                var tch = $(this).find('.plan-teacher').val();
                var times = $(this).find('.plan-times').val();
                if (sub && tch) {
                    items.push({ subject_id: sub, teacher_id: tch, times_per_week: parseInt(times, 10) || 1, duration: $(this).find('.plan-duration').val() || 'single' });
                }
            });
            state.plans[sid] = items;
        });
    }

    function payload() {
        collectPlans();
        return {
            _token: csrf(),
            name: $('#plan-name').val(),
            section_ids: selectedSections(),
            days: selectedDays(),
            slots: state.slots,
            plans: state.plans
        };
    }

    function syncSlotsFromPreview() {
        if (state.preview && state.preview.sections.length && state.preview.sections[0].slots) {
            state.slots = state.preview.sections[0].slots;
            renderSlotsList();
            updateTimingsSummary();
        }
    }

    function renderPreview(data) {
        state.preview = data;
        state.navIndex = 0;
        state.saved = !!data.saved;
        state.customMode = 'edit';
        state.swapPick = null;
        if (data.plan_name) {
            $('#plan-name').val(data.plan_name);
        }
        syncSlotsFromPreview();
        $('#btn-save').prop('disabled', false);
        $('#preview-empty').hide();
        $('#preview-area').show();
        $('#customize-toolbar').show();
        setCustomMode('edit');
        $('#rate-text').text(data.placement_rate + '% Placement Rate');
        $('#rate-msg').text(data.message);
        $('#rate-progress').css('width', data.placement_rate + '%');
        renderNav();
    }

    function loadSavedTimetables(sectionIds, done) {
        if (!sectionIds || !sectionIds.length) {
            if (typeof done === 'function') { done(false); }
            return;
        }
        $.post(routes.loadSaved, { _token: csrf(), section_ids: sectionIds }, function (res) {
            if (res.ok && res.sections && res.sections.length) {
                renderPreview(res);
                flash({ msg: res.message, type: 'success' });
                if (typeof done === 'function') { done(true); }
            } else if (typeof done === 'function') {
                done(false);
            }
        }).fail(function () {
            if (typeof done === 'function') { done(false); }
        });
    }

    function renderNav() {
        if (!state.preview || !state.preview.sections.length) return;
        var sec = state.preview.sections[state.navIndex];
        $('#nav-label').text(sec.label);
        $('#nav-counter').text((state.navIndex + 1) + ' of ' + state.preview.sections.length);
        var $g = $('<table class="auto-tt-preview-grid"><thead><tr><th class="period-col">Period</th></tr></thead><tbody></tbody></table>');
        sec.days.forEach(function (d) { $g.find('thead tr').append('<th>' + d + '</th>'); });
        sec.slots.forEach(function (slot, si) {
            var $tr = $('<tr></tr>');
            $tr.append('<td class="period-col"><strong>' + (slot.label || '') + '</strong><br><small>' + slot.time_from + ' – ' + slot.time_to + '</small></td>');
            sec.days.forEach(function (day) {
                var cell = sec.grid[day][si];
                var $td = $('<td></td>');
                if (slot.type === 'break') {
                    $td.html('<div class="auto-tt-cell-break">' + (slot.label || 'Break') + '</div>');
                } else if (cell) {
                    var $card = $('<div class="auto-tt-cell-card"></div>').css('background', cell.color || '#e8eaf6');
                    $card.append('<span class="subj">' + cell.subject_name + '</span><span class="tchr">' + cell.teacher_name + '</span>');
                    $card.attr('data-day', day).attr('data-slot-index', si).attr('data-section-idx', state.navIndex);
                    $card.data({ day: day, slotIndex: si, sectionIdx: state.navIndex });
                    if (state.customMode !== 'view') { $card.addClass('auto-tt-editable'); }
                    $td.append($card);
                } else {
                    var $empty = $('<div class="auto-tt-cell-empty"></div>');
                    $empty.attr('data-day', day).attr('data-slot-index', si).attr('data-section-idx', state.navIndex);
                    $empty.data({ day: day, slotIndex: si, sectionIdx: state.navIndex });
                    if (state.customMode !== 'view') { $empty.addClass('auto-tt-editable'); }
                    $td.append($empty);
                }
                $tr.append($td);
            });
            $g.find('tbody').append($tr);
        });
        $('#grid-container').html($g);
    }

    $('#nav-prev').on('click', function () {
        if (state.navIndex > 0) { state.navIndex--; renderNav(); }
    });
    $('#nav-next').on('click', function () {
        if (state.preview && state.navIndex < state.preview.sections.length - 1) { state.navIndex++; renderNav(); }
    });

    function runGenerate() {
        $.post(routes.generate, payload(), function (res) {
            if (res.ok || (res.sections && res.sections.length)) {
                renderPreview(res);
                flash({ msg: res.message, type: res.placement_rate >= 80 ? 'success' : 'warning' });
            } else {
                flash({ msg: res.message || 'Could not generate.', type: 'danger' });
            }
        });
    }

    function runGenerateFlow() {
        preloadPlansForSections(function () {
            collectPlans();
            runGenerate();
        });
    }

    $('#btn-preview').on('click', function () {
        if (!selectedSections().length) { flash({ msg: 'Select at least one section.', type: 'warning' }); return; }
        var go = function () {
            if (state.saved && state.preview && state.preview.sections.length) {
                if (!window.confirm('Generate a new timetable? This replaces the current saved schedule for the selected sections.')) {
                    return;
                }
            }
            runGenerateFlow();
        };
        if (!state.slots.length) {
            buildSlotsFromServer(go);
        } else {
            go();
        }
    });

    function previewSavePayload() {
        return {
            _token: csrf(),
            name: $('#plan-name').val(),
            sections: state.preview.sections.map(function (sec) {
                return {
                    section_id: sec.section_id,
                    days: sec.days,
                    slots: sec.slots,
                    grid: sec.grid
                };
            })
        };
    }

    function setCustomMode(mode) {
        state.customMode = mode;
        state.swapPick = null;
        $('#customize-toolbar [data-mode]').removeClass('active btn-primary').addClass('btn-outline-primary');
        if (mode === 'edit') {
            $('#btn-mode-edit').addClass('active btn-primary').removeClass('btn-outline-primary');
            $('#customize-hint').text('Click a period to add, change, or remove a subject (Clear in editor).');
        } else if (mode === 'swap') {
            $('#btn-mode-swap').addClass('active btn-primary').removeClass('btn-outline-primary');
            $('#customize-hint').text('Click two periods to exchange them.');
        } else {
            $('#btn-mode-view').addClass('active');
            $('#customize-hint').text('Customization paused. Choose Edit or Swap to continue.');
        }
        if (state.preview) { renderNav(); }
    }

    $('#customize-toolbar').on('click', '[data-mode]', function () {
        setCustomMode($(this).data('mode'));
    });

    function applyCellLocal(sectionIdx, day, slotIndex, cell) {
        var sec = state.preview.sections[sectionIdx];
        sec.grid[day][slotIndex] = cell;
        renderNav();
    }

    function swapCellsLocal(sectionIdx, dayA, slotA, dayB, slotB) {
        var sec = state.preview.sections[sectionIdx];
        var a = sec.grid[dayA][slotA];
        var b = sec.grid[dayB][slotB];
        sec.grid[dayA][slotA] = b;
        sec.grid[dayB][slotB] = a;
        renderNav();
    }

    $('#btn-save').on('click', function () {
        if (!state.preview || !state.preview.sections.length) {
            flash({ msg: 'Generate a timetable first.', type: 'warning' });
            return;
        }
        $.post(routes.savePreview, previewSavePayload(), function (res) {
            if (res.ok) {
                state.preview.sections = res.sections;
                state.saved = true;
                syncSlotsFromPreview();
                renderNav();
                flash({ msg: res.message, type: 'success' });
            } else {
                flash({ msg: res.message || 'Could not save.', type: 'danger' });
            }
        });
    });

    function cellClickData($el) {
        var d = $el.data();
        var sectionIdx = $el.attr('data-section-idx');
        var slotIndex = $el.attr('data-slot-index');
        return {
            sectionIdx: sectionIdx != null ? parseInt(sectionIdx, 10) : (d.sectionIdx != null ? d.sectionIdx : d.sectionidx),
            day: $el.attr('data-day') || d.day,
            slotIndex: slotIndex != null ? parseInt(slotIndex, 10) : (d.slotIndex != null ? d.slotIndex : d.slotindex)
        };
    }

    $(document).on('click', '.auto-tt-cell-card.auto-tt-editable, .auto-tt-cell-empty.auto-tt-editable', function () {
        if (!state.preview || state.customMode === 'view') { return; }
        var d = cellClickData($(this));
        if (d.sectionIdx == null || !d.day || d.slotIndex == null) { return; }
        if (state.customMode === 'swap') {
            if (!state.swapPick) {
                state.swapPick = d;
                $(this).addClass('swap-pick');
                flash({ msg: 'Now click the second period to swap.', type: 'info' });
                return;
            }
            if (state.swapPick.day === d.day && state.swapPick.slotIndex === d.slotIndex) {
                $(this).removeClass('swap-pick');
                state.swapPick = null;
                return;
            }
            var sec = state.preview.sections[d.sectionIdx];
            var pick = state.swapPick;
            state.swapPick = null;
            $('.swap-pick').removeClass('swap-pick');
            if (sec.ttr_id) {
                $.post(routes.swapCells, {
                    _token: csrf(),
                    ttr_id: sec.ttr_id,
                    day_a: pick.day,
                    slot_a: pick.slotIndex,
                    day_b: d.day,
                    slot_b: d.slotIndex
                }, function (res) {
                    if (res.ok) {
                        sec.grid[pick.day][pick.slotIndex] = res.cell_a;
                        sec.grid[d.day][d.slotIndex] = res.cell_b;
                        renderNav();
                        flash({ msg: res.message, type: 'success' });
                    } else {
                        flash({ msg: res.message, type: 'danger' });
                    }
                });
            } else {
                swapCellsLocal(d.sectionIdx, pick.day, pick.slotIndex, d.day, d.slotIndex);
                flash({ msg: 'Periods exchanged.', type: 'success' });
            }
            return;
        }
        openCellEditor(d.sectionIdx, d.day, d.slotIndex, $(this).hasClass('auto-tt-cell-card') ? $(this) : null);
    });

    function openCellEditor(sectionIdx, day, slotIndex, $card) {
        var sec = state.preview.sections[sectionIdx];
        $('#cell-day').val(day);
        $('#cell-slot-index').val(slotIndex);
        $('#cell-section-idx').val(sectionIdx);
        var url = routes.subjects.replace('__ID__', sec.section_id);
        $.get(url, function (res) {
            var $sub = $('#cell-subject').empty().append('<option value="">— Clear —</option>');
            res.subjects.forEach(function (s) {
                $sub.append('<option value="' + s.id + '">' + s.name + '</option>');
            });
            var currentTeacherId = null;
            var cell = sec.grid[day] ? sec.grid[day][slotIndex] : null;
            if (cell) {
                if (cell.teacher_id) {
                    currentTeacherId = cell.teacher_id;
                }
                if (cell.subject_id) {
                    $sub.val(String(cell.subject_id));
                } else if ($card) {
                    var txt = $card.find('.subj').text();
                    $sub.find('option').filter(function () { return $(this).text() === txt; }).prop('selected', true);
                }
            }
            function fillTeachersForSubject() {
                var subjectId = $('#cell-subject').val();
                var $t = $('#cell-teacher').empty();
                if (!subjectId) {
                    $t.append('<option value="">— Select subject first —</option>');
                    return;
                }
                var s = res.subjects.find(function (x) { return String(x.id) === String(subjectId); });
                if (!s || !s.teachers || !s.teachers.length) {
                    $t.append('<option value="">— No teachers in department —</option>');
                    return;
                }
                var pickId = currentTeacherId || s.default_teacher_id || (s.teachers[0] ? s.teachers[0].id : '');
                s.teachers.forEach(function (t) {
                    $t.append('<option value="' + t.id + '"' + (String(t.id) === String(pickId) ? ' selected' : '') + '>' + t.name + '</option>');
                });
                currentTeacherId = null;
            }
            $('#cell-subject').off('change').on('change', fillTeachersForSubject);
            fillTeachersForSubject();
            $('#cellModal').modal('show');
        });
    }

    $('#cell-save').on('click', function () {
        var sectionIdx = parseInt($('#cell-section-idx').val(), 10);
        var sec = state.preview.sections[sectionIdx];
        var day = $('#cell-day').val();
        var slotIndex = parseInt($('#cell-slot-index').val(), 10);
        var subjectId = $('#cell-subject').val() || null;
        var teacherId = $('#cell-teacher').val() || null;

        function finish(cell) {
            applyCellLocal(sectionIdx, day, slotIndex, cell);
            $('#cellModal').modal('hide');
            flash({ msg: cell ? 'Period updated.' : 'Period cleared.', type: 'success' });
        }

        if (!subjectId || !teacherId) {
            if (sec.ttr_id) {
                $.post(routes.updateCell, {
                    _token: csrf(),
                    ttr_id: sec.ttr_id,
                    day: day,
                    slot_index: slotIndex,
                    subject_id: null,
                    teacher_id: null,
                    slots: state.slots
                }, function (res) {
                    if (res.ok) { finish(null); }
                    else { flash({ msg: res.message, type: 'danger' }); }
                });
            } else {
                finish(null);
            }
            return;
        }

        if (!sec.ttr_id) {
            var subName = $('#cell-subject option:selected').text();
            var tchName = $('#cell-teacher option:selected').text();
            finish({
                subject_id: parseInt(subjectId, 10),
                teacher_id: parseInt(teacherId, 10),
                subject_name: subName,
                teacher_name: tchName,
                color: '#e8eaf6'
            });
            return;
        }

        $.post(routes.updateCell, {
            _token: csrf(),
            ttr_id: sec.ttr_id,
            day: day,
            slot_index: slotIndex,
            subject_id: subjectId,
            teacher_id: teacherId,
            slots: state.slots
        }, function (res) {
            if (res.ok) {
                var cell = res.cell ? {
                    subject_id: res.cell.subject_id,
                    teacher_id: res.cell.teacher_id,
                    subject_name: res.cell.subject_name,
                    teacher_name: res.cell.teacher_name,
                    color: '#e8eaf6'
                } : null;
                finish(cell);
            } else {
                flash({ msg: res.message, type: 'danger' });
            }
        });
    });

    $('#cell-clear').on('click', function () {
        $('#cell-subject').val('');
        $('#cell-teacher').val('');
        $('#cell-save').click();
    });

    buildSlotsFromServer(function () {
        if (savedSectionIds.length) {
            setSectionsChecked(savedSectionIds.map(String));
            savedSectionIds.forEach(function (id) {
                $('.section-cb[value="' + id + '"]').closest('.class-picker-block').addClass('open');
            });
            loadSavedTimetables(savedSectionIds);
        }
    });
})();
</script>
@endsection
