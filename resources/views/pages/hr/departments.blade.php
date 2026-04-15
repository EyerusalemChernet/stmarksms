@extends('layouts.master')
@section('page_title', 'Departments')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-building mr-2"></i>Departments</h5>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left mr-1"></i>Back to HR</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">Add Department</h6></div>
            <div class="card-body">
                <form id="dept-form">
                    @csrf
                    <div class="form-group">
                        <label>Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="dept-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="dept-desc" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white"><h6 class="card-title mb-0">All Departments</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light"><tr><th>Name</th><th>Staff Count</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr>
                            <td>{{ $dept->name }}</td>
                            <td><span class="badge badge-primary">{{ $dept->staff_count }}</span></td>
                            <td>
                                <form action="{{ route('hr.departments.destroy', $dept->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this department?')"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">No departments yet.</td></tr>
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
$('#dept-form').on('submit', function(e){
    e.preventDefault();
    $.post('{{ route("hr.departments.store") }}', {
        _token: '{{ csrf_token() }}',
        name: $('#dept-name').val(),
        description: $('#dept-desc').val()
    }).done(function(r){
        if(r.ok){ location.reload(); }
        else { alert(r.msg); }
    });
});
</script>
@endsection
