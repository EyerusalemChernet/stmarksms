@extends('layouts.master')
@section('page_title', 'Staff Shifts')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-clock mr-2"></i>Shifts</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to HR</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add Shift</h6></div>
            <div class="card-body">
                <form id="shift-form">
                    @csrf
                    <div class="form-group">
                        <label>Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="shift-name" class="form-control"
                               placeholder="e.g. Morning Shift" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="shift-start" class="form-control" required>
                        </div>
                        <div class="form-group col-6">
                            <label>End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="shift-end" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="shift-desc" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Shift</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">All Shifts</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Name</th><th>Start</th><th>End</th><th>Staff</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $sh)
                        <tr>
                            <td class="font-weight-bold">{{ $sh->name }}</td>
                            <td>{{ $sh->start_time }}</td>
                            <td>{{ $sh->end_time }}</td>
                            <td><span class="badge badge-dark">{{ $sh->staff_shifts_count }}</span></td>
                            <td>
                                <form action="{{ route('hr.shifts.destroy', $sh->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete this shift?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No shifts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
$('#shift-form').on('submit', function(e){
    e.preventDefault();
    $.post('{{ route("hr.shifts.store") }}', {
        _token:      '{{ csrf_token() }}',
        name:        $('#shift-name').val(),
        start_time:  $('#shift-start').val(),
        end_time:    $('#shift-end').val(),
        description: $('#shift-desc').val()
    }).done(function(r){
        if(r.ok){ location.reload(); }
        else { alert(r.msg); }
    }).fail(function(xhr){
        var err = xhr.responseJSON;
        if(err && err.errors){ alert(Object.values(err.errors).join('\n')); }
        else { alert('An error occurred.'); }
    });
});
</script>
@endsection
