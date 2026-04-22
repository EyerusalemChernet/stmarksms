@extends('layouts.master')
@section('page_title', 'Attendance')
@section('breadcrumb')
    <span class="breadcrumb-item active">Attendance</span>
@endsection
@section('content')

@if(session('flash_success'))<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('flash_danger') }}</div>@endif

{{-- ── TEACHER: mark attendance form ───────────────────────────────────────── --}}
@if(Qs::userIsTeacher())
<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title"><i class="bi bi-clipboard-check mr-2 text-primary"></i>Mark Attendance — My Homeroom Class</h6>
    </div>
    <div class="card-body">
        @if(isset($homeroom_sections) && $homeroom_sections->isEmpty())
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
                                @foreach($homeroom_sections ?? [] as $sec)
                                    <option value="{{ $sec->id }}" data-class="{{ $sec->my_class_id }}">
                                        {{ $sec->my_class->name ?? '?' }} — {{ $sec->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
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

{{-- ── ADMIN / SUPER ADMIN: read-only notice ───────────────────────────────── --}}
@elseif(Qs::userIsTeamSA())
<div class="alert alert-info">
    <i class="bi bi-info-circle mr-2"></i>
    <strong>View Only.</strong> Attendance is marked by homeroom teachers only.
    You can view all sessions and student reports below.
</div>
@endif

{{-- ── ALL ROLES: sessions link ────────────────────────────────────────────── --}}
<div class="card mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Attendance Sessions</h6>
        <div style="gap:6px;display:flex;">
            @if(Qs::userIsTeamSA())
            <a href="{{ route('attendance.risk') }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-shield-exclamation mr-1"></i>Early Warning
            </a>
            @endif
            <a href="{{ route('attendance.sessions') }}" class="btn btn-sm btn-light">
                <i class="bi bi-list-ul mr-1"></i>View All Sessions
            </a>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
function setClassFromSection(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('teacher_class_id').value = opt.getAttribute('data-class') || '';
}
(function(){
    var sel = document.getElementById('teacher_section_id');
    if (sel && sel.options.length === 2) {
        sel.selectedIndex = 1;
        setClassFromSection(sel);
    }
})();
</script>
@endsection
