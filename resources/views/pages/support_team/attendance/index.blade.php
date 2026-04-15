@extends('layouts.master')
@section('page_title', 'Attendance')
@section('breadcrumb')
    <span class="breadcrumb-item active">Attendance</span>
@endsection
@section('content')

@if(session('flash_success'))<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('flash_danger') }}</div>@endif

{{-- ── ADMIN VIEW: full class/section selector ─────────────────────────────── --}}
@if($is_admin)
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title"><i class="bi bi-clipboard-check mr-2 text-primary"></i>Open Attendance Session</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('attendance.create') }}">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="my_class_id" onchange="getClassSections(this.value)" required
                                class="select-search form-control" data-placeholder="Select Class">
                            <option value=""></option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select name="section_id" id="section_id" required
                                class="select-search form-control" data-placeholder="Select Class First">
                            <option value=""></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" required class="form-control"
                               value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mb-3">
                        <i class="bi bi-play-circle mr-1"></i>Open Session
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── TEACHER VIEW: homeroom sections only ────────────────────────────────── --}}
@else
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title"><i class="bi bi-clipboard-check mr-2 text-primary"></i>Mark Attendance — My Homeroom Class</h6>
    </div>
    <div class="card-body">
        @if($homeroom_sections->isEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle mr-2"></i>
                You have not been assigned as a homeroom teacher for any class.
                Please contact the administrator to assign you to a section.
            </div>
        @else
            <form method="POST" action="{{ route('attendance.create') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Your Homeroom Class <span class="text-danger">*</span></label>
                            <select name="section_id" id="teacher_section_id" required
                                    class="form-control" onchange="setClassFromSection(this)">
                                <option value="">— Select Section —</option>
                                @foreach($homeroom_sections as $sec)
                                    <option value="{{ $sec->id }}"
                                            data-class="{{ $sec->my_class_id }}">
                                        {{ $sec->my_class->name ?? '?' }} — {{ $sec->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Hidden class id, set by JS --}}
                    <input type="hidden" name="my_class_id" id="teacher_class_id">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" required class="form-control"
                                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-3">
                            <i class="bi bi-play-circle mr-1"></i>Open Session
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endif

<div class="card mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Sessions</h6>
        <a href="{{ route('attendance.sessions') }}" class="btn btn-sm btn-light">
            <i class="bi bi-list-ul mr-1"></i>View All Sessions
        </a>
    </div>
</div>

@endsection
@section('scripts')
<script>
// For teacher view: sync hidden my_class_id when section changes
function setClassFromSection(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('teacher_class_id').value = opt.getAttribute('data-class') || '';
}
// Pre-fill on page load if only one section
(function(){
    var sel = document.getElementById('teacher_section_id');
    if (sel && sel.options.length === 2) { // 1 real option + blank
        sel.selectedIndex = 1;
        setClassFromSection(sel);
    }
})();
</script>
@endsection
