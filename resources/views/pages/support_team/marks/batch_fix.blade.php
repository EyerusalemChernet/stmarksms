@extends('layouts.master')
@section('page_title', 'Fix Mark Errors')
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
            <h5 class="card-title"><i class="icon-wrench mr-2"></i> Batch Fix </h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">

            {{-- Info banner --}}
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
                <i class="bi bi-info-circle-fill" style="color:#3b82f6;font-size:18px;flex-shrink:0;margin-top:2px;"></i>
                <div style="font-size:13px;color:#1e40af;line-height:1.5;">
                    <strong>How Batch Fix works:</strong> Select an exam, class, and section to recalculate all grades, positions, totals, and averages automatically. Use this after editing marks manually or if you notice inconsistencies.
                </div>
            </div>

            <form class="ajax-update" method="post" action="{{ route('marks.batch_update') }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-10">
                        <fieldset>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exam_id" class="col-form-label font-weight-bold">Exam:</label>
                                        <select required id="exam_id" name="exam_id" data-placeholder="Select Exam" class="form-control select">
                                            <option value="">Select Exam</option>
                                            @foreach($exams as $ex)
                                                <option value="{{ $ex->id }}">{{ $ex->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="my_class_id" class="col-form-label font-weight-bold">Class:</label>
                                        <select required onchange="getClassSections(this.value)" id="my_class_id" name="my_class_id" class="form-control select">
                                            <option value="">Select Class</option>
                                            @foreach($my_classes as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="section_id" class="col-form-label font-weight-bold">Section:</label>
                                        <select required id="section_id" name="section_id" data-placeholder="Select Class First" class="form-control select">
                                            <option value="">Select Class First</option>
                                        </select>
                                    </div>
                                </div>

                            </div>

                        </fieldset>
                    </div>

                    <div class="col-md-2 mt-4">
                        <div class="text-right mt-1">
                            <button type="submit" class="btn btn-danger">Fix Errors <i class="icon-wrench2 ml-2"></i></button>
                        </div>
                    </div>

                </div>

            </form>

        </div>
    </div>
@endsection
