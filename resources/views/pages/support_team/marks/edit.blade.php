@php
    $hasComponents = isset($assessment_components) && $assessment_components->isNotEmpty();
    $compTotal     = $hasComponents ? $assessment_components->sum('max_mark') : 0;
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     ASSESSMENT BREAKDOWN PANEL
     ══════════════════════════════════════════════════════════════════════ --}}
<div id="assessment-setup-card" style="
    border-radius: 10px;
    border: 1.5px solid {{ $hasComponents ? '#28a745' : '#e0a800' }};
    background: #fff;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
">

    {{-- ── Header — always visible ──────────────────────────────────────── --}}
    <div style="
        background: {{ $hasComponents ? 'linear-gradient(135deg,#28a745,#20c997)' : 'linear-gradient(135deg,#ffc107,#fd7e14)' }};
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    ">
        {{-- Left: icon + title + description --}}
        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
            <div style="
                background: rgba(255,255,255,.25);
                border-radius: 8px;
                width: 40px; height: 40px;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            ">
                <i class="bi bi-{{ $hasComponents ? 'check2-circle' : 'sliders' }}" style="font-size:20px;color:#fff;"></i>
            </div>
            <div style="min-width:0;">
                <div style="color:#fff;font-weight:700;font-size:14px;line-height:1.2;">
                    @if($hasComponents)
                        Assessment Breakdown Active
                    @else
                        Customise the Assessment (30 marks)
                    @endif
                </div>
                <div style="color:rgba(255,255,255,.85);font-size:12px;margin-top:2px;line-height:1.4;">
                    @if($hasComponents)
                        The 30-mark assessment is split into:
                        @foreach($assessment_components as $i => $comp)
                            <strong style="color:#fff;">{{ $comp->name }}</strong> ({{ $comp->max_mark }}){{ !$loop->last ? ' · ' : '' }}
                        @endforeach
                        — Total: <strong style="color:#fff;">{{ $compTotal }}/30</strong>
                    @else
                        The assessment column is currently a single 0–30 input.
                        You can split it into named parts — e.g. <strong style="color:#fff;">Test (15) + Quiz (10) + Homework (5)</strong> — that must add up to 30.
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: action buttons --}}
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
            @if($hasComponents)
                <button type="button" id="clear-components-btn"
                    style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);color:#fff;border-radius:6px;padding:5px 12px;font-size:12px;cursor:pointer;white-space:nowrap;">
                    <i class="bi bi-x-circle mr-1"></i>Remove Breakdown
                </button>
            @endif
            <button type="button" id="toggle-setup-btn"
                style="background:#fff;border:none;border-radius:6px;padding:5px 14px;font-size:12px;font-weight:600;cursor:pointer;color:{{ $hasComponents ? '#28a745' : '#856404' }};white-space:nowrap;">
                <i class="bi bi-{{ $hasComponents ? 'pencil-square' : 'plus-circle' }} mr-1"></i>
                {{ $hasComponents ? 'Edit Breakdown' : 'Set Up Breakdown' }}
            </button>
        </div>
    </div>

    {{-- ── Expandable builder body ────────────────────────────────────────── --}}
    <div id="setup-body" class="{{ $hasComponents ? 'd-none' : 'd-none' }}" style="padding:20px;">

        {{-- Explainer --}}
        <div style="
            background: #fffbea;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #5a4000;
            line-height: 1.6;
        ">
            <i class="bi bi-lightbulb-fill mr-1" style="color:#ffc107;"></i>
            <strong>How this works:</strong>
            Add one row per assessment type below (e.g. Test, Quiz, Homework, Class Work).
            Set the max mark for each. <strong>All max marks must add up to exactly 30.</strong>
            Once saved, the marks table will show a separate column for each component instead of one combined input.
            The system automatically sums them into the Assessment total.
        </div>

        {{-- Component builder table --}}
        <table class="table table-sm mb-0" id="comp-table" style="border-collapse:separate;border-spacing:0 4px;">
            <thead>
                <tr style="font-size:12px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">
                    <th style="padding:4px 8px;border:none;width:55%;">Component Name</th>
                    <th style="padding:4px 8px;border:none;width:30%;">Max Mark <span style="font-weight:400;">(must total 30)</span></th>
                    <th style="padding:4px 8px;border:none;width:15%;"></th>
                </tr>
            </thead>
            <tbody id="comp-rows">
                @if($hasComponents)
                    @foreach($assessment_components as $comp)
                    <tr class="comp-row">
                        <td style="padding:4px 6px;border:none;">
                            <input type="text" class="form-control form-control-sm comp-name"
                                   value="{{ $comp->name }}" placeholder="e.g. Test"
                                   style="border-radius:6px;" required>
                        </td>
                        <td style="padding:4px 6px;border:none;">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control comp-max"
                                       value="{{ $comp->max_mark }}" min="1" max="30"
                                       style="border-radius:6px 0 0 6px;" required>
                                <div class="input-group-append">
                                    <span class="input-group-text" style="border-radius:0 6px 6px 0;font-size:11px;">/ 30</span>
                                </div>
                            </div>
                        </td>
                        <td style="padding:4px 6px;border:none;text-align:center;">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-comp-row"
                                    style="border-radius:6px;padding:3px 8px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr class="comp-row">
                        <td style="padding:4px 6px;border:none;">
                            <input type="text" class="form-control form-control-sm comp-name"
                                   placeholder="e.g. Test" style="border-radius:6px;" required>
                        </td>
                        <td style="padding:4px 6px;border:none;">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control comp-max"
                                       placeholder="e.g. 15" min="1" max="30"
                                       style="border-radius:6px 0 0 6px;" required>
                                <div class="input-group-append">
                                    <span class="input-group-text" style="border-radius:0 6px 6px 0;font-size:11px;">/ 30</span>
                                </div>
                            </div>
                        </td>
                        <td style="padding:4px 6px;border:none;text-align:center;">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-comp-row"
                                    style="border-radius:6px;padding:3px 8px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Footer: add row + total indicator + save --}}
        <div style="
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        ">
            <button type="button" id="add-comp-row"
                style="background:#f8f9fa;border:1.5px dashed #adb5bd;color:#495057;border-radius:6px;padding:5px 14px;font-size:12px;cursor:pointer;">
                <i class="bi bi-plus mr-1"></i>Add Component
            </button>

            {{-- Live total pill --}}
            <div id="total-pill" style="
                display: flex;
                align-items: center;
                gap: 6px;
                background: #f8f9fa;
                border: 1.5px solid #dee2e6;
                border-radius: 20px;
                padding: 4px 14px;
                font-size: 13px;
            ">
                <span style="color:#6c757d;">Total:</span>
                <strong id="comp-total-display" style="color:#dc3545;">0</strong>
                <span style="color:#6c757d;">/ 30</span>
                <span id="total-check" style="display:none;color:#28a745;font-size:16px;">✓</span>
            </div>

            <div style="margin-left:auto;display:flex;gap:8px;">
                <button type="button" id="cancel-setup-btn"
                    style="background:#f8f9fa;border:1px solid #dee2e6;color:#6c757d;border-radius:6px;padding:6px 16px;font-size:13px;cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="save-components-btn"
                    style="background:linear-gradient(135deg,#28a745,#20c997);border:none;color:#fff;border-radius:6px;padding:6px 20px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="bi bi-save mr-1"></i>Save Breakdown
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MARKS TABLE
     ══════════════════════════════════════════════════════════════════════ --}}
