@extends('layouts.master')
@section('page_title', 'Edit Exam')
@section('content')

<div style="max-width:600px;margin:0 auto;">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Edit Exam</h5>
            <p style="font-size:13px;color:#64748b;margin:0;">{{ $ex->name }} — Session {{ $ex->year }}</p>
        </div>
        <a href="{{ route('exams.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">

        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:16px 20px;display:flex;align-items:center;gap:10px;">
            <div style="background:rgba(255,255,255,.2);border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-pencil-fill" style="color:#fff;font-size:16px;"></i>
            </div>
            <div>
                <div style="color:#fff;font-weight:700;font-size:15px;">{{ $ex->name }}</div>
                <div style="color:rgba(255,255,255,.8);font-size:12px;">Semester {{ $ex->term }} · {{ $ex->year }}</div>
            </div>
            @php $badge = $ex->statusBadge(); @endphp
            <span style="margin-left:auto;background:rgba(255,255,255,.25);color:#fff;font-size:11px;font-weight:600;padding:3px 12px;border-radius:20px;">
                {{ $badge['label'] }}
            </span>
        </div>

        <div style="padding:24px;">
            @if(session('flash_success'))
            <div style="background:#d1fae5;border:1px solid #a7f3d0;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#065f46;">
                <i class="bi bi-check-circle mr-1"></i>{{ session('flash_success') }}
            </div>
            @endif

            <form method="post" action="{{ route('exams.update', $ex->id) }}">
                @csrf @method('PUT')

                {{-- Name --}}
                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">
                        Exam Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" name="name" value="{{ $ex->name }}" required
                           style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
                </div>

                {{-- Semester (read-only display + hidden) --}}
                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Semester</label>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:13px;color:#475569;display:flex;align-items:center;gap:8px;">
                        <i class="bi bi-lock" style="color:#94a3b8;"></i>
                        Semester {{ $ex->term }}
                        <span style="font-size:11px;color:#94a3b8;margin-left:4px;">(cannot be changed)</span>
                    </div>
                    <input type="hidden" name="term" value="{{ $ex->term }}">
                </div>

                {{-- Dates --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Start Date</label>
                        <input type="date" name="start_date"
                               value="{{ $ex->start_date ? $ex->start_date->format('Y-m-d') : '' }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">End Date</label>
                        <input type="date" name="end_date"
                               value="{{ $ex->end_date ? $ex->end_date->format('Y-m-d') : '' }}"
                               style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
                    </div>
                </div>

                {{-- Status --}}
                <div style="margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Status</label>
                    <select name="status" style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;background:#fff;outline:none;">
                        @foreach(['upcoming'=>'Upcoming','ongoing'=>'Ongoing','completed'=>'Completed','cancelled'=>'Cancelled'] as $val => $lbl)
                        <option value="{{ $val }}" {{ $ex->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Description --}}
                <div style="margin-bottom:20px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Notes</label>
                    <textarea name="description" rows="3"
                              placeholder="Optional notes for teachers…"
                              style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;resize:none;">{{ $ex->description }}</textarea>
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="submit" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(245,158,11,.3);">
                        <i class="bi bi-save mr-1"></i>Save Changes
                    </button>
                    <a href="{{ route('exams.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;text-decoration:none;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
