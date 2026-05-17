@extends('layouts.master')
@section('page_title', 'Manage Marks')
@section('content')

    {{-- Breadcrumb / Navigation --}}
    <div style="margin-bottom:16px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('marks.index') }}" style="color:#4f46e5;font-size:13px;text-decoration:none;font-weight:600;">
            <i class="bi bi-arrow-left mr-1"></i>Back to Marks
        </a>
        <span style="color:#cbd5e1;">|</span>
        <span style="font-size:13px;color:#64748b;">Session: <strong style="color:#1e293b;">{{ \App\Helpers\Qs::getCurrentSession() }}</strong></span>
    </div>

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-bold">Fill The Form To Manage Marks</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            @include('pages.support_team.marks.selector')
        </div>
    </div>

    <div class="card">

        <div class="card-header">
            <div class="row">
                <div class="col-md-4"><h6 class="card-title"><strong>Subject: </strong> {{ $m->subject->name }}</h6></div>
                <div class="col-md-4"><h6 class="card-title"><strong>Class: </strong> {{ $m->my_class->name.' '.$m->section->name }}</h6></div>
                <div class="col-md-4"><h6 class="card-title"><strong>Exam: </strong> {{ $m->exam->name.' - '.$m->year }}</h6></div>
            </div>
        </div>

        <div class="card-body">
            @include('pages.support_team.marks.edit')
            {{--@include('pages.support_team.marks.random')--}}
        </div>
    </div>

    {{--Marks Manage End--}}

@endsection
