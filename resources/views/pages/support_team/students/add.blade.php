@extends('layouts.master')
@section('page_title', 'Admit Student')
@section('content')

<style>
.admission-card { border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.07); }
.step-indicator { display:flex; gap:0; margin-bottom:24px; }
.step-indicator .step { flex:1; text-align:center; padding:10px 6px; font-size:12px; font-weight:600; color:#9ca3af; border-bottom:3px solid #e5e7eb; cursor:pointer; transition:all .2s; }
.step-indicator .step.active { color:#4f46e5; border-bottom-color:#4f46e5; }
.step-indicator .step.done { color:#10b981; border-bottom-color:#10b981; }
.step-indicator .step .step-num { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; background:#e5e7eb; color:#6b7280; font-size:11px; margin-right:6px; }
.step-indicator .step.active .step-num { background:#4f46e5; color:#fff; }
.step-indicator .step.done .step-num { background:#10b981; color:#fff; }
.form-step { display:none; }
.form-step.active { display:block; }
.field-label { font-size:13px; font-weight:600; color:#374151; margin-bottom:4px; }
.field-label .req { color:#ef4444; }
.form-input { width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; color:#111827; background:#fff; transition:border-color .15s; }
.form-input:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
.form-input.error { border-color:#ef4444; }
.error-msg { font-size:11px; color:#ef4444; margin-top:3px; display:none; }
.error-msg.show { display:block; }
.nav-tabs-custom { display:flex; border-bottom:2px solid #e5e7eb; margin-bottom:0; }
.nav-tabs-custom .tab-btn { padding:10px 20px; font-size:13px; font-weight:600; color:#6b7280; border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .2s; }
.nav-tabs-custom .tab-btn.active { color:#4f46e5; border-bottom-color:#4f46e5; }
.tab-panel { display:none; padding:24px; }
.tab-panel.active { display:block; }
.ocr-box { border:1px solid #4f46e5; border-radius:8px; overflow:hidden; margin-bottom:20px; }
.ocr-box-header { background:#4f46e5; color:#fff; padding:10px 16px; font-size:13px; font-weight:600; }
.ocr-box-body { padding:16px; background:#fafbff; }
.btn-primary-custom { background:#4f46e5; color:#fff; border:none; padding:9px 20px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s; }
.btn-primary-custom:hover { background:#4338ca; }
.btn-primary-custom:disabled { background:#a5b4fc; cursor:not-allowed; }
.btn-secondary-custom { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; padding:9px 20px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.btn-success-custom { background:#10b981; color:#fff; border:none; padding:9px 20px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; }
.btn-success-custom:disabled { background:#6ee7b7; cursor:not-allowed; }
.section-title { font-size:14px; font-weight:700; color:#4f46e5; margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid #e5e7eb; }
</style>

<div class="card admission-card">
    <div class="card-header bg-white" style="padding:16px 20px;">
        <h6 class="mb-0" style="font-size:15px;font-weight:700;">Student Admission</h6>
    </div>

    {{-- Main tabs --}}
    <div class="nav-tabs-custom">
        <button class="tab-btn active" onclick="switchMainTab('single', this)">
            <i class="bi bi-person-plus mr-1"></i>Single Admission
        </button>
        <button class="tab-btn" onclick="switchMainTab('bulk', this)" id="bulk-tab-btn">
            <i class="bi bi-people-fill mr-1"></i>Bulk Admission
        </button>
    </div>

    {{-- 
         SINGLE ADMISSION TAB
     --}}
    <div class="tab-panel active" id="tab-single">

        {{-- Step indicator --}}
        <div class="step-indicator" style="margin:0;padding:0 24px;">
            <div class="step active" id="step-ind-1" onclick="goToStep(1)">
                <span class="step-num">1</span>Personal Data
            </div>
            <div class="step" id="step-ind-2" onclick="goToStep(2)">
                <span class="step-num">2</span>Student Data
            </div>
        </div>

        <form id="admission-form" method="post" enctype="multipart/form-data" action="{{ route('students.store') }}">
            @csrf

            {{--  STEP 1: Personal Data  --}}
            <div class="form-step active" id="step-1">
                <div style="padding:24px;">

                    {{-- OCR Quick Fill --}}
                    <div class="ocr-box mb-4">
                        <div class="ocr-box-header">
                            <i class="bi bi-camera-fill mr-2"></i>Quick Fill from Document
                            <small style="opacity:.7;font-weight:400;"> (Optional)</small>
                        </div>
                        <div class="ocr-box-body">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label class="field-label">Upload Birth Certificate or Student ID</label>
                                    <input type="file" id="ocr-upload" accept="image/*" class="form-input" style="padding:5px;">
                                    <small class="text-muted" style="font-size:11px;">Upload a clear photo or scan (JPEG/PNG)</small>
                                </div>
                                <div class="col-md-4"><div id="ocr-status" class="small mt-2"></div></div>
                                <div class="col-md-3 text-right">
                                    <button type="button" id="ocr-scan-btn" class="btn-primary-custom" disabled>
                                        <i class="bi bi-search mr-1"></i>Scan Document
                                    </button>
                                </div>
                            </div>
                            <div id="ocr-preview" style="display:none;margin-top:12px;">
                                <hr style="margin:10px 0;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong style="font-size:13px;"><i class="bi bi-check-circle text-success mr-1"></i>Extracted Information</strong>
                                    <div style="gap:6px;display:flex;">
                                        <button type="button" id="ocr-fill-btn" class="btn-success-custom" style="padding:5px 12px;font-size:12px;">
                                            <i class="bi bi-stars mr-1"></i>Auto-Fill Form
                                        </button>
                                        <button type="button" id="ocr-clear-btn" class="btn-secondary-custom" style="padding:5px 12px;font-size:12px;">Clear</button>
                                    </div>
                                </div>
                                <div class="row" style="font-size:13px;">
                                    <div class="col-md-4"><span class="text-muted">Full Name:</span><strong id="ocr-name" class="ml-1"></strong></div>
                                    <div class="col-md-4"><span class="text-muted">Date of Birth:</span><strong id="ocr-dob" class="ml-1"></strong></div>
                                    <div class="col-md-4"><span class="text-muted">Address / Place:</span><strong id="ocr-address" class="ml-1"></strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">Personal Information</div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="field-label">Full Name <span class="req">*</span></label>
                                <input type="text" name="name" id="f-name" value="{{ old('name') }}"
                                       class="form-input" placeholder="Full Name">
                                <div class="error-msg" id="err-name">Full Name is required.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="field-label">Address <span class="req">*</span></label>
                                <input type="text" name="address" id="f-address" value="{{ old('address') }}"
                                       class="form-input" placeholder="Address">
                                <div class="error-msg" id="err-address">Address is required.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                       class="form-input" placeholder="Email Address">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Gender <span class="req">*</span></label>
                                <select name="gender" id="f-gender" class="form-input">
                                    <option value="">-- Choose --</option>
                                    <option value="Male" {{ old('gender')=='Male' ? 'selected':'' }}>Male</option>
                                    <option value="Female" {{ old('gender')=='Female' ? 'selected':'' }}>Female</option>
                                </select>
                                <div class="error-msg" id="err-gender">Gender is required.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Phone <small style="font-weight:400;color:#9ca3af;">(09XXXXXXXX)</small></label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                       class="form-input" placeholder="e.g. 0911434321"
                                       pattern="09[0-9]{8}" title="10 digits starting with 09">
                                <div class="error-msg" id="err-phone">Must be 10 digits starting with 09.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Alternative Phone</label>
                                <input type="text" name="phone2" value="{{ old('phone2') }}"
                                       class="form-input" placeholder="e.g. 0922434321"
                                       pattern="09[0-9]{8}" title="10 digits starting with 09">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Date of Birth</label>
                                <input type="date" name="dob" id="f-dob" value="{{ old('dob') }}" class="form-input">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Nationality <span class="req">*</span></label>
                                <select name="nal_id" id="f-nal" class="form-input">
                                    <option value="">-- Choose --</option>
                                    @foreach($nationals->sortBy(fn($n) => $n->name === 'Ethiopian' ? 0 : 1) as $nal)
                                        <option value="{{ $nal->id }}" {{ old('nal_id')==$nal->id ? 'selected':'' }}>{{ $nal->name }}</option>
                                    @endforeach
                                </select>
                                <div class="error-msg" id="err-nal">Nationality is required.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Region <span class="req">*</span></label>
                                <select name="state_id" id="state_id" class="form-input" onchange="getLGA(this.value)">
                                    <option value="">-- Choose --</option>
                                    @foreach($states as $st)
                                        <option value="{{ $st->id }}" {{ old('state_id')==$st->id ? 'selected':'' }}>{{ $st->name }}</option>
                                    @endforeach
                                </select>
                                <div class="error-msg" id="err-state">Region is required.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Sub-city / Woreda <span class="req">*</span></label>
                                <select name="lga_id" id="lga_id" class="form-input">
                                    <option value="">-- Select Region First --</option>
                                </select>
                                <div class="error-msg" id="err-lga">Sub-city / Woreda is required.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="field-label">Blood Group</label>
                                <select name="bg_id" class="form-input">
                                    <option value="">-- Choose --</option>
                                    @foreach(App\Models\BloodGroup::all() as $bg)
                                        <option value="{{ $bg->id }}" {{ old('bg_id')==$bg->id ? 'selected':'' }}>{{ $bg->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="field-label d-block">Upload Passport Photo</label>
                                <input type="file" name="photo" accept="image/*" class="form-input" style="padding:5px;">
                                <small style="font-size:11px;color:#9ca3af;">Accepted: jpeg, png. Max 2MB</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn-primary-custom" onclick="goToStep(2)">
                            Next: Student Data <i class="bi bi-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{--  STEP 2: Student Data  --}}
            <div class="form-step" id="step-2">
                <div style="padding:24px;">
                    <div class="section-title">Academic Information</div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Class <span class="req">*</span></label>
                                <select name="my_class_id" id="f-class" class="form-input" onchange="getClassSections(this.value)">
                                    <option value="">-- Choose --</option>
                                    @foreach($my_classes as $c)
                                        <option value="{{ $c->id }}" {{ old('my_class_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <div class="error-msg" id="err-class">Class is required.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Section <span class="req">*</span></label>
                                <select name="section_id" id="section_id" class="form-input">
                                    <option value="">-- Select Class First --</option>
                                </select>
                                <div class="error-msg" id="err-section">Section is required.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Parent</label>
                                <select name="my_parent_id" class="form-input">
                                    <option value="">-- Choose --</option>
                                    @foreach($parents as $p)
                                        <option value="{{ Qs::hash($p->id) }}" {{ old('my_parent_id')==Qs::hash($p->id) ? 'selected':'' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="field-label">Year Admitted <span class="req">*</span></label>
                                <select name="year_admitted" id="f-year" class="form-input">
                                    <option value="">-- Choose --</option>
                                    @for($y = date('Y'); $y >= date('Y', strtotime('-10 years')); $y--)
                                        <option value="{{ $y }}" {{ old('year_admitted')==$y || $y==date('Y') ? 'selected':'' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                                <div class="error-msg" id="err-year">Year Admitted is required.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="field-label">Religion</label>
                                <select name="religion" class="form-input">
                                    <option value="">-- Choose --</option>
                                    @foreach(['Ethiopian Orthodox','Muslim','Protestant','Catholic','Traditional','Other'] as $rel)
                                        <option value="{{ $rel }}" {{ old('religion')==$rel ? 'selected':'' }}>{{ $rel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="field-label">Admission Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-input" placeholder="Auto-generated on save"
                                           readonly style="background:#f8f9fa;color:#6c757d;border-radius:6px 0 0 6px;">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="border-radius:0 6px 6px 0;">
                                            <i class="bi bi-lock-fill text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                                <small style="font-size:11px;color:#9ca3af;">Format: STM-{{ date('Y') }}-XXXX</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn-secondary-custom" onclick="goToStep(1)">
                            <i class="bi bi-arrow-left mr-1"></i>Back
                        </button>
                        <button type="submit" id="submit-btn" class="btn-primary-custom">
                            <i class="bi bi-person-check mr-1"></i>Admit Student
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>{{-- end tab-single --}}


    {{-- ═══════════════════════════════════════════════════════
         BULK ADMISSION TAB
    ═══════════════════════════════════════════════════════ --}}
    <div class="tab-panel" id="tab-bulk">

        <div class="alert alert-info border-0 mb-4" style="border-radius:8px;">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill mr-3 mt-1" style="font-size:18px;"></i>
                <div>
                    <strong>Bulk Student Admission via CSV</strong><br>
                    Upload a CSV file to admit multiple students at once. Each row becomes one student record.
                    Admission numbers are auto-generated. Default password is <code>student</code>.
                    <a href="{{ route('students.bulk.template') }}" class="ml-2 font-weight-bold">
                        <i class="bi bi-download mr-1"></i>Download CSV Template
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered" style="font-size:12px;">
                <thead class="thead-light">
                    <tr>
                        <th>Column</th><th>name</th><th>gender</th><th>email</th><th>phone</th>
                        <th>dob</th><th>address</th><th>class_name</th><th>section_name</th>
                        <th>year_admitted</th><th>religion</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-weight-bold">Example</td>
                        <td>Abebe Kebede</td><td>Male</td><td>abebe@email.com</td><td>0911234567</td>
                        <td>2010-05-12</td><td>Addis Ababa</td><td>Grade 1</td><td>A</td>
                        <td>{{ date('Y') }}</td><td>Ethiopian Orthodox</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form id="bulk-upload-form" method="post" enctype="multipart/form-data" action="{{ route('students.bulk.import') }}">
            @csrf
            <div class="row align-items-end">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="field-label">Select CSV File <span class="req">*</span></label>
                        <input type="file" name="csv_file" id="bulk-csv-file" accept=".csv,text/csv" class="form-input" style="padding:5px;" required>
                        <small style="font-size:11px;color:#9ca3af;">Max 5MB. UTF-8 encoded CSV only.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="field-label">Default Section (fallback)</label>
                        <select name="default_section_id" class="form-input">
                            <option value="">-- Choose --</option>
                            @foreach(App\Models\Section::all() as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <button type="button" id="bulk-preview-btn" class="btn-primary-custom" style="width:100%;background:#0ea5e9;">
                            <i class="bi bi-eye mr-1"></i>Preview CSV
                        </button>
                    </div>
                </div>
            </div>

            <div id="bulk-preview-area" style="display:none;" class="mb-3">
                <h6 class="font-weight-semibold mb-2">
                    <i class="bi bi-table mr-1"></i>Preview
                    <span id="bulk-row-count" class="badge badge-primary ml-1"></span>
                </h6>
                <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover" id="bulk-preview-table">
                        <thead class="thead-dark" id="bulk-preview-head"></thead>
                        <tbody id="bulk-preview-body"></tbody>
                    </table>
                </div>
                <div id="bulk-validation-errors" class="mt-2"></div>
            </div>

            <div class="d-flex" style="gap:10px;">
                <button type="submit" id="bulk-submit-btn" class="btn-success-custom" disabled>
                    <i class="bi bi-cloud-upload mr-1"></i>Import Students
                </button>
                <button type="reset" class="btn-secondary-custom" onclick="resetBulkForm()">
                    <i class="bi bi-x-circle mr-1"></i>Reset
                </button>
            </div>
        </form>

        <div id="bulk-result" class="mt-3" style="display:none;"></div>
    </div>{{-- end tab-bulk --}}

</div>{{-- end card --}}

@endsection

@section('scripts')
<script>
//  Main tab switcher 
function switchMainTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

//  Step navigation 
function goToStep(n) {
    if (n === 2 && !validateStep1()) return;
    document.querySelectorAll('.form-step').forEach(function(s){ s.classList.remove('active'); });
    document.getElementById('step-' + n).classList.add('active');
    // Update step indicators
    for (var i = 1; i <= 2; i++) {
        var ind = document.getElementById('step-ind-' + i);
        ind.classList.remove('active', 'done');
        if (i < n) ind.classList.add('done');
        else if (i === n) ind.classList.add('active');
    }
    document.documentElement.scrollTop = 0;
}

//  Step 1 validation 
function validateStep1() {
    var ok = true;
    function check(id, errId, condition) {
        var el = document.getElementById(id);
        var err = document.getElementById(errId);
        if (condition) {
            el.classList.add('error'); err.classList.add('show'); ok = false;
        } else {
            el.classList.remove('error'); err.classList.remove('show');
        }
    }
    check('f-name',    'err-name',    !document.getElementById('f-name').value.trim());
    check('f-address', 'err-address', !document.getElementById('f-address').value.trim());
    check('f-gender',  'err-gender',  !document.getElementById('f-gender').value);
    check('f-nal',     'err-nal',     !document.getElementById('f-nal').value);
    check('state_id',  'err-state',   !document.getElementById('state_id').value);
    check('lga_id',    'err-lga',     !document.getElementById('lga_id').value);

    // Phone pattern check
    var phone = document.querySelector('input[name="phone"]');
    if (phone.value && !/^09[0-9]{8}$/.test(phone.value)) {
        phone.classList.add('error');
        document.getElementById('err-phone').classList.add('show');
        ok = false;
    } else {
        phone.classList.remove('error');
        document.getElementById('err-phone').classList.remove('show');
    }

    if (!ok) {
        var first = document.querySelector('#step-1 .error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return ok;
}

//  Step 2 validation 
function validateStep2() {
    var ok = true;
    function check(id, errId) {
        var el = document.getElementById(id);
        var err = document.getElementById(errId);
        if (!el.value) { el.classList.add('error'); err.classList.add('show'); ok = false; }
        else { el.classList.remove('error'); err.classList.remove('show'); }
    }
    check('f-class',   'err-class');
    check('section_id','err-section');
    check('f-year',    'err-year');
    return ok;
}

//  Form submit 
document.getElementById('admission-form').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!validateStep2()) return;

    var btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Saving...';

    var fd = new FormData(this);
    fetch(this.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r){ return r.json(); })
    .then(function(resp) {
        if (resp.ok) {
            flash({ msg: resp.msg || 'Student admitted successfully.', type: 'success' });
            document.getElementById('admission-form').reset();
            document.getElementById('section_id').innerHTML = '<option value="">-- Select Class First --</option>';
            document.getElementById('lga_id').innerHTML = '<option value="">-- Select Region First --</option>';
            goToStep(1);
        } else {
            flash({ msg: resp.msg || 'Error saving student.', type: 'danger' });
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-check mr-1"></i>Admit Student';
    })
    .catch(function(err) {
        flash({ msg: 'Server error. Please try again.', type: 'danger' });
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-check mr-1"></i>Admit Student';
    });
});

//  OCR 
var ocrData = { name: '', dob: '', address: '' };

document.getElementById('ocr-upload').addEventListener('change', function() {
    document.getElementById('ocr-scan-btn').disabled = !(this.files && this.files.length > 0);
    if (this.files && this.files.length)
        document.getElementById('ocr-status').innerHTML = '<span class="text-success"><i class="bi bi-file-image mr-1"></i>Ready to scan</span>';
});

document.getElementById('ocr-scan-btn').addEventListener('click', function() {
    var file = document.getElementById('ocr-upload').files[0];
    if (!file) return;
    var btn = this;
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Loading OCR...';
    document.getElementById('ocr-status').innerHTML = '<span class="text-muted">Loading OCR engine...</span>';
    if (typeof Tesseract === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        s.onload = function() { runOCR(file, btn); };
        s.onerror = function() {
            document.getElementById('ocr-status').innerHTML = '<span class="text-danger">Failed to load OCR library.</span>';
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-search mr-1"></i>Scan Document';
        };
        document.head.appendChild(s);
    } else { runOCR(file, btn); }
});

function runOCR(file, btn) {
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Scanning...';
    document.getElementById('ocr-status').innerHTML = '<div class="progress" style="height:8px;"><div id="ocr-progress-bar" class="progress-bar bg-primary" style="width:0%"></div></div><small class="text-muted">Processing...</small>';
    var reader = new FileReader();
    reader.onload = function(e) {
        Tesseract.recognize(e.target.result, 'eng', {
            logger: function(m) {
                if (m.status === 'recognizing text')
                    document.getElementById('ocr-progress-bar').style.width = Math.round(m.progress * 100) + '%';
            }
        }).then(function(r) {
            ocrData = extractFromText(r.data.text);
            document.getElementById('ocr-name').textContent    = ocrData.name    || 'Not detected';
            document.getElementById('ocr-dob').textContent     = ocrData.dob     || 'Not detected';
            document.getElementById('ocr-address').textContent = ocrData.address || 'Not detected';
            document.getElementById('ocr-preview').style.display = 'block';
            document.getElementById('ocr-status').innerHTML = '<span class="text-success"><i class="bi bi-check-circle mr-1"></i>Scan complete.</span>';
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-search mr-1"></i>Scan Again';
        }).catch(function() {
            document.getElementById('ocr-status').innerHTML = '<span class="text-danger">Scan failed. Try a clearer image.</span>';
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-search mr-1"></i>Scan Document';
        });
    };
    reader.readAsDataURL(file);
}

function extractFromText(text) {
    var r = { name: '', dob: '', address: '' };
    var nm = text.match(/\b([A-Z][a-z]{1,20}\s+[A-Z][a-z]{1,20}(?:\s+[A-Z][a-z]{1,20})?)\b/);
    if (nm) r.name = nm[1].trim();
    var dp = [/\b(\d{2}[\/\-\.]\d{2}[\/\-\.]\d{4})\b/, /\b(\d{4}[\/\-\.]\d{2}[\/\-\.]\d{2})\b/, /(?:Date\s*of\s*Birth|DOB|Born)[:\s]+([^\n\r,]{6,20})/i];
    for (var i = 0; i < dp.length; i++) { var m = text.match(dp[i]); if (m) { r.dob = m[1].trim(); break; } }
    var ap = [/(?:Address|Place\s*of\s*Birth|Residence)[:\s]+([^\n\r]{5,60})/i, /(?:Addis\s+Ababa|Addis\s+Abeba)/i, /(?:Kebele|Woreda|Sub-?city)[:\s]+([^\n\r]{3,40})/i];
    for (var j = 0; j < ap.length; j++) { var am = text.match(ap[j]); if (am) { r.address = (am[1] || am[0]).trim(); break; } }
    return r;
}

document.getElementById('ocr-fill-btn').addEventListener('click', function() {
    var filled = 0;
    if (ocrData.name) { document.querySelector('input[name="name"]').value = ocrData.name; filled++; }
    if (ocrData.dob) {
        var n = normaliseDateForPicker(ocrData.dob);
        if (n) { document.getElementById('f-dob').value = n; filled++; }
    }
    if (ocrData.address) { document.querySelector('input[name="address"]').value = ocrData.address; filled++; }
    flash({ msg: filled > 0 ? filled + ' field(s) filled. Please verify.' : 'No data extracted. Fill manually.', type: filled > 0 ? 'success' : 'warning' });
});

function normaliseDateForPicker(str) {
    var m = str.match(/^(\d{4})[\/\-\.](\d{2})[\/\-\.](\d{2})$/); if (m) return m[1] + '-' + m[2] + '-' + m[3];
    m = str.match(/^(\d{2})[\/\-\.](\d{2})[\/\-\.](\d{4})$/); if (m) return m[3] + '-' + m[2] + '-' + m[1];
    return str;
}

document.getElementById('ocr-clear-btn').addEventListener('click', function() {
    ocrData = { name: '', dob: '', address: '' };
    document.getElementById('ocr-upload').value = '';
    document.getElementById('ocr-preview').style.display = 'none';
    document.getElementById('ocr-scan-btn').disabled = true;
    document.getElementById('ocr-scan-btn').innerHTML = '<i class="bi bi-search mr-1"></i>Scan Document';
    document.getElementById('ocr-status').innerHTML = '';
});

//  Bulk CSV Preview 
document.getElementById('bulk-csv-file').addEventListener('change', function() {
    document.getElementById('bulk-preview-area').style.display = 'none';
    document.getElementById('bulk-submit-btn').disabled = true;
    document.getElementById('bulk-preview-head').innerHTML = '';
    document.getElementById('bulk-preview-body').innerHTML = '';
    document.getElementById('bulk-validation-errors').innerHTML = '';
});

document.getElementById('bulk-preview-btn').addEventListener('click', function() {
    var file = document.getElementById('bulk-csv-file').files[0];
    if (!file) { flash({ msg: 'Please select a CSV file first.', type: 'warning' }); return; }
    var reader = new FileReader();
    reader.onload = function(e) {
        var lines = e.target.result.split(/\r?\n/).filter(function(l) { return l.trim(); });
        if (lines.length < 2) { flash({ msg: 'CSV must have a header row and at least one data row.', type: 'warning' }); return; }
        var headers = lines[0].split(',').map(function(h) { return h.trim(); });
        var headRow = '<tr>' + headers.map(function(h) { return '<th>' + h + '</th>'; }).join('') + '<th>Status</th></tr>';
        document.getElementById('bulk-preview-head').innerHTML = headRow;
        var bodyHtml = '', errors = [], validRows = 0;
        for (var i = 1; i < Math.min(lines.length, 51); i++) {
            var cols = lines[i].split(',').map(function(c) { return c.trim(); });
            var rowErrors = [];
            var nameIdx = headers.indexOf('name'), genderIdx = headers.indexOf('gender'), classIdx = headers.indexOf('class_name');
            if (nameIdx >= 0 && (!cols[nameIdx] || cols[nameIdx].length < 3)) rowErrors.push('Name too short');
            if (genderIdx >= 0 && cols[genderIdx] && !['Male','Female'].includes(cols[genderIdx])) rowErrors.push('Gender must be Male/Female');
            if (classIdx >= 0 && !cols[classIdx]) rowErrors.push('Class required');
            var statusCell = rowErrors.length
                ? '<td><span class="badge badge-danger">' + rowErrors.join(', ') + '</span></td>'
                : '<td><span class="badge badge-success">OK</span></td>';
            if (rowErrors.length) errors.push('Row ' + i + ': ' + rowErrors.join(', '));
            else validRows++;
            bodyHtml += '<tr>' + cols.map(function(c) { return '<td>' + c + '</td>'; }).join('') + statusCell + '</tr>';
        }
        if (lines.length > 51) bodyHtml += '<tr><td colspan="' + (headers.length + 1) + '" class="text-center text-muted">... and ' + (lines.length - 51) + ' more rows</td></tr>';
        document.getElementById('bulk-preview-body').innerHTML = bodyHtml;
        document.getElementById('bulk-row-count').textContent = (lines.length - 1) + ' rows';
        document.getElementById('bulk-preview-area').style.display = 'block';
        if (errors.length) {
            document.getElementById('bulk-validation-errors').innerHTML = '<div class="alert alert-warning border-0"><strong>' + errors.length + ' row(s) have issues:</strong><ul class="mb-0 mt-1">' + errors.map(function(e) { return '<li>' + e + '</li>'; }).join('') + '</ul></div>';
        }
        document.getElementById('bulk-submit-btn').disabled = validRows === 0;
    };
    reader.readAsText(file);
});

document.getElementById('bulk-upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('bulk-submit-btn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Importing...';
    var fd = new FormData(this);
    fetch(this.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { return r.json(); })
    .then(function(r) {
        var html = '<div class="alert alert-' + (r.ok ? 'success' : 'danger') + ' border-0"><strong>' + (r.ok ? 'Import Complete' : 'Import Failed') + '</strong><br>' + r.msg + '</div>';
        if (r.errors && r.errors.length) html += '<ul class="list-group mt-2">' + r.errors.map(function(e) { return '<li class="list-group-item list-group-item-danger py-1 small">' + e + '</li>'; }).join('') + '</ul>';
        document.getElementById('bulk-result').innerHTML = html;
        document.getElementById('bulk-result').style.display = 'block';
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-cloud-upload mr-1"></i>Import Students';
    })
    .catch(function(err) {
        document.getElementById('bulk-result').innerHTML = '<div class="alert alert-danger border-0">Server error. Please try again.</div>';
        document.getElementById('bulk-result').style.display = 'block';
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-cloud-upload mr-1"></i>Import Students';
    });
});

function resetBulkForm() {
    document.getElementById('bulk-preview-area').style.display = 'none';
    document.getElementById('bulk-result').style.display = 'none';
    document.getElementById('bulk-submit-btn').disabled = true;
    document.getElementById('bulk-preview-head').innerHTML = '';
    document.getElementById('bulk-preview-body').innerHTML = '';
    document.getElementById('bulk-validation-errors').innerHTML = '';
}

//  Open bulk tab if URL has #tab-bulk 
if (window.location.hash === '#tab-bulk') {
    switchMainTab('bulk', document.getElementById('bulk-tab-btn'));
}
</script>
@endsection