<form class="ajax-update" action="{{ route('marks.update', [$exam_id, $my_class_id, $section_id, $subject_id]) }}" method="post">
    @csrf @method('put')
    <div style="overflow-x:auto;">
    <table class="table table-striped table-sm" style="min-width:600px;">
        <thead class="thead-light">
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>ADM No</th>
            @if($hasComponents)
                @foreach($assessment_components as $comp)
                    <th style="background:#fff8e1;color:#856404;">
                        <div>{{ $comp->name }}</div>
                        <small class="text-muted font-weight-normal">max {{ $comp->max_mark }}</small>
                    </th>
                @endforeach
                <th style="background:#e8f5e9;color:#1b5e20;">
                    <div>Assessment</div>
                    <small class="font-weight-normal">total / 30</small>
                </th>
            @else
                <th>
                    <div>Assessment</div>
                    <small class="text-muted font-weight-normal">max 30</small>
                </th>
            @endif
            <th>
                <div>Mid Exam</div>
                <small class="text-muted font-weight-normal">max 20</small>
            </th>
            <th>
                <div>Final Exam</div>
                <small class="text-muted font-weight-normal">max 50</small>
            </th>
            <th>AI Comment</th>
        </tr>
        </thead>
        <tbody>
        @foreach($marks->sortBy('user.name') as $mk)
            <tr data-mark-id="{{ $mk->id }}"
                data-student-name="{{ $mk->user->name }}"
                data-subject="{{ $m->subject->name }}">
                <td class="text-muted">{{ $loop->iteration }}</td>
                <td class="font-weight-bold">{{ $mk->user->name }}</td>
                <td><span class="badge badge-light border">{{ $mk->user->student_record->adm_no }}</span></td>

                @if($hasComponents)
                    @foreach($assessment_components as $comp)
                    <td>
                        <input type="number"
                               name="comp_{{ $comp->id }}_{{ $mk->id }}"
                               class="form-control form-control-sm text-center comp-student-input"
                               data-max="{{ $comp->max_mark }}"
                               min="0" max="{{ $comp->max_mark }}"
                               value="0"
                               title="{{ $comp->name }} (max {{ $comp->max_mark }})"
                               style="width:68px;border-color:#ffe082;">
                    </td>
                    @endforeach
                    <td>
                        <input type="text"
                               class="form-control form-control-sm text-center font-weight-bold assessment-total-display"
                               value="{{ (int)($mk->t1 ?? 0) }}"
                               readonly
                               style="width:60px;background:#e8f5e9;border-color:#a5d6a7;color:#1b5e20;">
                    </td>
                @else
                    <td>
                        <input type="number" min="0" max="30"
                               class="form-control form-control-sm text-center assessment-input"
                               name="t1_{{ $mk->id }}" value="{{ $mk->t1 }}"
                               title="Assessment (max 30)"
                               style="width:68px;">
                    </td>
                @endif

                <td>
                    <input type="number" min="0" max="20"
                           class="form-control form-control-sm text-center mid-exam-input"
                           name="t2_{{ $mk->id }}" value="{{ $mk->t2 }}"
                           title="Mid Exam (max 20)"
                           style="width:68px;">
                </td>
                <td>
                    <input type="number" min="0" max="50"
                           class="form-control form-control-sm text-center final-exam-input"
                           name="exm_{{ $mk->id }}" value="{{ $mk->exm }}"
                           title="Final Exam (max 50)"
                           style="width:68px;">
                </td>
                <td style="min-width:260px;">
                    <div class="d-flex" style="gap:6px;">
                        <textarea class="form-control form-control-sm comment-input"
                                  name="t_comment_{{ $mk->id }}"
                                  rows="2" style="font-size:12px;resize:none;"
                                  placeholder="Teacher comment...">{{ $mk->examRecord->t_comment ?? '' }}</textarea>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary generate-comment-btn flex-shrink-0"
                                title="AI generates evidence-based feedback from score patterns. Review before saving."
                                data-toggle="tooltip"
                                style="white-space:nowrap;align-self:flex-start;">
                            <i class="bi bi-stars"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>

    <div class="text-center mt-3">
        <button type="submit" class="btn btn-primary px-5">
            <i class="icon-paperplane mr-2"></i>Update Marks
        </button>
    </div>
