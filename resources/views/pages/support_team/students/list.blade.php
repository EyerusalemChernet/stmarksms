@extends('layouts.master')
@section('page_title', 'Student Information - '.$my_class->name)
@section('content')

<div class="d-flex align-items-center mb-4" style="gap:12px;">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 2px;">{{ $my_class->name }} — Students</h5>
        <small class="text-muted">{{ $students->count() }} student(s) enrolled</small>
    </div>
    @if(Qs::userIsTeamSA())
    <a href="{{ route('students.create') }}" class="btn btn-sm btn-primary ml-auto">
        <i class="bi bi-person-plus mr-1"></i>Admit Student
    </a>
    @endif
</div>

@if(session('flash_success'))<div class="alert alert-success border-0 mb-3">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0 mb-3">{{ session('flash_danger') }}</div>@endif

<div class="card">
    <div class="card-header bg-white">
        <ul class="nav nav-tabs nav-tabs-highlight card-header-tabs">
            <li class="nav-item"><a href="#all-students" class="nav-link active" data-toggle="tab">All {{ $my_class->name }} Students</a></li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Sections</a>
                <div class="dropdown-menu dropdown-menu-right">
                    @foreach($sections as $s)
                        <a href="#s{{ $s->id }}" class="dropdown-item" data-toggle="tab">{{ $my_class->name.' '.$s->name }}</a>
                    @endforeach
                </div>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="all-students">
                <table class="table datatable-button-html5-columns" style="font-size:13px;">
                    <thead class="thead-light">
                    <tr>
                        <th>S/N</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>ADM No</th>
                        <th>Section</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><img class="rounded-circle" style="height:36px;width:36px;object-fit:cover;" src="{{ $s->user->photo }}" alt="photo"></td>
                            <td><strong>{{ $s->user->name }}</strong></td>
                            <td><span class="badge badge-secondary">{{ $s->adm_no ?? '—' }}</span></td>
                            <td>{{ $my_class->name.' '.$s->section->name }}</td>
                            <td>{{ $s->user->email }}</td>
                            <td>
                                <div class="d-flex" style="gap:4px;">
                                    <a href="{{ route('students.show', Qs::hash($s->id)) }}" class="btn btn-xs btn-outline-info" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(Qs::userIsTeamSA())
                                    <a href="{{ route('students.edit', Qs::hash($s->id)) }}" class="btn btn-xs btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-warning"
                                            title="Reset Password"
                                            onclick="confirmResetPassword('{{ Qs::hash($s->user->id) }}', '{{ addslashes($s->user->name) }}')">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <form id="reset-pass-{{ Qs::hash($s->user->id) }}" method="post"
                                          action="{{ route('st.reset_pass', Qs::hash($s->user->id)) }}" class="d-none">@csrf</form>
                                    @endif
                                    <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($s->user->id)) }}" class="btn btn-xs btn-outline-primary" title="Marksheet">
                                        <i class="bi bi-journal-check"></i>
                                    </a>
                                    @if(Qs::userIsSuperAdmin())
                                    <button type="button" class="btn btn-xs btn-outline-danger" title="Delete"
                                            onclick="confirmDelete('{{ Qs::hash($s->user->id) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form method="post" id="item-delete-{{ Qs::hash($s->user->id) }}"
                                          action="{{ route('students.destroy', Qs::hash($s->user->id)) }}" class="d-none">
                                        @csrf @method('delete')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @foreach($sections as $se)
            <div class="tab-pane fade" id="s{{$se->id}}">
                <table class="table datatable-button-html5-columns" style="font-size:13px;">
                    <thead class="thead-light">
                    <tr>
                        <th>S/N</th><th>Photo</th><th>Name</th><th>ADM No</th><th>Email</th><th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students->where('section_id', $se->id) as $sr)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><img class="rounded-circle" style="height:36px;width:36px;object-fit:cover;" src="{{ $sr->user->photo }}" alt="photo"></td>
                            <td><strong>{{ $sr->user->name }}</strong></td>
                            <td><span class="badge badge-secondary">{{ $sr->adm_no ?? '—' }}</span></td>
                            <td>{{ $sr->user->email }}</td>
                            <td>
                                <div class="d-flex" style="gap:4px;">
                                    <a href="{{ route('students.show', Qs::hash($sr->id)) }}" class="btn btn-xs btn-outline-info" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(Qs::userIsTeamSA())
                                    <a href="{{ route('students.edit', Qs::hash($sr->id)) }}" class="btn btn-xs btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-warning"
                                            title="Reset Password"
                                            onclick="confirmResetPassword('{{ Qs::hash($sr->user->id) }}', '{{ addslashes($sr->user->name) }}')">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <form id="reset-pass-{{ Qs::hash($sr->user->id) }}" method="post"
                                          action="{{ route('st.reset_pass', Qs::hash($sr->user->id)) }}" class="d-none">@csrf</form>
                                    @endif
                                    <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($sr->user->id)) }}" class="btn btn-xs btn-outline-primary" title="Marksheet">
                                        <i class="bi bi-journal-check"></i>
                                    </a>
                                    @if(Qs::userIsSuperAdmin())
                                    <button type="button" class="btn btn-xs btn-outline-danger" title="Delete"
                                            onclick="confirmDelete('{{ Qs::hash($sr->user->id) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form method="post" id="item-delete-{{ Qs::hash($sr->user->id) }}"
                                          action="{{ route('students.destroy', Qs::hash($sr->user->id)) }}" class="d-none">
                                        @csrf @method('delete')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function confirmResetPassword(userId, studentName) {
    swal({
        title: 'Reset Password',
        text: 'Reset ' + studentName + '\'s password to "student"? They will be prompted to change it on next login.',
        icon: 'warning',
        buttons: ['Cancel', 'Yes, Reset'],
        dangerMode: true,
    }).then(function(confirmed) {
        if (confirmed) {
            document.getElementById('reset-pass-' + userId).submit();
        }
    });
}
</script>
@endsection

        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-highlight">
                <li class="nav-item"><a href="#all-students" class="nav-link active" data-toggle="tab">All {{ $my_class->name }} Students</a></li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Sections</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($sections as $s)
                            <a href="#s{{ $s->id }}" class="dropdown-item" data-toggle="tab">{{ $my_class->name.' '.$s->name }}</a>
                        @endforeach
                    </div>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all-students">
                    <table class="table datatable-button-html5-columns">
                        <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>ADM_No</th>
                            <th>Section</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($students as $s)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><img class="rounded-circle" style="height: 40px; width: 40px;" src="{{ $s->user->photo }}" alt="photo"></td>
                                <td>{{ $s->user->name }}</td>
                                <td>{{ $s->adm_no }}</td>
                                <td>{{ $my_class->name.' '.$s->section->name }}</td>
                                <td>{{ $s->user->email }}</td>
                                <td class="text-center">
                                    <div class="list-icons">
                                        <div class="dropdown">
                                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                <i class="icon-menu9"></i>
                                            </a>

                                            <div class="dropdown-menu dropdown-menu-left">
                                                <a href="{{ route('students.show', Qs::hash($s->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                                @if(Qs::userIsTeamSA())
                                                    <a href="{{ route('students.edit', Qs::hash($s->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); if(confirm('Reset this student\'s password to the default (student)?')) document.getElementById('reset-pass-{{ Qs::hash($s->user->id) }}').submit();"><i class="icon-lock"></i> Reset password</a>
                                                    <form id="reset-pass-{{ Qs::hash($s->user->id) }}" method="post" action="{{ route('st.reset_pass', Qs::hash($s->user->id)) }}" class="d-none">@csrf</form>
                                                @endif
                                                <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($s->user->id)) }}" class="dropdown-item"><i class="icon-check"></i> Marksheet</a>

                                                {{--Delete--}}
                                                @if(Qs::userIsSuperAdmin())
                                                    <a id="{{ Qs::hash($s->user->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                    <form method="post" id="item-delete-{{ Qs::hash($s->user->id) }}" action="{{ route('students.destroy', Qs::hash($s->user->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($sections as $se)
                    <div class="tab-pane fade" id="s{{$se->id}}">                         <table class="table datatable-button-html5-columns">
                            <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>ADM_No</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($students->where('section_id', $se->id) as $sr)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><img class="rounded-circle" style="height: 40px; width: 40px;" src="{{ $sr->user->photo }}" alt="photo"></td>
                                    <td>{{ $sr->user->name }}</td>
                                    <td>{{ $sr->adm_no }}</td>
                                    <td>{{ $sr->user->email }}</td>
                                    <td class="text-center">
                                        <div class="list-icons">
                                            <div class="dropdown">
                                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                                    <i class="icon-menu9"></i>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a href="{{ route('students.show', Qs::hash($sr->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Info</a>
                                                    @if(Qs::userIsTeamSA())
                                                        <a href="{{ route('students.edit', Qs::hash($sr->id)) }}" class="dropdown-item"><i class="icon-pencil"></i> Edit</a>
                                                        <a href="#" class="dropdown-item" onclick="event.preventDefault(); if(confirm('Reset this student\'s password to the default (student)?')) document.getElementById('reset-pass-{{ Qs::hash($sr->user->id) }}').submit();"><i class="icon-lock"></i> Reset password</a>
                                                        <form id="reset-pass-{{ Qs::hash($sr->user->id) }}" method="post" action="{{ route('st.reset_pass', Qs::hash($sr->user->id)) }}" class="d-none">@csrf</form>
                                                    @endif
                                                    <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($sr->user->id)) }}" class="dropdown-item"><i class="icon-check"></i> Marksheet</a>

                                                    {{--Delete--}}
                                                    @if(Qs::userIsSuperAdmin())
                                                        <a id="{{ Qs::hash($sr->user->id) }}" onclick="confirmDelete(this.id)" href="#" class="dropdown-item"><i class="icon-trash"></i> Delete</a>
                                                        <form method="post" id="item-delete-{{ Qs::hash($sr->user->id) }}" action="{{ route('students.destroy', Qs::hash($sr->user->id)) }}" class="hidden">@csrf @method('delete')</form>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

    {{--Student List Ends--}}

@endsection
