@extends('layouts.master')
@section('page_title', 'Promotion Workspace')
@section('content')

<style>
.ws-layout { display:grid; grid-template-columns:260px 1fr 220px; gap:12px; height:calc(100vh - 140px); min-height:500px; }
.ws-panel { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; display:flex; flex-direction:column; }
.ws-panel-header { padding:12px 14px; border-bottom:1px solid #f1f5f9; font-weight:700; font-size:13px; color:#1e293b; flex-shrink:0; }
.ws-panel-body { flex:1; overflow-y:auto; padding:10px; }
.student-chip { display:flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:7px; padding:5px 8px; margin-bottom:4px; font-size:12px; cursor:grab; transition:background .1s; }
.student-chip:hover { background:#ede9fe; border-color:#c4b5fd; }
.student-chip.locked { cursor:not-allowed; background:#fef3c7; border-color:#fde68a; }
.student-chip.assigned { opacity:.5; }
.section-card { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; margin-bottom:10px; overflow:hidden; }
.section-card.green { border-color:#a7f3d0; }
.section-card.yellow { border-color:#fde68a; }
.section-card.red { border-color:#fca5a5; }
.section-header { padding:10px 12px; display:flex; align-items:center; justify-content:space-between; }
.section-drop-zone { min-height:60px; padding:6px; }
.cap-bar { height:4px; border-radius:4px; margin:0 12px 8px; background:#e2e8f0; overflow:hidden; }
.cap-bar-fill { height:100%; border-radius:4px; transition:width .3s; }
.ctrl-btn { width:100%; margin-bottom:6px; font-size:12px; text-align:left; padding:7px 10px; border-radius:7px; border:1px solid #e2e8f0; background:#fff; cursor:pointer; display:flex; align-items:center; gap:6px; transition:background .1s; }
.ctrl-btn:hover { background:#f1f5f9; }
.ctrl-btn.danger { border-color:#fca5a5; color:#ef4444; }
.ctrl-btn.primary { background:#4f46e5; color:#fff; border-color:#4f46e5; }
.ctrl-btn.primary:hover { background:#4338ca; }
.ctrl-btn:disabled { opacity:.5; cursor:not-allowed; }
</style>

{{-- Alpine.js + SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="d-flex align-items-center mb-3" style="gap:10px;">
    <a href="{{ route('promotion.batches.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <strong style="font-size:15px;">Promotion Workspace</strong>
        <small class="text-muted ml-2">{{ $batch->fromClass?->name }} ({{ $batch->fromYear?->name }}) → {{ $batch->toClass?->name }} ({{ $batch->toYear?->name }})</small>
    </div>
    <span class="badge badge-primary ml-auto">{{ $batch->redistribution_mode }}</span>
    <span id="save-indicator" style="font-size:11px;color:#10b981;display:none;"><i class="bi bi-check-circle mr-1"></i>Saved</span>
</div>

@if(session('flash_success'))<div class="alert alert-success border-0 mb-2">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0 mb-2">{{ session('flash_danger') }}</div>@endif

<div class="ws-layout"
     x-data="promotionWorkspace()"
     x-init="init()">

    {{-- ── LEFT: Student Pool ──────────────────────────────────────────── --}}
    <div class="ws-panel">
        <div class="ws-panel-header">
            <i class="bi bi-people mr-1 text-primary"></i>Students
            <span class="badge badge-secondary ml-1" x-text="Object.keys(students).length"></span>
        </div>
        <div style="padding:8px;border-bottom:1px solid #f1f5f9;flex-shrink:0;">
            <input type="text" x-model="searchQuery" placeholder="Search..." class="form-control form-control-sm mb-1">
            <div class="d-flex" style="gap:4px;">
                <button @click="filterGender='all'" :class="filterGender==='all'?'btn-primary':'btn-outline-secondary'" class="btn btn-xs flex-fill">All</button>
                <button @click="filterGender='male'" :class="filterGender==='male'?'btn-primary':'btn-outline-secondary'" class="btn btn-xs flex-fill">♂</button>
                <button @click="filterGender='female'" :class="filterGender==='female'?'btn-primary':'btn-outline-secondary'" class="btn btn-xs flex-fill">♀</button>
            </div>
        </div>
        <div class="ws-panel-body" id="student-pool">
            <template x-for="s in filteredStudents" :key="s.id">
                <div class="student-chip"
                     :class="{ locked: s.isLocked, assigned: s.sectionId !== null }"
                     :data-student-id="s.id"
                     :title="s.name + ' — ' + s.prevSection">
                    <span x-text="s.gender==='male'?'♂':'♀'" :style="'color:'+(s.gender==='male'?'#3b82f6':'#ec4899')"></span>
                    <span x-text="s.name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    <span x-show="s.score" x-text="s.score+'%'" style="font-size:10px;color:#94a3b8;"></span>
                    <i x-show="s.isLocked" class="bi bi-lock-fill" style="color:#f59e0b;font-size:10px;"></i>
                </div>
            </template>
        </div>
    </div>

    {{-- ── CENTER: Section Board ───────────────────────────────────────── --}}
    <div class="ws-panel">
        <div class="ws-panel-header">
            <i class="bi bi-grid-3x3-gap mr-1 text-primary"></i>Section Distribution
        </div>
        <div class="ws-panel-body">
            <template x-for="(sec, secId) in sections" :key="secId">
                <div class="section-card" :class="capacityColor(secId)">
                    <div class="section-header">
                        <div>
                            <strong x-text="sec.name" style="font-size:14px;"></strong>
                            <small class="text-muted ml-2" x-text="sec.students.length + (sec.capacity ? '/'+sec.capacity : '') + ' students'"></small>
                        </div>
                        <div style="font-size:11px;color:#64748b;">
                            <span x-text="'♂'+boyCount(secId)"></span>
                            <span class="ml-1" x-text="'♀'+girlCount(secId)"></span>
                            <span class="ml-1" x-text="avgScore(secId)+'% avg'"></span>
                        </div>
                    </div>
                    <template x-if="sec.capacity">
                        <div class="cap-bar">
                            <div class="cap-bar-fill"
                                 :style="'width:'+Math.min(100,Math.round(sec.students.length/sec.capacity*100))+'%;background:'+(capacityColor(secId)==='red'?'#ef4444':capacityColor(secId)==='yellow'?'#f59e0b':'#10b981')"></div>
                        </div>
                    </template>
                    <div class="section-drop-zone"
                         :id="'section-'+secId"
                         :data-section-id="secId">
                        <template x-for="studentId in sec.students" :key="studentId">
                            <div class="student-chip"
                                 :class="{ locked: students[studentId]?.isLocked }"
                                 :data-student-id="studentId">
                                <span x-text="students[studentId]?.gender==='male'?'♂':'♀'"
                                      :style="'color:'+(students[studentId]?.gender==='male'?'#3b82f6':'#ec4899')"></span>
                                <span x-text="students[studentId]?.name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;"></span>
                                <button @click="toggleLock(students[studentId]?.draftId)"
                                        style="background:none;border:none;padding:0;cursor:pointer;"
                                        :title="students[studentId]?.isLocked?'Unlock':'Lock'">
                                    <i :class="students[studentId]?.isLocked?'bi-lock-fill text-warning':'bi-unlock text-muted'" class="bi" style="font-size:10px;"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── RIGHT: Controls ────────────────────────────────────────────── --}}
    <div class="ws-panel">
        <div class="ws-panel-header"><i class="bi bi-sliders mr-1 text-primary"></i>Controls</div>
        <div class="ws-panel-body">

            {{-- Stats --}}
            <div style="background:#f8fafc;border-radius:8px;padding:10px;margin-bottom:12px;font-size:12px;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Total</span>
                    <strong x-text="Object.keys(students).length"></strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Assigned</span>
                    <strong x-text="assignedCount" style="color:#10b981;"></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Unassigned</span>
                    <strong x-text="unassignedCount" :style="unassignedCount>0?'color:#ef4444':''"></strong>
                </div>
            </div>

            <button class="ctrl-btn" @click="shuffleAgain()">
                <i class="bi bi-shuffle"></i> Shuffle Again
            </button>
            <button class="ctrl-btn" @click="autoBalance()">
                <i class="bi bi-bar-chart-line"></i> Auto Balance
            </button>
            <button class="ctrl-btn" @click="undo()" :disabled="undoStack.length===0">
                <i class="bi bi-arrow-counterclockwise"></i> Undo
                <span x-show="undoStack.length>0" x-text="'('+undoStack.length+')'" style="font-size:10px;color:#94a3b8;"></span>
            </button>
            <button class="ctrl-btn danger" @click="resetDrafts()">
                <i class="bi bi-arrow-repeat"></i> Reset Distribution
            </button>

            <hr style="border-color:#f1f5f9;margin:10px 0;">

            <button class="ctrl-btn" @click="showAddSection=true">
                <i class="bi bi-plus-circle"></i> Add Section
            </button>

            <hr style="border-color:#f1f5f9;margin:10px 0;">

            <form method="POST" action="{{ route('promotion.batches.finalize', $batch->id) }}"
                  onsubmit="return confirm('Finalize this promotion? This will create enrollment records for all students. This action cannot be undone without a rollback.')">
                @csrf
                <button type="submit" class="ctrl-btn primary" :disabled="!canFinalize">
                    <i class="bi bi-check-circle-fill"></i> Finalize Promotion
                </button>
            </form>
            <template x-if="!canFinalize">
                <small style="color:#ef4444;font-size:11px;display:block;margin-top:4px;">
                    <i class="bi bi-exclamation-triangle mr-1"></i>
                    <span x-text="unassignedCount+' student(s) unassigned'"></span>
                </small>
            </template>
        </div>
    </div>
</div>

{{-- Add Section Modal --}}
<div x-data="{ showAddSection: false }" x-show="showAddSection"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;"
     :style="showAddSection?'display:flex':''">
    <div style="background:#fff;border-radius:12px;padding:24px;width:360px;">
        <h6 style="font-weight:700;margin-bottom:16px;">Add New Section</h6>
        <div class="form-group mb-3">
            <label style="font-size:13px;font-weight:600;">Section Name</label>
            <input type="text" id="new-section-name" class="form-control" placeholder="e.g. 8C">
        </div>
        <div class="form-group mb-4">
            <label style="font-size:13px;font-weight:600;">Capacity (optional)</label>
            <input type="number" id="new-section-capacity" class="form-control" placeholder="e.g. 40" min="1">
        </div>
        <div class="d-flex" style="gap:8px;">
            <button class="btn btn-primary flex-fill" onclick="addSectionFromModal()">Add Section</button>
            <button class="btn btn-secondary" @click="showAddSection=false">Cancel</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var csrfToken = '{{ csrf_token() }}';
var batchId   = {{ $batch->id }};
var toClassId = {{ $batch->to_class_id }};

var initialStudents  = @json($studentsJson);
var initialSections  = @json($sectionsJson);

function promotionWorkspace() {
    return {
        students:     JSON.parse(JSON.stringify(initialStudents)),
        sections:     JSON.parse(JSON.stringify(initialSections)),
        undoStack:    [],
        searchQuery:  '',
        filterGender: 'all',
        saveTimer:    null,
        showAddSection: false,

        init() {
            this.$nextTick(() => this.initSortable());
        },

        initSortable() {
            var self = this;
            Object.keys(this.sections).forEach(function(secId) {
                var el = document.getElementById('section-' + secId);
                if (!el) return;
                new Sortable(el, {
                    group: 'students',
                    animation: 150,
                    filter: '.locked',
                    onEnd: function(evt) {
                        var studentId = parseInt(evt.item.dataset.studentId);
                        var fromSec   = evt.from.dataset.sectionId;
                        var toSec     = evt.to.dataset.sectionId;
                        if (fromSec !== toSec) {
                            self.moveStudent(studentId, parseInt(fromSec), parseInt(toSec));
                        }
                    }
                });
            });
        },

        moveStudent(studentId, fromSecId, toSecId) {
            var s = this.students[studentId];
            if (!s || s.isLocked) return;

            // Capacity check
            var toSec = this.sections[toSecId];
            if (toSec && toSec.capacity && toSec.students.length >= toSec.capacity) {
                if (!confirm('Section ' + toSec.name + ' is at capacity. Move anyway?')) return;
            }

            // Push to undo stack
            this.undoStack.push({ draftId: s.draftId, studentId, prevSecId: fromSecId, newSecId: toSecId });
            if (this.undoStack.length > 20) this.undoStack.shift();

            // Update state
            if (fromSecId && this.sections[fromSecId]) {
                this.sections[fromSecId].students = this.sections[fromSecId].students.filter(id => id !== studentId);
            }
            if (!this.sections[toSecId].students.includes(studentId)) {
                this.sections[toSecId].students.push(studentId);
            }
            s.sectionId = toSecId;

            this.scheduleSave(s.draftId, toSecId);
        },

        scheduleSave(draftId, sectionId) {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.saveDraft(draftId, sectionId), 500);
        },

        async saveDraft(draftId, sectionId) {
            var resp = await fetch('/promotion/drafts/' + draftId, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ proposed_section_id: sectionId })
            });
            if (resp.ok) {
                var ind = document.getElementById('save-indicator');
                if (ind) { ind.style.display='inline'; setTimeout(()=>ind.style.display='none', 2000); }
            }
        },

        async toggleLock(draftId) {
            var student = Object.values(this.students).find(s => s.draftId === draftId);
            if (!student) return;
            student.isLocked = !student.isLocked;
            await fetch('/promotion/drafts/' + draftId + '/lock', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({})
            });
        },

        undo() {
            if (!this.undoStack.length) return;
            var { draftId, studentId, prevSecId, newSecId } = this.undoStack.pop();
            var s = this.students[studentId];
            if (!s) return;

            if (newSecId && this.sections[newSecId]) {
                this.sections[newSecId].students = this.sections[newSecId].students.filter(id => id !== studentId);
            }
            if (prevSecId && this.sections[prevSecId] && !this.sections[prevSecId].students.includes(studentId)) {
                this.sections[prevSecId].students.push(studentId);
            }
            s.sectionId = prevSecId || null;
            this.saveDraft(draftId, prevSecId || null);
        },

        async shuffleAgain() {
            if (!confirm('Reshuffle all unlocked students?')) return;
            var resp = await fetch('/promotion/batches/' + batchId + '/shuffle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            if (resp.ok) location.reload();
        },

        async autoBalance() {
            if (!confirm('Auto-balance unlocked students across sections?')) return;
            var resp = await fetch('/promotion/batches/' + batchId + '/shuffle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            if (resp.ok) location.reload();
        },

        async resetDrafts() {
            if (!confirm('Reset all assignments to the original distribution? All manual changes will be lost.')) return;
            var resp = await fetch('/promotion/batches/' + batchId + '/shuffle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            if (resp.ok) location.reload();
        },

        capacityColor(secId) {
            var s = this.sections[secId];
            if (!s || !s.capacity) return 'green';
            var pct = s.students.length / s.capacity;
            if (pct >= 1.0) return 'red';
            if (pct >= 0.8) return 'yellow';
            return 'green';
        },

        boyCount(secId) {
            var s = this.sections[secId];
            if (!s) return 0;
            return s.students.filter(id => this.students[id]?.gender === 'male').length;
        },

        girlCount(secId) {
            var s = this.sections[secId];
            if (!s) return 0;
            return s.students.filter(id => this.students[id]?.gender === 'female').length;
        },

        avgScore(secId) {
            var s = this.sections[secId];
            if (!s || !s.students.length) return 0;
            var scores = s.students.map(id => this.students[id]?.score || 0);
            return Math.round(scores.reduce((a,b)=>a+b,0) / scores.length);
        },

        get filteredStudents() {
            return Object.values(this.students).filter(s => {
                var q = this.searchQuery.toLowerCase();
                var matchName   = !q || s.name.toLowerCase().includes(q);
                var matchGender = this.filterGender === 'all' || s.gender === this.filterGender;
                return matchName && matchGender;
            });
        },

        get assignedCount() {
            return Object.values(this.students).filter(s => s.sectionId !== null).length;
        },

        get unassignedCount() {
            return Object.values(this.students).filter(s => s.sectionId === null).length;
        },

        get canFinalize() {
            return this.unassignedCount === 0;
        },
    };
}

async function addSectionFromModal() {
    var name     = document.getElementById('new-section-name').value.trim();
    var capacity = document.getElementById('new-section-capacity').value;
    if (!name) { alert('Section name is required.'); return; }

    var resp = await fetch('/promotion/sections', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ my_class_id: toClassId, section_name: name, capacity: capacity || null })
    });
    var data = await resp.json();
    if (data.ok) {
        location.reload();
    } else {
        alert(data.msg || 'Failed to add section.');
    }
}
</script>
@endsection
