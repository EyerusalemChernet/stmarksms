@extends('layouts.master')
@section('page_title', 'Academic Calendar Generator')
@section('content')

{{-- Header stats --}}
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center py-3" style="border-left:4px solid #4f46e5;">
            <div style="font-size:28px;font-weight:700;color:#4f46e5;">{{ $years->count() }}</div>
            <div class="text-muted" style="font-size:13px;">Academic Years</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center py-3" style="border-left:4px solid #10b981;">
            <div style="font-size:28px;font-weight:700;color:#10b981;">{{ $rules->where('is_active',true)->count() }}</div>
            <div class="text-muted" style="font-size:13px;">Active Rules</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center py-3" style="border-left:4px solid #f59e0b;">
            <div style="font-size:28px;font-weight:700;color:#f59e0b;">{{ $current ? $current->events->count() : 0 }}</div>
            <div class="text-muted" style="font-size:13px;">Events This Year</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center py-3" style="border-left:4px solid #ef4444;">
            <div style="font-size:28px;font-weight:700;color:#ef4444;">{{ $current ? $current->holidays->count() : 0 }}</div>
            <div class="text-muted" style="font-size:13px;">Holidays This Year</div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Generate panel --}}
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;">
                <h6 class="mb-0"><i class="bi bi-magic mr-2"></i>Generate New Academic Year</h6>
            </div>
            <div class="card-body">
                <p class="text-muted" style="font-size:13px;">
                    One click generates the full Ethiopian academic calendar:
                    imports holidays, applies scheduling rules, resolves conflicts, and publishes.
                </p>
                <div class="form-group">
                    <label class="font-weight-semibold">Gregorian Start Year</label>
                    <select id="gen-year" class="form-control">
                        @for($y = date('Y'); $y <= date('Y') + 3; $y++)
                        <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>
                            {{ $y }}/{{ $y+1 }} ({{ $y-7 }}/{{ $y-6 }} E.C.)
                        </option>
                        @endfor
                    </select>
                </div>
                <button id="btn-generate" class="btn btn-primary btn-block">
                    <i class="bi bi-play-fill mr-1"></i>Generate Academic Year
                </button>
                <div id="gen-progress" class="mt-3" style="display:none;">
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block">Running pipeline: rules → holidays → conflicts → publish...</small>
                </div>
                <div id="gen-result" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    {{-- Academic years list --}}
    <div class="col-lg-8 mb-3">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="bi bi-calendar-range mr-2"></i>Academic Years</h6>
                <a href="{{ route('acal.rules') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-sliders mr-1"></i>Manage Rules
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Year</th><th>Ethiopian</th><th>Period</th>
                            <th>Events</th><th>Holidays</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($years as $yr)
                    <tr>
                        <td class="font-weight-semibold">{{ $yr->name }}</td>
                        <td><span class="badge badge-light">{{ $yr->eth_name }}</span></td>
                        <td style="font-size:12px;">
                            {{ $yr->start_date->format('d M Y') }} —
                            {{ $yr->end_date->format('d M Y') }}
                        </td>
                        <td>{{ $yr->events->count() }}</td>
                        <td>{{ $yr->holidays->count() }}</td>
                        <td>
                            @if($yr->status === 'active')
                                <span class="badge badge-success">Active</span>
                                @if($yr->is_current)<span class="badge badge-primary ml-1">Current</span>@endif
                            @elseif($yr->status === 'draft')
                                <span class="badge badge-warning">Draft</span>
                            @else
                                <span class="badge badge-secondary">Archived</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('acal.show', $yr->id) }}" class="btn btn-xs btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if(App\Helpers\Qs::userIsSuperAdmin() && $yr->status !== 'archived')
                            <form method="post" action="{{ route('acal.archive', $yr->id) }}" class="d-inline">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-xs btn-secondary"
                                        onclick="return confirm('Archive this year?')">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No academic years yet. Generate one above.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Pipeline workflow diagram --}}
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-diagram-3 mr-2"></i>Generation Pipeline</h6></div>
    <div class="card-body">
        <div class="d-flex align-items-center flex-wrap" style="gap:0;">
            @foreach([
                ['bi-gear','#4f46e5','1. Rules Engine','Reads active calendar rules'],
                ['bi-cloud-download','#3b82f6','2. Holiday Import','Fetches ET holidays from Nager.Date API'],
                ['bi-calendar-check','#10b981','3. Event Generation','Calculates dates from rules'],
                ['bi-exclamation-triangle','#f59e0b','4. Conflict Resolver','Moves exams off holidays/weekends'],
                ['bi-robot','#8b5cf6','5. AI Review','Ollama validates edge cases'],
                ['bi-check-circle','#ef4444','6. Publish','Calendar goes live'],
            ] as $step)
            <div class="text-center" style="flex:1;min-width:120px;padding:12px 8px;">
                <div style="width:48px;height:48px;border-radius:50%;background:{{ $step[1] }};color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:20px;">
                    <i class="bi {{ $step[0] }}"></i>
                </div>
                <div style="font-size:12px;font-weight:600;">{{ $step[2] }}</div>
                <div style="font-size:11px;color:#9ca3af;">{{ $step[3] }}</div>
            </div>
            @if(!$loop->last)
            <div style="font-size:20px;color:#d1d5db;padding:0 4px;">→</div>
            @endif
            @endforeach
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$('#btn-generate').on('click', function () {
    var year = $('#gen-year').val();
    var $btn = $(this).prop('disabled', true).html('<i class="bi bi-hourglass-split mr-1"></i>Generating...');
    $('#gen-progress').show();
    $('#gen-result').hide();

    $.post('{{ route("acal.generate") }}', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        start_year: year
    })
    .done(function (r) {
        var cls  = r.ok ? 'success' : 'danger';
        var html = '<div class="alert alert-'+cls+' border-0 mb-0"><strong>'+(r.ok ? '✓ Done' : '✗ Failed')+'</strong><br>'+r.msg+'</div>';
        if (r.ok && r.summary) {
            html += '<div class="row text-center mt-2">'
                +'<div class="col-4"><strong>'+r.summary.events+'</strong><br><small class="text-muted">Events</small></div>'
                +'<div class="col-4"><strong>'+r.summary.holidays+'</strong><br><small class="text-muted">Holidays</small></div>'
                +'<div class="col-4"><strong>'+r.summary.conflicts+'</strong><br><small class="text-muted">Conflicts fixed</small></div>'
                +'</div>';
            if (r.year_id) html += '<a href="{{ url("/academic-calendar") }}/'+r.year_id+'" class="btn btn-sm btn-primary btn-block mt-2">View Calendar →</a>';
        }
        $('#gen-result').html(html).show();
        if (r.ok) setTimeout(function(){ location.reload(); }, 3000);
    })
    .fail(function (xhr) {
        $('#gen-result').html('<div class="alert alert-danger border-0">'+
            (xhr.responseJSON ? xhr.responseJSON.msg : 'Server error')+
        '</div>').show();
    })
    .always(function () {
        $('#gen-progress').hide();
        $btn.prop('disabled', false).html('<i class="bi bi-play-fill mr-1"></i>Generate Academic Year');
    });
});
</script>
@endsection
