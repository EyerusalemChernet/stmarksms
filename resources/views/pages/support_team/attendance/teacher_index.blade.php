<style>
.att-hero {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 16px;
    padding: 24px 28px;
    color: #fff;
    margin-bottom: 24px;
    box-shadow: 0 8px 28px rgba(79,70,229,.28);
}
.att-section-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 20px;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none !important;
    display: block;
    color: inherit;
}
.att-section-card:hover, .att-section-card.selected {
    border-color: #4f46e5;
    box-shadow: 0 4px 16px rgba(79,70,229,.12);
    transform: translateY(-1px);
}
.att-section-card.selected { background: #f5f3ff; }
</style>

<div class="att-hero">
    <div style="font-size:12px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">Homeroom Attendance</div>
    <h4 style="font-weight:800;margin:8px 0 4px;">Mark Today's Attendance</h4>
    <p style="margin:0;opacity:.9;font-size:13px;">Choose your class section and date, then mark all students in one screen.</p>
</div>

@if($homeroom_sections->isEmpty())
<div class="alert alert-warning border-0" style="border-radius:12px;">
    <i class="bi bi-exclamation-triangle mr-2"></i>
    You are not assigned as a homeroom teacher. Contact the administrator to assign you to a section.
</div>
@else
<form method="POST" action="{{ route('attendance.create') }}" id="att-open-form">
    @csrf
    <input type="hidden" name="my_class_id" id="att_class_id">
    <input type="hidden" name="section_id" id="att_section_id">

    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">1. Select homeroom</div>
    <div class="row mb-4">
        @foreach($homeroom_sections as $sec)
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="att-section-card" data-class="{{ $sec->my_class_id }}" data-section="{{ $sec->id }}"
                 onclick="selectHomeroom(this, {{ $sec->my_class_id }}, {{ $sec->id }})">
                <div class="d-flex align-items-center" style="gap:12px;">
                    <div style="background:#ede9fe;border-radius:10px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-people-fill" style="color:#4f46e5;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:15px;color:#1e293b;">{{ $sec->my_class->name ?? 'Class' }}</div>
                        <div style="font-size:12px;color:#64748b;">Section {{ $sec->name }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">2. Pick date</div>
    <div class="row align-items-end">
        <div class="col-md-4">
            <label class="font-weight-semibold">Attendance date</label>
            <input type="date" name="date" required class="form-control form-control-lg"
                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary btn-lg btn-block" id="att-open-btn" disabled>
                <i class="bi bi-play-circle mr-1"></i> Open Attendance Sheet
            </button>
        </div>
    </div>
</form>
@endif

<script>
function selectHomeroom(el, classId, sectionId) {
    document.querySelectorAll('.att-section-card').forEach(function(c){ c.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('att_class_id').value = classId;
    document.getElementById('att_section_id').value = sectionId;
    document.getElementById('att-open-btn').disabled = false;
}
(function(){
    var cards = document.querySelectorAll('.att-section-card');
    if (cards.length === 1) {
        cards[0].click();
    }
})();
</script>
