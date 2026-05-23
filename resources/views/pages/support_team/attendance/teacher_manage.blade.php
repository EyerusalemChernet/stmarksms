<style>
.att-toolbar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.att-student-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}
.att-student-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    transition: border-color .12s, box-shadow .12s;
}
.att-student-card.status-present { border-color: #86efac; background: #f0fdf4; }
.att-student-card.status-late { border-color: #fde68a; background: #fffbeb; }
.att-student-card.status-absent { border-color: #fca5a5; background: #fef2f2; }
.att-status-btn {
    flex: 1;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    border-radius: 8px;
    padding: 8px 4px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all .1s;
    text-align: center;
}
.att-status-btn.active-present { background: #10b981; border-color: #10b981; color: #fff; }
.att-status-btn.active-late { background: #f59e0b; border-color: #f59e0b; color: #fff; }
.att-status-btn.active-absent { background: #ef4444; border-color: #ef4444; color: #fff; }
.att-bulk-bar { display: flex; gap: 8px; flex-wrap: wrap; }
.att-bulk-bar .btn { font-size: 12px; font-weight: 600; }
</style>

<div class="att-toolbar">
    <div>
        <div style="font-weight:800;font-size:17px;color:#1e293b;">
            {{ $session->my_class->name }} — Section {{ $session->section->name }}
        </div>
        <div style="font-size:13px;color:#64748b;">
            <i class="bi bi-calendar3 mr-1"></i>{{ \Carbon\Carbon::parse($session->date)->format('l, d M Y') }}
            <span class="mx-2">·</span>
            <span id="att-count-present" class="text-success font-weight-bold">0</span> present,
            <span id="att-count-late" class="text-warning font-weight-bold">0</span> late,
            <span id="att-count-absent" class="text-danger font-weight-bold">0</span> absent
        </div>
    </div>
    <div class="att-bulk-bar">
        <button type="button" class="btn btn-outline-success btn-sm" onclick="setAllStatus('present')">All Present</button>
        <button type="button" class="btn btn-outline-warning btn-sm" onclick="setAllStatus('late')">All Late</button>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="setAllStatus('absent')">All Absent</button>
    </div>
</div>

@if($students->count() < 1)
<div class="alert alert-warning border-0">No students found in this class/section.</div>
@else
<form method="POST" action="{{ route('attendance.store', $session->id) }}" id="att-save-form">
    @csrf
    <div class="att-student-grid">
        @foreach($students->sortBy('user.name') as $st)
        @php $current = $existing[$st->user_id] ?? 'present'; @endphp
        <div class="att-student-card status-{{ $current }}" id="card-{{ $st->user_id }}" data-student="{{ $st->user_id }}">
            <div class="d-flex align-items-center mb-3" style="gap:10px;">
                <img src="{{ $st->user->photo }}" class="rounded-circle" width="40" height="40" alt="">
                <div style="min-width:0;flex:1;">
                    <div style="font-weight:700;font-size:14px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $st->user->name }}</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $st->adm_no }}</div>
                </div>
            </div>
            <div class="d-flex" style="gap:6px;">
                <button type="button" class="att-status-btn {{ $current === 'present' ? 'active-present' : '' }}"
                        data-status="present" data-student="{{ $st->user_id }}"
                        onclick="setStudentStatus({{ $st->user_id }}, 'present', this)">
                    <i class="bi bi-check-circle d-block" style="font-size:14px;"></i>Present
                </button>
                <button type="button" class="att-status-btn {{ $current === 'late' ? 'active-late' : '' }}"
                        data-status="late" data-student="{{ $st->user_id }}"
                        onclick="setStudentStatus({{ $st->user_id }}, 'late', this)">
                    <i class="bi bi-clock d-block" style="font-size:14px;"></i>Late
                </button>
                <button type="button" class="att-status-btn {{ $current === 'absent' ? 'active-absent' : '' }}"
                        data-status="absent" data-student="{{ $st->user_id }}"
                        onclick="setStudentStatus({{ $st->user_id }}, 'absent', this)">
                    <i class="bi bi-x-circle d-block" style="font-size:14px;"></i>Absent
                </button>
            </div>
            <input type="radio" name="status_{{ $st->user_id }}" value="present" class="d-none att-radio"
                   id="radio-{{ $st->user_id }}-present" {{ $current === 'present' ? 'checked' : '' }}>
            <input type="radio" name="status_{{ $st->user_id }}" value="late" class="d-none att-radio"
                   id="radio-{{ $st->user_id }}-late" {{ $current === 'late' ? 'checked' : '' }}>
            <input type="radio" name="status_{{ $st->user_id }}" value="absent" class="d-none att-radio"
                   id="radio-{{ $st->user_id }}-absent" {{ $current === 'absent' ? 'checked' : '' }}>
        </div>
        @endforeach
    </div>

    <div style="position:sticky;bottom:16px;margin-top:24px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 20px;box-shadow:0 -4px 20px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <a href="{{ route('attendance.index') }}" class="btn btn-light"><i class="bi bi-arrow-left mr-1"></i> Back</a>
        <button type="submit" class="btn btn-success btn-lg px-4">
            <i class="bi bi-check2-all mr-1"></i> Save Attendance
        </button>
    </div>
</form>
@endif

<script>
function setStudentStatus(studentId, status, btn) {
    var card = document.getElementById('card-' + studentId);
    card.className = 'att-student-card status-' + status;
    card.querySelectorAll('.att-status-btn').forEach(function(b) {
        b.classList.remove('active-present', 'active-late', 'active-absent');
    });
    btn.classList.add('active-' + status);
    document.getElementById('radio-' + studentId + '-' + status).checked = true;
    updateCounts();
}
function setAllStatus(status) {
    document.querySelectorAll('.att-student-card').forEach(function(card) {
        var sid = card.getAttribute('data-student');
        var btn = card.querySelector('.att-status-btn[data-status="' + status + '"]');
        if (btn) setStudentStatus(sid, status, btn);
    });
}
function updateCounts() {
    var p = 0, l = 0, a = 0;
    document.querySelectorAll('.att-radio:checked').forEach(function(r) {
        if (r.value === 'present') p++;
        else if (r.value === 'late') l++;
        else if (r.value === 'absent') a++;
    });
    document.getElementById('att-count-present').textContent = p;
    document.getElementById('att-count-late').textContent = l;
    document.getElementById('att-count-absent').textContent = a;
}
updateCounts();
</script>
