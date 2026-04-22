@extends('layouts.master')
@section('page_title', 'Manage Exam Marks')
@section('content')
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title"><i class="icon-books mr-2"></i> Manage Exam Marks</h5>
            <div class="header-elements">
                <a href="{{ route('marks.insights') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-graph-up-arrow mr-1"></i>Smart Insights
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('pages.support_team.marks.selector')
        </div>
    </div>
    @endsection
