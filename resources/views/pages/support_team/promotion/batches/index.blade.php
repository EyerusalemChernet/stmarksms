@extends('layouts.master')
@section('page_title', 'Promotion Center')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-3" style="flex-wrap:wrap;gap:12px;">
    <div>
        <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Promotion Center</h5>
        <p style="font-size:13px;color:#64748b;margin:0;">
            One workflow: auto-evaluate by marks → review batches in workspace → finalize.
            Manual promote and per-class batches use the same rules.
        </p>
    </div>
    <div class="d-flex" style="gap:8px;flex-wrap:wrap;">
        <a href="{{ route('students.promotion') }}" class="btn btn-outline-primary btn-sm">Manual Promote</a>
        <a href="{{ route('students.promotion_manage') }}" class="btn btn-outline-secondary btn-sm">Manage / Reset</a>
        @if(Qs::userIsSuperAdmin())
        <a href="{{ route('promotion.batches.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle mr-1"></i>New Batch (one class)
        </a>
        @endif
    </div>
</div>

@if(session('flash_success'))<div class="alert alert-success border-0">{{ session('flash_success') }}</div>@endif
@if(session('flash_danger'))<div class="alert alert-danger border-0">{{ session('flash_danger') }}</div>@endif

@if(Qs::userIsSuperAdmin())
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#ecfdf5,#f0fdf4);border:1px solid #a7f3d0 !important;">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="d-flex align-items-start" style="gap:12px;">
                    <div style="background:#10b981;border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-arrow-up-circle" style="color:#fff;font-size:20px;"></i>
                    </div>
                    <div>
                        <strong style="font-size:15px;color:#065f46;">Step 1 — Auto-evaluate all classes</strong>
                        <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                            Creates a <strong>draft batch per class</strong> using session averages (pass: {{ $promotion_min_avg }}%).
                            Promoted and held-back students appear in each batch workspace.
                            Does not finalize until you review and confirm each batch.
                        </p>
                        @if($draft_count > 0)
                        <span class="badge badge-primary mt-2">{{ $draft_count }} draft batch(es) awaiting review</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mt-3 mt-lg-0">
                <form method="POST" action="{{ route('promotion.batches.run_auto') }}"
                      onsubmit="return confirm('Create draft promotion batches for ALL classes?\n\nYou will review section assignments before anything is finalized.')">
                    @csrf
                    <div class="form-group mb-2">
                        <label class="font-weight-semibold" style="font-size:12px;">Minimum average to promote (%)</label>
                        <input type="number" name="min_average" class="form-control" min="0" max="100"
                               value="{{ $promotion_min_avg }}" required>
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-semibold" style="font-size:12px;">Section distribution</label>
                        <select name="redistribution_mode" class="form-control">
                            <option value="balanced" selected>Balanced (recommended)</option>
                            <option value="keep_same">Keep same sections</option>
                            <option value="random">Random</option>
                            <option value="manual">Manual (assign in workspace)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="bi bi-lightning-charge mr-1"></i>
                        Run Auto-Promotion for {{ $current_session }}
                    </button>
                </form>
            </div>
        </div>
        <hr style="border-color:#bbf7d0;margin:16px 0 12px;">
        <p class="mb-0" style="font-size:12px;color:#047857;">
            <strong>Step 2:</strong> Open each draft below → adjust sections → <strong>Finalize</strong>.
            <strong>Step 3:</strong> Use Manage Promotions to reset if needed.
            Settings: <a href="{{ route('term_setup.index') }}">Term & Semester Setup</a>
            · <a href="{{ route('promotion_rules.index') }}">Promotion Rules</a>
        </p>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <strong>Promotion Batches</strong>
        <small class="text-muted ml-2">Draft = review · Finalized = applied</small>
    </div>
    <div class="card-body p-0">
        @if($batches->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-arrow-up-circle" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p class="mb-2">No promotion batches yet.</p>
            @if(Qs::userIsSuperAdmin())
            <p style="font-size:13px;">Run <strong>Auto-Promotion</strong> above, or create a single-class batch.</p>
            @endif
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="thead-light">
                    <tr>
                        <th>From</th><th>To</th><th>Mode</th><th>Students</th>
                        <th>Status</th><th>Created By</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($batches as $b)
                <tr>
                    <td>
                        <strong>{{ $b->fromClass?->name }}</strong>
                        <small class="text-muted d-block">{{ $b->fromYear?->name }}</small>
                    </td>
                    <td>
                        <strong>{{ $b->toClass?->name }}</strong>
                        <small class="text-muted d-block">{{ $b->toYear?->name }}</small>
                    </td>
                    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$b->redistribution_mode)) }}</span></td>
                    <td>{{ $b->student_count }}</td>
                    <td>
                        <span class="badge {{ $b->statusBadgeClass() }}">{{ ucfirst($b->status) }}</span>
                    </td>
                    <td>{{ $b->createdBy?->name ?? '—' }}</td>
                    <td>{{ $b->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex" style="gap:4px;">
                            @if($b->isDraft())
                            <a href="{{ route('promotion.batches.workspace', $b->id) }}" class="btn btn-xs btn-primary">
                                <i class="bi bi-grid-3x3-gap"></i> Workspace
                            </a>
                            <form method="POST" action="{{ route('promotion.batches.destroy', $b->id) }}"
                                  onsubmit="return confirm('Delete this draft batch?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @elseif($b->isFinalized())
                            <a href="{{ route('promotion.batches.summary', $b->id) }}" class="btn btn-xs btn-success">
                                <i class="bi bi-check-circle"></i> Summary
                            </a>
                            <form method="POST" action="{{ route('promotion.batches.rollback', $b->id) }}"
                                  onsubmit="return confirm('Roll back this finalized promotion?')">
                                @csrf
                                <button class="btn btn-xs btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Rollback</button>
                            </form>
                            @else
                            <span class="text-muted" style="font-size:12px;">Rolled back</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $batches->links() }}</div>
        @endif
    </div>
</div>

@endsection
