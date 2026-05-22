@extends('layouts.master')
@section('page_title', 'Manage Subjects')
@section('content')

<style>
.subject-card { border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:10px; background:#fff; transition:box-shadow .15s; }
.subject-card:hover { box-shadow:0 2px 10px rgba(0,0,0,.08); }
.badge-class { display:inline-block; background:#ede9fe; color:#6d28d9; border-radius:6px; padding:2px 8px; font-size:11px; font-weight:600; margin:2px; }
.tab-btn-custom { padding:10px 20px; font-size:13px; font-weight:600; color:#64748b; border:none; background:none; cursor:pointer; border-bottom:3px solid transparent; transition:all .2s; }
.tab-btn-custom.active { color:#4f46e5; border-bottom-color:#4f46e5; }
.tab-panel-custom { display:none; padding:20px 0; }
.tab-panel-custom.active { display:block; }
.assign-class-cb { display:flex; align-items:center; gap:8px; padding:7px 10px; border:1px solid #e2e8f0; border-radius:7px; cursor:pointer; font-size:13px; transition:background .15s; }
.assign-class-cb:hover { background:#f8fafc; }
.assign-class-cb input[type=checkbox]:checked + span { color:#4f46e5; font-weight:600; }
</style>

<div class="card">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0"><i class="bi bi-book mr-2 text-primary"></i>Manage Subjects</h6>
    </div>
    <div class="card-body">

        {{-- Tab bar --}}
        <div style="display:flex;border-bottom:2px solid #e5e7eb;margin-bottom:0;">
            <button class="tab-btn-custom active" onclick="switchTab('catalog',this)">
                <i class="bi bi-collection mr-1"></i>Subject Catalog
                <span class="badge badge-primary ml-1">{{ $masters->count() }}</span>
            </button>
            <button class="tab-btn-custom" onclick="switchTab('assign',this)">
                <i class="bi bi-diagram-3 mr-1"></i>Assign to Classes
            </button>
            <button class="tab-btn-custom" onclick="switchTab('byclass',this)">
                <i class="bi bi-grid-3x3-gap mr-1"></i>View by Class
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════════
             TAB 1 — SUBJECT CATALOG (master subjects)
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-panel-custom active" id="tab-catalog">

            <div class="row">
                {{-- Add new master subject --}}
                <div class="col-md-4">
                    <div class="card border-0 bg-light" style="border-radius:10px;">
                        <div class="card-body">
                            <h6 class="font-weight-700 mb-3" style="font-size:14px;">
                                <i class="bi bi-plus-circle text-primary mr-1"></i>Add New Subject
                            </h6>
                            <form class="ajax-store" method="POST" action="{{ route('master_subjects.store') }}">
                                @csrf
                                <div class="form-group mb-2">
                                    <label style="font-size:12px;font-weight:600;">Subject Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" required class="form-control form-control-sm" placeholder="e.g. Mathematics">
                                </div>
                                <div class="form-group mb-2">
                                    <label style="font-size:12px;font-weight:600;">Code / Short Name</label>
                                    <input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. MATH" maxlength="20"
                                           oninput="this.value=this.value.toUpperCase()">
                                    <small class="text-muted" style="font-size:11px;">Used on report cards and timetables</small>
                                </div>
                                <div class="form-group mb-3">
                                    <label style="font-size:12px;font-weight:600;">Description</label>
                                    <input type="text" name="description" class="form-control form-control-sm" placeholder="Optional">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                    <i class="bi bi-plus mr-1"></i>Add to Catalog
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Catalog list --}}
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" style="font-size:14px;font-weight:700;">All Subjects ({{ $masters->count() }})</h6>
                        <input type="text" id="catalog-search" class="form-control form-control-sm" style="width:200px;" placeholder="Search subjects...">
                    </div>

                    <div id="catalog-list">
                    @forelse($masters as $m)
                    <div class="subject-card d-flex justify-content-between align-items-start catalog-item" data-name="{{ strtolower($m->name) }}">
                        <div style="flex:1;">
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <strong style="font-size:14px;">{{ $m->name }}</strong>
                                @if($m->code)
                                <span style="background:#dbeafe;color:#1d4ed8;border-radius:5px;padding:1px 7px;font-size:11px;font-weight:700;">{{ $m->code }}</span>
                                @endif
                            </div>
                            @if($m->description)
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $m->description }}</div>
                            @endif
                            <div style="margin-top:6px;">
                                <span style="font-size:12px;color:#94a3b8;">
                                    <i class="bi bi-diagram-3 mr-1"></i>
                                    Assigned to <strong>{{ $m->class_subjects_count }}</strong> class(es)
                                </span>
                            </div>
                        </div>
                        <div class="d-flex" style="gap:6px;flex-shrink:0;margin-left:12px;">
                            {{-- Quick assign button --}}
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                    onclick="quickAssign({{ $m->id }}, '{{ addslashes($m->name) }}')"
                                    title="Assign to classes">
                                <i class="bi bi-diagram-3"></i> Assign
                            </button>
                            {{-- Edit --}}
                            @if(Qs::userIsTeamSA())
                            <button type="button" class="btn btn-xs btn-outline-secondary"
                                    onclick="editMaster({{ $m->id }}, '{{ addslashes($m->name) }}', '{{ $m->code }}', '{{ addslashes($m->description ?? '') }}')"
                                    title="Edit subject">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endif
                            {{-- Delete --}}
                            @if(Qs::userIsSuperAdmin())
                            <form method="POST" action="{{ route('master_subjects.destroy', $m->id) }}"
                                  onsubmit="return confirm('Delete \'{{ $m->name }}\' and remove it from ALL {{ $m->class_subjects_count }} class(es)? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete subject">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-book" style="font-size:36px;opacity:.3;"></i>
                        <p class="mt-2">No subjects yet. Add your first subject using the form.</p>
                    </div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             TAB 2 — ASSIGN SUBJECT TO CLASSES
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-panel-custom" id="tab-assign">

            <div class="row">
                <div class="col-md-7">
                    <form id="assign-form" method="POST" action="{{ route('subjects.assign') }}">
                        @csrf

                        <div class="form-group">
                            <label class="font-weight-semibold" style="font-size:13px;">
                                Subject <span class="text-danger">*</span>
                            </label>
                            <select name="master_subject_id" id="assign-subject-select" required
                                    class="form-control select-search" data-placeholder="Choose a subject from catalog">
                                <option value=""></option>
                                @foreach($masters as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}{{ $m->code ? ' ('.$m->code.')' : '' }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size:11px;">
                                Subject not in the list?
                                <a href="#" onclick="switchTab('catalog', document.querySelector('.tab-btn-custom'));return false;">Add it to the catalog first →</a>
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-semibold" style="font-size:13px;">Department (optional)</label>
                            <select name="department_id" class="form-control select-search" data-placeholder="Select department">
                                <option value=""></option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-semibold mb-0" style="font-size:13px;">
                                    Assign to Classes <span class="text-danger">*</span>
                                </label>
                                <div style="gap:8px;display:flex;">
                                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="selectAllClasses(true)">Select All</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="selectAllClasses(false)">Clear</button>
                                </div>
                            </div>
                            <div class="row" id="class-checkboxes">
                                @foreach($my_classes as $c)
                                <div class="col-md-6 mb-2">
                                    <label class="assign-class-cb">
                                        <input type="checkbox" name="class_ids[]" value="{{ $c->id }}">
                                        <span>{{ $c->name }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <small class="text-muted" style="font-size:11px;">Already-assigned classes will be skipped automatically.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-diagram-3 mr-1"></i>Assign Subject to Selected Classes
                        </button>
                    </form>
                </div>

                <div class="col-md-5">
                    <div class="card border-0" style="background:#f8fafc;border-radius:10px;">
                        <div class="card-body">
                            <h6 style="font-size:13px;font-weight:700;color:#374151;">
                                <i class="bi bi-info-circle text-primary mr-1"></i>How it works
                            </h6>
                            <ol style="font-size:13px;color:#475569;padding-left:18px;line-height:2;">
                                <li>Pick a subject from the catalog</li>
                                <li>Optionally assign a department</li>
                                <li>Tick the classes that should offer it</li>
                                <li>Click <strong>Assign</strong></li>
                            </ol>
                            <hr style="border-color:#e2e8f0;">
                            <p style="font-size:12px;color:#94a3b8;margin:0;">
                                Each subject exists <strong>once</strong> in the catalog. Assigning it to a class creates a link — not a duplicate. Marks, timetables and reports all reference the same subject.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             TAB 3 — VIEW BY CLASS
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-panel-custom" id="tab-byclass">

            <div class="row mb-3">
                <div class="col-md-4">
                    <select id="class-filter" class="form-control select" data-placeholder="Filter by class"
                            onchange="filterByClass(this.value)">
                        <option value="all">All Classes</option>
                        @foreach($my_classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table class="table table-hover datatable-button-html5-columns" style="font-size:13px;">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Code</th>
                        <th>Class</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($subjects as $s)
                <tr class="class-row" data-class="{{ $s->my_class_id }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $s->name }}</strong>
                        @if($s->masterSubject)
                        <span style="font-size:10px;color:#94a3b8;display:block;">from catalog</span>
                        @else
                        <span style="font-size:10px;color:#f59e0b;display:block;">legacy (not linked)</span>
                        @endif
                    </td>
                    <td><span style="font-family:monospace;font-size:12px;">{{ $s->slug ?: '—' }}</span></td>
                    <td>{{ $s->my_class->name ?? '—' }}</td>
                    <td>{{ $s->assignedLabel() }}</td>
                    <td>
                        <div class="d-flex" style="gap:4px;">
                            @if(Qs::userIsTeamSA())
                            <a href="{{ route('subjects.edit', $s->id) }}" class="btn btn-xs btn-outline-secondary" title="Edit department">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(Qs::userIsSuperAdmin())
                            <form method="POST" action="{{ route('subjects.destroy', $s->id) }}"
                                  onsubmit="return confirm('Remove this subject from {{ $s->my_class->name ?? 'this class' }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Remove from class">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>

{{-- Edit Master Subject Modal --}}
<div id="edit-master-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <h6 style="font-weight:700;margin-bottom:20px;">Edit Subject</h6>
        <form id="edit-master-form" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label style="font-size:13px;font-weight:600;">Subject Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="edit-master-name" required class="form-control">
            </div>
            <div class="form-group">
                <label style="font-size:13px;font-weight:600;">Code</label>
                <input type="text" name="code" id="edit-master-code" class="form-control" maxlength="20"
                       oninput="this.value=this.value.toUpperCase()">
            </div>
            <div class="form-group">
                <label style="font-size:13px;font-weight:600;">Description</label>
                <input type="text" name="description" id="edit-master-desc" class="form-control">
            </div>
            <div class="d-flex justify-content-end" style="gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel-custom').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn-custom').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-' + name).classList.add('active');
    if (btn) btn.classList.add('active');
}

// ── Catalog search ────────────────────────────────────────────────────────────
document.getElementById('catalog-search').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.catalog-item').forEach(function(el) {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});

// ── Select / deselect all classes ─────────────────────────────────────────────
function selectAllClasses(state) {
    document.querySelectorAll('#class-checkboxes input[type=checkbox]').forEach(function(cb) {
        cb.checked = state;
    });
}

// ── Quick assign from catalog tab ─────────────────────────────────────────────
function quickAssign(masterId, masterName) {
    // Switch to assign tab and pre-select the subject
    switchTab('assign', document.querySelectorAll('.tab-btn-custom')[1]);
    var sel = document.getElementById('assign-subject-select');
    if (sel) {
        sel.value = masterId;
        // Trigger Select2 update if loaded
        if (typeof $ !== 'undefined' && $(sel).data('select2')) {
            $(sel).val(masterId).trigger('change');
        }
    }
}

// ── Edit master modal ─────────────────────────────────────────────────────────
function editMaster(id, name, code, desc) {
    var baseUrl = '{{ url("master-subjects") }}/' + id;
    document.getElementById('edit-master-form').action = baseUrl;
    document.getElementById('edit-master-name').value = name;
    document.getElementById('edit-master-code').value = code || '';
    document.getElementById('edit-master-desc').value = desc || '';
    document.getElementById('edit-master-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-master-modal').style.display = 'none';
}

document.getElementById('edit-master-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Handle edit form submit via AJAX
document.getElementById('edit-master-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var fd = new FormData(form);
    // FormData doesn't send PUT method override properly with fetch, use POST + _method
    fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r){ return r.json(); })
    .then(function(resp) {
        if (resp.ok) {
            flash({ msg: resp.msg, type: 'success' });
            closeEditModal();
            setTimeout(function(){ location.reload(); }, 800);
        } else {
            flash({ msg: resp.msg || 'Update failed.', type: 'danger' });
        }
    })
    .catch(function() { flash({ msg: 'Server error.', type: 'danger' }); });
});

// ── Filter by class in View by Class tab ─────────────────────────────────────
function filterByClass(classId) {
    document.querySelectorAll('.class-row').forEach(function(row) {
        row.style.display = (classId === 'all' || row.dataset.class == classId) ? '' : 'none';
    });
}

// ── Assign form submit ────────────────────────────────────────────────────────
document.getElementById('assign-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var checked = document.querySelectorAll('#class-checkboxes input:checked');
    if (!document.getElementById('assign-subject-select').value) {
        flash({ msg: 'Please select a subject.', type: 'warning' }); return;
    }
    if (checked.length === 0) {
        flash({ msg: 'Please select at least one class.', type: 'warning' }); return;
    }

    var btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Assigning...';

    var fd = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r){ return r.json(); })
    .then(function(resp) {
        flash({ msg: resp.msg, type: resp.ok ? 'success' : 'warning' });
        if (resp.ok) {
            selectAllClasses(false);
            setTimeout(function(){ location.reload(); }, 1000);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-diagram-3 mr-1"></i>Assign Subject to Selected Classes';
    })
    .catch(function() {
        flash({ msg: 'Server error.', type: 'danger' });
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-diagram-3 mr-1"></i>Assign Subject to Selected Classes';
    });
});
</script>
@endsection
