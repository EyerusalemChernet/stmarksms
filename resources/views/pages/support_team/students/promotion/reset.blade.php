@extends('layouts.master')
@section('page_title', 'Manage Promotions')
@section('content')

    {{--Reset All--}}
    <div class="card">
        <div class="card-body text-center">
            <button id="promotion-reset-all" class="btn btn-danger btn-large">Reset All Promotions for the Session</button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-header">
            <h6 class="card-title font-weight-bold mb-0"><i class="bi bi-funnel mr-1"></i> Filter Promotions</h6>
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('students.promotion_manage') }}" class="row align-items-end">
                <div class="col-md-3">
                    <label class="font-weight-bold">From Class</label>
                    <select name="fc" id="filter_fc" class="form-control select">
                        <option value="">All Classes</option>
                        @foreach($my_classes as $c)
                            <option value="{{ $c->id }}" {{ (string)$filter_fc === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold">Section</label>
                    <select name="fs" id="filter_fs" class="form-control select">
                        <option value="">All Sections</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->id }}" data-class="{{ $s->my_class_id }}"
                                {{ (string)$filter_fs === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold">Status</label>
                    <select name="status" class="form-control select">
                        <option value="">All Statuses</option>
                        <option value="P" {{ $filter_status === 'P' ? 'selected' : '' }}>Promoted</option>
                        <option value="D" {{ $filter_status === 'D' ? 'selected' : '' }}>Not Promoted</option>
                        <option value="G" {{ $filter_status === 'G' ? 'selected' : '' }}>Graduated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block"><i class="bi bi-search mr-1"></i> Apply</button>
                    <a href="{{ route('students.promotion_manage') }}" class="btn btn-light btn-block mt-1">Clear</a>
                </div>
            </form>
            <p class="text-muted small mb-0 mt-2">
                Showing <strong>{{ $promotions->count() }}</strong> record(s) for
                <span class="text-danger">{{ $old_year }}</span> → <span class="text-success">{{ $new_year }}</span>.
                Use class and section filters before reviewing large schools.
            </p>
        </div>
    </div>

    {{-- Manage Promotions --}}
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title font-weight-bold">Manage Promotions — Students Moved From <span class="text-danger">{{ $old_year }}</span> To <span class="text-success">{{ $new_year }}</span></h5>
            {!! Qs::getPanelOptions() !!}
        </div>

        <div class="card-body">

            @if($promotions->isEmpty())
                <div class="alert alert-info mb-0">No promotion records match your filters for this session transition.</div>
            @else
            <table id="promotions-list" class="table datatable-button-html5-columns">
                <thead>
                <tr>
                    <th>S/N</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>From Class</th>
                    <th>To Class</th>
                    <th>Avg %</th>
                    <th>Status</th>
                    <th>Reason / Context</th>
                    <th>Report Card</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($promotions->sortBy(fn($p) => ($p->fc->name ?? '').($p->fs->name ?? '').($p->student->name ?? '')) as $p)
                    @php $ins = $insights[$p->id] ?? []; @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><img class="rounded-circle" style="height: 40px; width: 40px;" src="{{ $p->student->photo }}" alt="photo"></td>
                        <td>{{ $p->student->name }}</td>
                        <td>{{ $p->fc->name.' '.$p->fs->name }}</td>
                        <td>{{ $p->tc->name.' '.$p->ts->name }}</td>
                        <td>
                            @if(isset($ins['session_average']) && $ins['session_average'] !== null)
                                <span class="{{ $ins['session_average'] >= ($ins['pass_mark'] ?? 50) ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($ins['session_average'], 1) }}%
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @if($p->status === 'P')
                            <td><span class="badge badge-success">Promoted</span></td>
                        @elseif($p->status === 'D')
                            <td><span class="badge badge-danger">Not Promoted</span></td>
                        @else
                            <td><span class="badge badge-primary">Graduated</span></td>
                        @endif
                        <td style="max-width:280px;">
                            <div class="font-weight-semibold" style="font-size:12px;">{{ $ins['summary'] ?? '—' }}</div>
                            @if(!empty($ins['reasons']))
                                <ul class="mb-0 pl-3 text-muted" style="font-size:11px;">
                                    @foreach($ins['reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('marks.show', [$p->student_id, $old_year]) }}" target="_blank" class="btn btn-sm btn-outline-success" title="View report card / marksheet">
                                <i class="bi bi-file-earmark-text"></i>
                            </a>
                        </td>
                        <td class="text-center">
                            <button data-id="{{ $p->id }}" class="btn btn-danger btn-sm promotion-reset">Reset</button>
                            <form id="promotion-reset-{{ $p->id }}" method="post" action="{{ route('students.promotion_reset', $p->id) }}">@csrf @method('DELETE')</form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        (function () {
            var fc = document.getElementById('filter_fc');
            var fs = document.getElementById('filter_fs');
            if (!fc || !fs) return;

            function filterSections() {
                var classId = fc.value;
                Array.prototype.forEach.call(fs.options, function (opt) {
                    if (!opt.value) return;
                    opt.hidden = classId && opt.getAttribute('data-class') !== classId;
                });
                if (fs.selectedOptions[0] && fs.selectedOptions[0].hidden) {
                    fs.value = '';
                }
            }

            fc.addEventListener('change', filterSections);
            filterSections();
        })();

        /* Single Reset */
        $('.promotion-reset').on('click', function () {
            let pid = $(this).data('id');
            if (confirm('Are You Sure you want to proceed?')){
                $('form#promotion-reset-'+pid).submit();
            }
            return false;
        });

        /* Reset All Promotions */
        $('#promotion-reset-all').on('click', function () {
            if (confirm('Are You Sure you want to proceed?')){
                $.ajax({
                    url:"{{ route('students.promotion_reset_all') }}",
                    type:'DELETE',
                    data:{ '_token' : $('#csrf-token').attr('content') },
                    success:function (resp) {
                        $('table#promotions-list > tbody').fadeOut().remove();
                        flash({msg : resp.msg, type : 'success'});
                    }
                })
            }
            return false;
        })
    </script>
@endsection
