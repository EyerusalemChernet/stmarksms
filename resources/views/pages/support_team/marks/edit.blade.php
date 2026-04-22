<form class="ajax-update" action="{{ route('marks.update', [$exam_id, $my_class_id, $section_id, $subject_id]) }}" method="post">
    @csrf @method('put')
    <table class="table table-striped table-sm">
        <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>ADM No</th>
            <th>Assessment (30)</th>
            <th>Mid Exam (20)</th>
            <th>Final Exam (50)</th>
            <th>AI Comment</th>
        </tr>
        </thead>
        <tbody>
        @foreach($marks->sortBy('user.name') as $mk)
            <tr data-mark-id="{{ $mk->id }}"
                data-student-name="{{ $mk->user->name }}"
                data-subject="{{ $m->subject->name }}">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $mk->user->name }}</td>
                <td>{{ $mk->user->student_record->adm_no }}</td>

                {{-- Assessment (t1, max 30) --}}
                <td>
                    <input title="Assessment (max 30)" min="0" max="30"
                           class="text-center form-control form-control-sm assessment-input"
                           name="t1_{{ $mk->id }}" value="{{ $mk->t1 }}" type="number"
                           style="width:70px;">
                </td>
                {{-- Mid Exam (t2, max 20) --}}
                <td>
                    <input title="Mid Exam (max 20)" min="0" max="20"
                           class="text-center form-control form-control-sm mid-exam-input"
                           name="t2_{{ $mk->id }}" value="{{ $mk->t2 }}" type="number"
                           style="width:70px;">
                </td>
                {{-- Final Exam (exm, max 50) --}}
                <td>
                    <input title="Final Exam (max 50)" min="0" max="50"
                           class="text-center form-control form-control-sm final-exam-input"
                           name="exm_{{ $mk->id }}" value="{{ $mk->exm }}" type="number"
                           style="width:70px;">
                </td>
                {{-- AI Comment --}}
                <td style="min-width:260px;">
                    <div class="d-flex" style="gap:6px;">
                        <textarea class="form-control form-control-sm comment-input"
                                  name="t_comment_{{ $mk->id }}"
                                  rows="2" style="font-size:12px;resize:none;"
                                  placeholder="Teacher comment...">{{ $mk->examRecord->t_comment ?? '' }}</textarea>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary generate-comment-btn flex-shrink-0"
                                title="AI analyses score patterns to suggest evidence-based feedback. Review and personalise before saving."
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

    <div class="text-center mt-2">
        <button type="submit" class="btn btn-primary">
            Update Marks <i class="icon-paperplane ml-2"></i>
        </button>
    </div>
</form>

<script>
$(function () {

    // Initialise Bootstrap tooltips
    $('[data-toggle="tooltip"]').tooltip();

    $(document).on('click', '.generate-comment-btn', function () {
        var $btn  = $(this);
        var $row  = $btn.closest('tr');
        var $comment = $row.find('.comment-input');

        var studentName = $row.data('student-name');
        var subject     = $row.data('subject');
        var assessment  = $row.find('.assessment-input').val() || 0;
        var midExam     = $row.find('.mid-exam-input').val()   || 0;
        var finalExam   = $row.find('.final-exam-input').val() || 0;

        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');

        $.ajax({
            url:    '{{ route("ai.generate_comment") }}',
            method: 'POST',
            data: {
                _token:       '{{ csrf_token() }}',
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

});
</script>