</form>

{{-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ══════════════════════════════════════════════════════════════════════ --}}
<script>
(function ($) {

    $('[data-toggle="tooltip"]').tooltip();

    // ── Toggle builder open/close ────────────────────────────────────────────
    function openBuilder() {
        $('#setup-body').removeClass('d-none');
        $('#toggle-setup-btn').html('<i class="bi bi-x-lg mr-1"></i>Cancel');
    }
    function closeBuilder() {
        $('#setup-body').addClass('d-none');
        $('#toggle-setup-btn').html(
            @if($hasComponents)
                '<i class="bi bi-pencil-square mr-1"></i>Edit Breakdown'
            @else
                '<i class="bi bi-plus-circle mr-1"></i>Set Up Breakdown'
            @endif
        );
    }

    $('#toggle-setup-btn').on('click', function () {
        if ($('#setup-body').hasClass('d-none')) openBuilder(); else closeBuilder();
    });
    $('#cancel-setup-btn').on('click', closeBuilder);

    // ── Live total counter ───────────────────────────────────────────────────
    function updateTotal() {
        var total = 0;
        $('.comp-max').each(function () { total += parseInt($(this).val()) || 0; });

        $('#comp-total-display').text(total)
            .css('color', total === 30 ? '#28a745' : '#dc3545');

        $('#total-check').toggle(total === 30);

        $('#total-pill').css('border-color', total === 30 ? '#28a745' : '#dee2e6');

        $('#save-components-btn')
            .css('opacity', total === 30 ? '1' : '0.5')
            .css('cursor',  total === 30 ? 'pointer' : 'not-allowed');
    }

    $(document).on('input', '.comp-max', updateTotal);
    updateTotal();

    // ── Add / remove rows ────────────────────────────────────────────────────
    $('#add-comp-row').on('click', function () {
        var row = '<tr class="comp-row">' +
            '<td style="padding:4px 6px;border:none;">' +
                '<input type="text" class="form-control form-control-sm comp-name" placeholder="e.g. Class Work" style="border-radius:6px;" required>' +
            '</td>' +
            '<td style="padding:4px 6px;border:none;">' +
                '<div class="input-group input-group-sm">' +
                    '<input type="number" class="form-control comp-max" placeholder="e.g. 5" min="1" max="30" style="border-radius:6px 0 0 6px;" required>' +
                    '<div class="input-group-append"><span class="input-group-text" style="border-radius:0 6px 6px 0;font-size:11px;">/ 30</span></div>' +
                '</div>' +
            '</td>' +
            '<td style="padding:4px 6px;border:none;text-align:center;">' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-comp-row" style="border-radius:6px;padding:3px 8px;"><i class="bi bi-trash"></i></button>' +
            '</td>' +
        '</tr>';
        $('#comp-rows').append(row);
        updateTotal();
    });

    $(document).on('click', '.remove-comp-row', function () {
        if ($('.comp-row').length > 1) {
            $(this).closest('tr').remove();
            updateTotal();
        } else {
            flash({ msg: 'You need at least one component.', type: 'warning' });
        }
    });

    // ── Save components ──────────────────────────────────────────────────────
    $('#save-components-btn').on('click', function () {
        var components = [];
        var valid = true;

        $('.comp-row').each(function () {
            var name = $(this).find('.comp-name').val().trim();
            var max  = parseInt($(this).find('.comp-max').val()) || 0;
            if (!name || max < 1) { valid = false; return false; }
            components.push({ name: name, max_mark: max });
        });

        if (!valid) {
            flash({ msg: 'Every row needs a name and a mark greater than 0.', type: 'danger' });
            return;
        }

        var total = components.reduce(function (s, c) { return s + c.max_mark; }, 0);
        if (total !== 30) {
            flash({ msg: 'Marks must total exactly 30. Current total: ' + total + '.', type: 'danger' });
            return;
        }

        var $btn = $(this).prop('disabled', true)
            .html('<i class="bi bi-hourglass-split mr-1"></i>Saving…');

        $.ajax({
            url:    '{{ route("marks.components.save", [$exam_id, $my_class_id, $subject_id]) }}',
            method: 'POST',
            data:   { _token: '{{ csrf_token() }}', components: components },
            success: function (resp) {
                if (resp.ok) {
                    flash({ msg: resp.msg + ' Reloading…', type: 'success' });
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    flash({ msg: resp.msg, type: 'danger' });
                    $btn.prop('disabled', false).html('<i class="bi bi-save mr-1"></i>Save Breakdown');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Save failed.';
                flash({ msg: msg, type: 'danger' });
                $btn.prop('disabled', false).html('<i class="bi bi-save mr-1"></i>Save Breakdown');
            }
        });
    });

    // ── Clear components ─────────────────────────────────────────────────────
    $('#clear-components-btn').on('click', function () {
        if (!confirm('Remove the breakdown and go back to a single Assessment (30) input?')) return;
        $.ajax({
            url:    '{{ route("marks.components.clear", [$exam_id, $my_class_id, $subject_id]) }}',
            method: 'POST',
            data:   { _token: '{{ csrf_token() }}', _method: 'DELETE' },
            success: function (resp) {
                if (resp.ok) {
                    flash({ msg: resp.msg + ' Reloading…', type: 'success' });
                    setTimeout(function () { location.reload(); }, 1200);
                }
            }
        });
    });

    // ── Auto-sum sub-component inputs per student row ────────────────────────
    @if($hasComponents)
    $(document).on('input', '.comp-student-input', function () {
        var $row  = $(this).closest('tr');
        var total = 0;
        $row.find('.comp-student-input').each(function () {
            total += parseInt($(this).val()) || 0;
        });
        var $disp = $row.find('.assessment-total-display');
        $disp.val(total)
             .css('color',        total > 30 ? '#dc3545' : '#1b5e20')
             .css('border-color', total > 30 ? '#f5c6cb' : '#a5d6a7');
    });
    @endif

    // ── AI Comment generation ────────────────────────────────────────────────
    $(document).on('click', '.generate-comment-btn', function () {
        var $btn     = $(this);
        var $row     = $btn.closest('tr');
        var $comment = $row.find('.comment-input');

        var studentName = $row.data('student-name');
        var subject     = $row.data('subject');
        var midExam     = $row.find('.mid-exam-input').val()   || 0;
        var finalExam   = $row.find('.final-exam-input').val() || 0;
        var assessment  = 0;

        @if($hasComponents)
            $row.find('.comp-student-input').each(function () {
                assessment += parseInt($(this).val()) || 0;
            });
        @else
            assessment = $row.find('.assessment-input').val() || 0;
        @endif

        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');

        $.ajax({
            url:    '{{ route("ai.generate_comment") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                student_name: studentName,
                subject:      subject,
                assessment:   assessment,
                mid_exam:     midExam,
                final_exam:   finalExam,
            },
            success: function (resp) {
                $comment.val(resp.comment);
                $btn.prop('disabled', false).html('<i class="bi bi-stars"></i>');
            },
            error: function () {
                flash({ msg: 'AI comment generation failed. Please type manually.', type: 'warning' });
                $btn.prop('disabled', false).html('<i class="bi bi-stars"></i>');
            }
        });
    });

}(jQuery));
</script>
