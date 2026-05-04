@extends('layouts.master')
@section('page_title', 'Calendar Rules Engine')
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-sliders mr-2"></i>Calendar Rules Engine</h6>
        <div style="gap:8px;display:flex;">
            <a href="{{ route('acal.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left mr-1"></i>Back
            </a>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#rule-modal">
                <i class="bi bi-plus-lg mr-1"></i>Add Rule
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>#</th><th>Rule Name</th><th>Type</th><th>Event Type</th><th>Rule Value</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @foreach($rules as $r)
            <tr>
                <td>{{ $r->sort_order }}</td>
                <td>
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $r->color }};margin-right:6px;"></span>
                    <strong>{{ $r->name }}</strong>
                    @if($r->description)<br><small class="text-muted">{{ $r->description }}</small>@endif
                </td>
                <td><code style="font-size:11px;">{{ $r->rule_type }}</code></td>
                <td><span class="badge badge-light">{{ ucfirst($r->event_type) }}</span></td>
                <td><code style="font-size:11px;">{{ json_encode($r->rule_value) }}</code></td>
                <td>
                    @if($r->is_active)
                        <span class="badge badge-success">Yes</span>
                    @else
                        <span class="badge badge-secondary">No</span>
                    @endif
                </td>
                <td>
                    <button class="btn btn-xs btn-warning btn-edit-rule"
                            data-id="{{ $r->id }}"
                            data-name="{{ $r->name }}"
                            data-rule_type="{{ $r->rule_type }}"
                            data-event_type="{{ $r->event_type }}"
                            data-rule_value="{{ json_encode($r->rule_value) }}"
                            data-color="{{ $r->color }}"
                            data-description="{{ $r->description }}"
                            data-sort_order="{{ $r->sort_order }}"
                            data-is_active="{{ $r->is_active ? 1 : 0 }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-danger btn-delete-rule" data-id="{{ $r->id }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Rule Modal --}}
<div class="modal fade" id="rule-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#4f46e5;color:#fff;">
                <h5 class="modal-title" id="rule-modal-title">Add Rule</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="rule-form">
                <input type="hidden" id="rule-id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Rule Name <span class="text-danger">*</span></label>
                                <input type="text" id="r-name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" id="r-sort" class="form-control" value="99">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Rule Type <span class="text-danger">*</span></label>
                                <select id="r-type" class="form-control" required>
                                    <option value="fixed_month_day">Fixed Month/Day</option>
                                    <option value="week_offset_from_start">Week Offset from Sem1 Start</option>
                                    <option value="week_offset_from_sem2">Week Offset from Sem2 Start</option>
                                    <option value="nth_weekday">Nth Weekday of Month</option>
                                    <option value="easter_offset">Orthodox Easter Offset</option>
                                    <option value="islamic_holiday">Islamic Holiday</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Event Type</label>
                                <select id="r-event-type" class="form-control">
                                    <option value="event">School Event</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="exam">Exam</option>
                                    <option value="break">Break</option>
                                    <option value="meeting">Meeting</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Color</label>
                                <input type="color" id="r-color" class="form-control" value="#4f46e5" style="height:38px;">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Rule Value (JSON) <span class="text-danger">*</span></label>
                        <textarea id="r-value" class="form-control" rows="3" required
                                  placeholder='e.g. {"month": 9, "day": 11}'></textarea>
                        <small class="text-muted">
                            fixed_month_day: <code>{"month":9,"day":11}</code> &nbsp;|&nbsp;
                            week_offset: <code>{"weeks":8,"duration_days":5}</code> &nbsp;|&nbsp;
                            easter_offset: <code>{"offset_days":0,"calendar":"orthodox"}</code>
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" id="r-desc" class="form-control" placeholder="Optional explanation">
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="r-active" checked>
                        <label class="custom-control-label" for="r-active">Active (included in generation)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg mr-1"></i>Save Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Edit rule
$(document).on('click', '.btn-edit-rule', function () {
    var d = $(this).data();
    $('#rule-id').val(d.id);
    $('#r-name').val(d.name);
    $('#r-type').val(d.rule_type);
    $('#r-event-type').val(d.event_type);
    $('#r-value').val(typeof d.rule_value === 'object' ? JSON.stringify(d.rule_value) : d.rule_value);
    $('#r-color').val(d.color);
    $('#r-desc').val(d.description);
    $('#r-sort').val(d.sort_order);
    $('#r-active').prop('checked', d.is_active == 1);
    $('#rule-modal-title').text('Edit Rule');
    $('#rule-modal').modal('show');
});

// Delete rule
$(document).on('click', '.btn-delete-rule', function () {
    var id = $(this).data('id');
    swal({title:'Delete Rule?',text:'This cannot be undone.',icon:'warning',buttons:true,dangerMode:true}).then(function(ok){
        if (!ok) return;
        $.ajax({ url: '{{ url("/academic-calendar/rules") }}/'+id, type:'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), _method:'DELETE' }
        }).done(function(){ location.reload(); });
    });
});

// Save rule
$('#rule-form').on('submit', function (e) {
    e.preventDefault();
    var id = $('#rule-id').val();
    var url = id ? '{{ url("/academic-calendar/rules") }}/'+id : '{{ route("acal.rules.store") }}';
    var payload = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        name: $('#r-name').val(), rule_type: $('#r-type').val(),
        event_type: $('#r-event-type').val(), rule_value: $('#r-value').val(),
        color: $('#r-color').val(), description: $('#r-desc').val(),
        sort_order: $('#r-sort').val(), is_active: $('#r-active').is(':checked') ? 1 : 0,
    };
    if (id) payload._method = 'PUT';
    $.post(url, payload).done(function(r){
        if (r.ok) { $('#rule-modal').modal('hide'); location.reload(); }
        else flash({msg: r.msg, type:'danger'});
    });
});

// Reset modal on open
$('#rule-modal').on('show.bs.modal', function(e){
    if (!$(e.relatedTarget).hasClass('btn-edit-rule')) {
        $('#rule-id').val(''); $('#rule-form')[0].reset(); $('#rule-modal-title').text('Add Rule');
    }
});
</script>
@endsection
