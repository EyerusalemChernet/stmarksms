@extends('layouts.master')
@section('page_title', 'Departments')
@section('content')

<div class="d-flex align-items-center mb-4" style="gap:12px;">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0;">Departments</h5>
</div>

@if(session('flash_success'))<div class="alert alert-success border-0 mb-3">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0 mb-3">{{ session('flash_danger') }}</div>@endif

<div class="row">
    {{-- Add Department --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="bi bi-building mr-1 text-primary"></i>Add Department</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('departments.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-semibold" style="font-size:13px;">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Sciences" required value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold" style="font-size:13px;">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-sm">
                        <i class="bi bi-plus-circle mr-1"></i>Add Department
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Department list (retractable) --}}
    <div class="col-md-8" id="dept-accordion">
        @forelse($departments as $dept)
        <div class="card mb-2" style="border-radius:10px;overflow:hidden;">
            {{-- Header (clickable to toggle) --}}
            <div class="card-header bg-white d-flex align-items-center justify-content-between"
                 style="cursor:pointer;padding:12px 16px;"
                 onclick="toggleDept({{ $dept->id }})">
                <div class="d-flex align-items-center" style="gap:10px;">
                    <i class="bi bi-chevron-right" id="chevron-{{ $dept->id }}"
                       style="transition:transform .2s;font-size:12px;color:#64748b;"></i>
                    <strong style="font-size:14px;">{{ $dept->name }}</strong>
                    <span class="badge badge-secondary">{{ $dept->teachers->count() }} teacher(s)</span>
                    @if($dept->description)
                    <small class="text-muted d-none d-md-inline">{{ \Illuminate\Support\Str::limit($dept->description, 50) }}</small>
                    @endif
                </div>
            </div>

            {{-- Body (collapsible) --}}
            <div id="dept-body-{{ $dept->id }}" style="display:none;">
                <div class="card-body pt-2 pb-3">

                    {{-- Current teachers with delete --}}
                    @if($dept->teachers->isNotEmpty())
                    <div class="mb-3" style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($dept->teachers as $t)
                        <div style="background:#ede9fe;border-radius:20px;padding:4px 10px 4px 8px;display:flex;align-items:center;gap:6px;font-size:12px;">
                            <img src="{{ $t->photo }}" class="rounded-circle"
                                 style="width:20px;height:20px;object-fit:cover;" alt="">
                            <span style="font-weight:600;color:#4f46e5;">{{ $t->name }}</span>
                            {{-- Delete button with confirmation --}}
                            <button type="button"
                                    onclick="confirmRemoveTeacher('{{ route('departments.teachers.remove', [$dept->id, $t->id]) }}', '{{ addslashes($t->name) }}', '{{ addslashes($dept->name) }}')"
                                    style="background:none;border:none;padding:0;cursor:pointer;color:#7c3aed;line-height:1;"
                                    title="Remove {{ $t->name }} from {{ $dept->name }}">
                                <i class="bi bi-x-circle-fill" style="font-size:14px;"></i>
                            </button>
                            {{-- Hidden form for actual delete --}}
                            <form id="remove-teacher-{{ $dept->id }}-{{ $t->id }}"
                                  method="POST"
                                  action="{{ route('departments.teachers.remove', [$dept->id, $t->id]) }}"
                                  class="d-none">
                                @csrf @method('DELETE')
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted small mb-3">No teachers assigned yet.</p>
                    @endif

                    {{-- Add teachers (multi-select with search) --}}
                    <form method="POST" action="{{ route('departments.teachers.add', $dept->id) }}"
                          class="d-flex align-items-end" style="gap:8px;">
                        @csrf
                        <div style="flex:1;">
                            <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block;">
                                Add Teachers
                            </label>
                            <select name="user_ids[]" multiple required
                                    class="form-control select-search"
                                    data-placeholder="Search and select teachers..."
                                    style="width:100%;">
                                @foreach($allTeachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}{{ $t->email ? ' — '.$t->email : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                            <i class="bi bi-plus-circle mr-1"></i>Add
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="card">
            <div class="card-body text-muted text-center py-4">
                No departments yet. Add one using the form on the left.
            </div>
        </div>
        @endforelse
    </div>
</div>

@endsection

@section('scripts')
<script>
// Toggle department accordion
function toggleDept(id) {
    var body    = document.getElementById('dept-body-' + id);
    var chevron = document.getElementById('chevron-' + id);
    var isOpen  = body.style.display !== 'none';
    body.style.display    = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
}

// Confirm remove teacher with SweetAlert
function confirmRemoveTeacher(actionUrl, teacherName, deptName) {
    swal({
        title: 'Remove Teacher?',
        text: 'Remove ' + teacherName + ' from ' + deptName + '?',
        icon: 'warning',
        buttons: ['Cancel', 'Yes, Remove'],
        dangerMode: true,
    }).then(function(confirmed) {
        if (confirmed) {
            // Find and submit the hidden form
            var forms = document.querySelectorAll('form[action="' + actionUrl + '"]');
            if (forms.length > 0) forms[0].submit();
        }
    });
}

// Open first department by default
document.addEventListener('DOMContentLoaded', function() {
    var firstBody    = document.querySelector('[id^="dept-body-"]');
    var firstChevron = document.querySelector('[id^="chevron-"]');
    if (firstBody)    firstBody.style.display = 'block';
    if (firstChevron) firstChevron.style.transform = 'rotate(90deg)';
});
</script>
@endsection
