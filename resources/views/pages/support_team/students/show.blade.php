@extends('layouts.master')
@section('page_title', 'Student Profile - '.$sr->user->name)
@section('content')
<div class="row">
    <div class="col-md-3 text-center">
        <div class="card">
            <div class="card-body">
                <img style="width: 90%; height:90%" src="{{ $sr->user->photo }}" alt="photo" class="rounded-circle">
                <br>
                <h3 class="mt-3">{{ $sr->user->name }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-highlight">
                    <li class="nav-item">
                        <a href="#" class="nav-link active">{{ $sr->user->name }}</a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{--Basic Info--}}
                    <div class="tab-pane fade show active" id="basic-info">
                        <table class="table table-bordered">
                            <tbody>
                            <tr>
                                <td class="font-weight-bold">Name</td>
                                <td>{{ $sr->user->name }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">ADM_NO</td>
                                <td>{{ $sr->adm_no }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Class</td>
                                <td>{{ $sr->my_class->name.' '.$sr->section->name }}</td>
                            </tr>
                            @if($sr->my_parent_id)
                                <tr>
                                    <td class="font-weight-bold">Parent / Guardian</td>
                                    <td>
                                        <div class="d-flex align-items-start" style="gap:10px;">
                                            <img src="{{ $sr->my_parent->photo ?? asset('global_assets/images/user.png') }}"
                                                 class="rounded-circle" style="width:36px;height:36px;object-fit:cover;flex-shrink:0;" alt="parent">
                                            <div>
                                                <a target="_blank" href="{{ route('users.show', Qs::hash($sr->my_parent_id)) }}"
                                                   style="font-weight:600;">{{ $sr->my_parent->name }}</a>
                                                @if($sr->my_parent->phone)
                                                <div style="font-size:12px;color:#64748b;">
                                                    <i class="bi bi-telephone mr-1"></i>{{ $sr->my_parent->phone }}
                                                    @if($sr->my_parent->phone2)
                                                        &nbsp;/&nbsp;{{ $sr->my_parent->phone2 }}
                                                    @endif
                                                </div>
                                                @endif
                                                @if($sr->my_parent->email)
                                                <div style="font-size:12px;color:#64748b;">
                                                    <i class="bi bi-envelope mr-1"></i>{{ $sr->my_parent->email }}
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="font-weight-bold">Parent / Guardian</td>
                                    <td>
                                        <span class="badge badge-warning" style="font-size:12px;">
                                            <i class="bi bi-exclamation-triangle mr-1"></i>Not assigned
                                        </span>
                                        @if(Qs::userIsTeamSA())
                                        <a href="{{ route('students.edit', Qs::hash($sr->id)) }}" class="btn btn-xs btn-outline-primary ml-2">
                                            <i class="bi bi-person-plus mr-1"></i>Assign Parent
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td class="font-weight-bold">Year Admitted</td>
                                <td>{{ $sr->year_admitted }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Gender</td>
                                <td>{{ $sr->user->gender }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Address</td>
                                <td>{{ $sr->user->address }}</td>
                            </tr>
                            @if($sr->user->email)
                            <tr>
                                <td class="font-weight-bold">Email</td>
                                <td>{{$sr->user->email }}</td>
                            </tr>
                            @endif
                            @if($sr->user->phone)
                                <tr>
                                    <td class="font-weight-bold">Phone</td>
                                    <td>{{$sr->user->phone.' '.$sr->user->phone2 }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="font-weight-bold">Birthday</td>
                                <td>{{$sr->user->dob }}</td>
                            </tr>
                            @if($sr->user->bg_id)
                            <tr>
                                <td class="font-weight-bold">Blood Group</td>
                                <td>{{$sr->user->blood_group->name }}</td>
                            </tr>
                            @endif
                            @if($sr->user->nal_id)
                            <tr>
                                <td class="font-weight-bold">Nationality</td>
                                <td>{{$sr->user->nationality->name }}</td>
                            </tr>
                            @endif
                            @if($sr->user->state_id)
                            <tr>
                                <td class="font-weight-bold">State</td>
                                <td>{{$sr->user->state->name }}</td>
                            </tr>
                            @endif
                            @if($sr->user->lga_id)
                            <tr>
                                <td class="font-weight-bold">LGA</td>
                                <td>{{$sr->user->lga->name }}</td>
                            </tr>
                            @endif
                            @if($sr->dorm_id)
                                <tr>
                                    <td class="font-weight-bold">Dormitory</td>
                                    <td>{{$sr->dorm->name.' '.$sr->dorm_room_no }}</td>
                                </tr>
                            @endif

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


    {{--Student Profile Ends--}}

@endsection
