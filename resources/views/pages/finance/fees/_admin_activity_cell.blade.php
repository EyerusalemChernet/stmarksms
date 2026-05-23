@php
  $updater = $record->adminUpdater ?? null;
  $at = $record->admin_updated_at ?? null;
@endphp
@if($at && $updater)
  <span class="badge badge-info">{{ ($record->admin_action ?? '') === 'created' ? 'Created' : 'Updated' }}</span>
  <span class="text-muted">by Super Admin</span><br>
  <strong>{{ $updater->name }}</strong><br>
  <span class="text-muted">{{ \App\Services\AdminFeeAudit::formatAt($at) }}</span>
@else
  <span class="text-muted">—</span>
@endif
