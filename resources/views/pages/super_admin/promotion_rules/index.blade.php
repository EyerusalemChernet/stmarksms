@extends('layouts.master')
@section('page_title', 'Promotion Rules')
@section('content')

<style>
.rule-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 18px; margin-bottom:12px; transition:box-shadow .15s; }
.rule-card:hover { box-shadow:0 2px 10px rgba(0,0,0,.07); }
.rule-card.inactive { opacity:.6; background:#f8fafc; }
.rule-type-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.scope-badge { background:#f1f5f9; color:#475569; border-radius:6px; padding:2px 8px; font-size:11px; }
.status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-4" style="flex-wrap:wrap;gap:12px;">
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Promotion Rules</h5>
        <p style="font-size:13px;color:#64748b;margin:0;">
            Configure the rules the Promotion Engine uses to evaluate each student.
            <strong>{{ $rules->where('is_active', true)->count() }}</strong> of {{ $rules->count() }} rules are active.
        </p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCreateModal()">
        <i class="bi bi-plus-circle mr-1"></i>Add Rule
    </button>
</div>

@if(session('flash_success'))
<div class="alert alert-success border-0 mb-3">{{ session('flash_success') }}</div>
@endif
@if(session('flash_danger'))
<div class="alert alert-danger border-0 mb-3">{{ session('flash_danger') }}</div>
@endif

{{-- Rules grouped by type --}}
@php
    $grouped = $rules->groupBy('rule_type');
    $typeColors = [
        'min_overall_average'    => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
        'core_subject_min_score' => ['bg'=>'#ede9fe','color'=>'#6d28d9'],
        'max_failed_subjects'    => ['bg'=>'#fee2e2','color'=>'#991b1b'],
        'min_attendance_rate'    => ['bg'=>'#d1fae5','color'=>'#065f46'],
        'fee_clearance_required' => ['bg'=>'#fef3c7','color'=>'#92400e'],
        'discipline_restriction' => ['bg'=>'#fce7f3','color'=>'#9d174d'],
        'conditional_promotion'  => ['bg'=>'#f0fdf4','color'=>'#166534'],
    ];
@endphp

@forelse($rules as $rule)
<div class="rule-card {{ !$rule->is_active ? 'inactive' : '' }}">
    <div class="d-flex align-items-start justify-content-between" style="gap:12px;">

        {{-- Icon + name --}}
        <div class="d-flex align-items-start" style="gap:12px;flex:1;min-width:0;">
            <div style="width:40px;height:40px;border-radius:10px;background:{{ $typeColors[$rule->rule_type]['bg'] ?? '#f1f5f9' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $rule->ruleIcon() }}" style="font-size:18px;color:{{ $typeColors[$rule->rule_type]['color'] ?? '#475569' }};"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="d-flex align-items-center flex-wrap" style="gap:8px;margin-bottom:4px;">
                    <strong style="font-size:14px;color:#1e293b;">{{ $rule->name }}</strong>
                    <span class="rule-type-badge" style="background:{{ $typeColors[$rule->rule_type]['bg'] ?? '#f1f5f9' }};color:{{ $typeColors[$rule->rule_type]['color'] ?? '#475569' }};">
                        {{ $rule->ruleTypeLabel() }}
                    </span>
                    <span class="scope-badge">{{ $rule->scopeLabel() }}</span>
                </div>

                {{-- Threshold display --}}
                @if($rule->hasThreshold() && $rule->threshold_value !== null)
                <div style="font-size:13px;color:#475569;margin-bottom:4px;">
                    <i class="bi bi-funnel mr-1"></i>
                    Condition: <strong>{{ $rule->operatorLabel() }} {{ $rule->threshold_value }}
                    @if(in_array($rule->rule_type, ['min_overall_average','core_subject_min_score','min_attendance_rate','conditional_promotion']))%@endif
                    @if($rule->rule_type === 'max_failed_subjects') subjects@endif
                    </strong>
                </div>
                @endif

                @if($rule->description)
                <div style="font-size:12px;color:#94a3b8;">{{ $rule->description }}</div>
                @endif
            </div>
        </div>

        {{-- Status + actions --}}
        <div class="d-flex align-items-center" style="gap:8px;flex-shrink:0;">
            {{-- Active toggle --}}
            <form method="POST" action="{{ route('promotion_rules.toggle', $rule->id) }}" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-xs {{ $rule->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                        title="{{ $rule->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                    <span class="status-dot" style="background:{{ $rule->is_active ? '#10b981' : '#94a3b8' }};"></span>
                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                </button>
            </form>

            {{-- Edit --}}
            <button type="button" class="btn btn-xs btn-outline-secondary"
                    onclick="openEditModal({{ $rule->id }}, @json($rule))">
                <i class="bi bi-pencil"></i>
            </button>

            {{-- Delete --}}
            <form method="POST" action="{{ route('promotion_rules.destroy', $rule->id) }}" class="d-inline"
                  onsubmit="return confirm('Delete rule \'{{ $rule->name }}\'?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-xs btn-outline-danger">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@empty
<div class="text-center text-muted py-5">
    <i class="bi bi-gear" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px;"></i>
    <p>No promotion rules yet. Click <strong>Add Rule</strong> to create your first rule.</p>
</div>
@endforelse

{{-- ── Create / Edit Modal ──────────────────────────────────────────────── --}}
<div id="rule-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 16px;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:100%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 id="modal-title" style="font-weight:700;margin:0;">Add Promotion Rule</h6>
            <button onclick="closeModal()" style="background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">&times;</button>
        </div>

        <form id="rule-form" method="POST">
            @csrf
            <span id="method-field"></span>

            <div class="form-group mb-3">
                <label style="font-size:13px;font-weight:600;">Rule Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="f-name" required class="form-control" placeholder="e.g. Minimum Overall Average">
            </div>

            <div class="form-group mb-3" id="type-group">
                <label style="font-size:13px;font-weight:600;">Rule Type <span class="text-danger">*</span></label>
                <select name="rule_type" id="f-rule-type" required class="form-control" onchange="onRuleTypeChange()">
                    <option value="">— Select type —</option>
                    @foreach($ruleTypes as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row" id="threshold-row">
                <div class="col-6">
                    <div class="form-group mb-3">
                        <label style="font-size:13px;font-weight:600;">Operator</label>
                        <select name="condition_operator" id="f-operator" class="form-control">
                            <option value="">— None —</option>
                            @foreach($operators as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group mb-3">
                        <label style="font-size:13px;font-weight:600;" id="threshold-label">Threshold Value</label>
                        <input type="number" name="threshold_value" id="f-threshold" class="form-control"
                               min="0" max="100" step="0.5" placeholder="e.g. 50">
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label style="font-size:13px;font-weight:600;">Apply To (Scope)</label>
                <select name="scope_type" id="f-scope-type" class="form-control" onchange="onScopeChange()">
                    <option value="school">Entire School</option>
                    <option value="class">Specific Class</option>
                    <option value="department">Specific Department</option>
                    <option value="year">Specific Academic Year</option>
                </select>
            </div>

            <div id="scope-class-row" class="form-group mb-3" style="display:none;">
                <label style="font-size:13px;font-weight:600;">Class</label>
                <select name="scope_class_id" class="form-control">
                    <option value="">— Select class —</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="scope-dept-row" class="form-group mb-3" style="display:none;">
                <label style="font-size:13px;font-weight:600;">Department</label>
                <select name="scope_department_id" class="form-control">
                    <option value="">— Select department —</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="scope-year-row" class="form-group mb-3" style="display:none;">
                <label style="font-size:13px;font-weight:600;">Academic Year</label>
                <input type="text" name="scope_year" class="form-control" placeholder="e.g. {{ $currentYear }}" value="{{ $currentYear }}">
            </div>

            <div class="form-group mb-4">
                <label style="font-size:13px;font-weight:600;">Description (optional)</label>
                <textarea name="description" id="f-description" rows="2" class="form-control"
                          placeholder="Explain what this rule does..."></textarea>
            </div>

            <div class="d-flex" style="gap:8px;">
                <button type="submit" class="btn btn-primary flex-fill">Save Rule</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
var createUrl = '{{ route('promotion_rules.store') }}';

function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Add Promotion Rule';
    document.getElementById('rule-form').action = createUrl;
    document.getElementById('method-field').innerHTML = '';
    document.getElementById('type-group').style.display = '';
    document.getElementById('f-rule-type').disabled = false;
    // Reset fields
    ['f-name','f-threshold','f-description'].forEach(function(id){ document.getElementById(id).value = ''; });
    document.getElementById('f-rule-type').value = '';
    document.getElementById('f-operator').value = '';
    document.getElementById('f-scope-type').value = 'school';
    onScopeChange();
    onRuleTypeChange();
    document.getElementById('rule-modal').style.display = 'flex';
}

function openEditModal(id, rule) {
    document.getElementById('modal-title').textContent = 'Edit Rule';
    var baseUrl = '{{ url('super_admin/promotion-rules') }}/' + id;
    document.getElementById('rule-form').action = baseUrl;
    document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    // Hide type selector (can't change type after creation)
    document.getElementById('type-group').style.display = 'none';

    document.getElementById('f-name').value        = rule.name || '';
    document.getElementById('f-operator').value    = rule.condition_operator || '';
    document.getElementById('f-threshold').value   = rule.threshold_value || '';
    document.getElementById('f-scope-type').value  = rule.scope_type || 'school';
    document.getElementById('f-description').value = rule.description || '';

    // Scope selectors
    onScopeChange();
    if (rule.scope_type === 'class' && rule.scope_class_id) {
        document.querySelector('[name=scope_class_id]').value = rule.scope_class_id;
    }
    if (rule.scope_type === 'department' && rule.scope_department_id) {
        document.querySelector('[name=scope_department_id]').value = rule.scope_department_id;
    }
    if (rule.scope_type === 'year' && rule.scope_year) {
        document.querySelector('[name=scope_year]').value = rule.scope_year;
    }

    onRuleTypeChange(rule.rule_type);
    document.getElementById('rule-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('rule-modal').style.display = 'none';
}

document.getElementById('rule-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function onScopeChange() {
    var scope = document.getElementById('f-scope-type').value;
    document.getElementById('scope-class-row').style.display = scope === 'class'      ? '' : 'none';
    document.getElementById('scope-dept-row').style.display  = scope === 'department' ? '' : 'none';
    document.getElementById('scope-year-row').style.display  = scope === 'year'       ? '' : 'none';
}

function onRuleTypeChange(typeOverride) {
    var type = typeOverride || document.getElementById('f-rule-type').value;
    var noThreshold = ['fee_clearance_required', 'discipline_restriction'];
    var show = type && !noThreshold.includes(type);
    document.getElementById('threshold-row').style.display = show ? '' : 'none';

    // Update threshold label
    var labels = {
        'min_overall_average':    'Minimum Average (%)',
        'core_subject_min_score': 'Minimum Score (%)',
        'max_failed_subjects':    'Max Failed Subjects (count)',
        'min_attendance_rate':    'Minimum Attendance (%)',
        'conditional_promotion':  'Minimum Average for Conditional (%)',
    };
    var lbl = labels[type] || 'Threshold Value';
    document.getElementById('threshold-label').textContent = lbl;
}
</script>
@endsection
