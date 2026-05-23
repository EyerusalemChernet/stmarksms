@extends('layouts.master')
@section('page_title', 'Manage System Settings')
@section('content')

    <div class="card">
        <div class="card-header header-elements-inline">
            <h6 class="card-title font-weight-semibold">Update System Settings</h6>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">
            <form enctype="multipart/form-data" method="post" action="{{ route('settings.update') }}">
                @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 border-right-2 border-right-blue-400">

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">Name of School <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input name="system_name" value="{{ $s['system_name'] ?? '' }}" required type="text" class="form-control" placeholder="Name of School">
                            </div>
                        </div>

                        {{-- Current Session — auto-synced with active academic year --}}
                        <div class="form-group row">
                            <label for="current_session" class="col-lg-3 col-form-label font-weight-semibold">Current Session <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select required name="current_session" id="current_session" class="form-control">
                                    @php
                                        $activeYear = \App\Models\AcademicYear::where('is_current', true)->first();
                                        $activeName = $activeYear ? $activeYear->name : null;
                                    @endphp
                                    @for($y = date('Y', strtotime('-3 years')); $y <= date('Y', strtotime('+2 years')); $y++)
                                        @php $session = ($y).'-'.($y+1); @endphp
                                        <option value="{{ $session }}"
                                            {{ ($s['current_session'] == $session || $activeName == $session) ? 'selected' : '' }}>
                                            {{ $session }}
                                            @if($activeName == $session)
                                                — Active Academic Year
                                            @endif
                                        </option>
                                    @endfor
                                </select>
                                @if($activeYear)
                                <small class="text-success mt-1 d-block">
                                    <i class="bi bi-check-circle mr-1"></i>
                                    Matched with active academic year: <strong>{{ $activeYear->name }}</strong> ({{ $activeYear->eth_name }})
                                </small>
                                @else
                                <small class="text-warning mt-1 d-block">
                                    <i class="bi bi-exclamation-triangle mr-1"></i>
                                    No active academic year found. <a href="{{ route('calendar.index') }}">Generate one</a>.
                                </small>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">School Acronym</label>
                            <div class="col-lg-9">
                                <input name="system_title" value="{{ $s['system_title'] ?? '' }}" type="text" class="form-control" placeholder="School Acronym">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">Phone</label>
                            <div class="col-lg-9">
                                <input name="phone" value="{{ $s['phone'] ?? '' }}" type="text" class="form-control" placeholder="Phone">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">School Email</label>
                            <div class="col-lg-9">
                                <input name="system_email" value="{{ $s['system_email'] ?? '' }}" type="email" class="form-control" placeholder="School Email">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">School Address <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input required name="address" value="{{ $s['address'] ?? '' }}" type="text" class="form-control" placeholder="School Address">
                            </div>
                        </div>

                </div>
                <div class="col-md-6">

                    {{-- Fees --}}
                    <fieldset>
                        <legend><strong>Next Term Fees</strong></legend>
                        @foreach($class_types as $ct)
                        <div class="form-group row">
                            <label class="col-lg-3 col-form-label font-weight-semibold">{{ $ct->name }}</label>
                            <div class="col-lg-9">
                                <input class="form-control"
                                       value="{{ $s['next_term_fees_'.strtolower($ct->code)] ?? '' }}"
                                       name="next_term_fees_{{ strtolower($ct->code) }}"
                                       placeholder="{{ $ct->name }}" type="text">
                            </div>
                        </div>
                        @endforeach
                    </fieldset>

                    <hr class="divider">

                    {{-- Logo --}}
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label font-weight-semibold">Change Logo:</label>
                        <div class="col-lg-9">
                            <div class="mb-3">
                                <img style="width:100px;" height="100px" src="{{ $s['logo'] ?? asset('global_assets/images/logo.png') }}" alt="Logo">
                            </div>
                            <input name="logo" accept="image/*" type="file" class="file-input" data-show-caption="false" data-show-upload="false" data-fouc>
                        </div>
                    </div>

                </div>
            </div>

            <hr class="divider">

            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    Save Settings <i class="icon-paperplane ml-2"></i>
                </button>
            </div>
            </form>
        </div>
    </div>

@endsection
