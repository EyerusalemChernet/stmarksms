@php
  $compact = $compact ?? false;
  if ($compact) {
    $latest = \App\Services\AdminFeeAudit::latestEventForInvoice($inv);
    $events = $latest ? collect([$latest]) : collect();
  } else {
    $events = \App\Services\AdminFeeAudit::eventsForInvoice($inv);
  }
@endphp
@if($events->isNotEmpty())
  @foreach($events as $event)
    <div class="{{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}" style="font-size:11px;">
      <span class="badge badge-info">{{ $event['action'] === 'created' ? 'Created' : 'Updated' }}</span>
      <span class="text-muted">by Super Admin</span><br>
      <span class="text-muted">{{ $event['label'] }}</span><br>
      <strong>{{ $event['user'] }}</strong><br>
      <span class="text-muted">{{ \App\Services\AdminFeeAudit::formatAt($event['at']) }}</span>
    </div>
  @endforeach
@else
  <span class="text-muted">—</span>
@endif
