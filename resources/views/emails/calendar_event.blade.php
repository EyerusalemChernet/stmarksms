<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Calendar Event Notification</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#f3f4f6; margin:0; padding:20px; }
  .wrap { max-width:560px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.08); }
  .header { background:linear-gradient(135deg,#4f46e5,#7c3aed); padding:28px 32px; color:#fff; }
  .header h1 { margin:0 0 4px; font-size:20px; font-weight:700; }
  .header p  { margin:0; font-size:13px; opacity:.8; }
  .body { padding:28px 32px; }
  .event-card { background:#f8f7ff; border-left:4px solid #4f46e5; border-radius:0 8px 8px 0; padding:16px 20px; margin:16px 0; }
  .event-card h2 { margin:0 0 10px; font-size:17px; color:#1e1b4b; }
  .meta { font-size:13px; color:#6b7280; margin:4px 0; }
  .meta strong { color:#374151; }
  .eth-badge { display:inline-block; background:#ede9fe; color:#5b21b6; font-size:12px; padding:3px 10px; border-radius:20px; margin-top:8px; }
  .btn { display:inline-block; margin-top:20px; padding:11px 24px; background:#4285f4; color:#fff !important; text-decoration:none; border-radius:8px; font-size:14px; font-weight:600; }
  .btn-gcal { background:#4285f4; }
  .footer { padding:16px 32px; background:#f9fafb; font-size:12px; color:#9ca3af; border-top:1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>📅 {{ config('app.name') }}</h1>
        <p>Academic Calendar Notification</p>
    </div>
    <div class="body">
        <p style="color:#374151;font-size:15px;">Dear <strong>{{ $user->name }}</strong>,</p>
        <p style="color:#6b7280;font-size:14px;">A new event has been added to the school academic calendar:</p>

        <div class="event-card">
            <h2>{{ $event->title }}</h2>
            <p class="meta">
                <strong>📆 Gregorian:</strong>
                {{ $event->start_date->format('l, d F Y') }}
                @if($event->end_date && $event->end_date != $event->start_date)
                    — {{ $event->end_date->format('d F Y') }}
                @endif
            </p>
            @if(!$event->all_day && $event->start_time)
            <p class="meta"><strong>🕐 Time:</strong> {{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}
                @if($event->end_time) — {{ \Carbon\Carbon::parse($event->end_time)->format('g:i A') }} @endif
            </p>
            @endif
            <p class="meta"><strong>📌 Type:</strong> {{ ucfirst($event->type) }}</p>
            @if($event->description)
            <p class="meta" style="margin-top:10px;">{{ $event->description }}</p>
            @endif
            <span class="eth-badge">🇪🇹 {{ $ethLabel }}</span>
        </div>

        <a href="{{ $gcalUrl }}" class="btn btn-gcal" target="_blank">
            <img src="https://www.gstatic.com/images/branding/product/1x/calendar_16dp.png"
                 style="vertical-align:middle;margin-right:6px;" alt="">
            Add to Google Calendar
        </a>

        <p style="margin-top:24px;font-size:13px;color:#9ca3af;">
            You can also subscribe to the full school calendar:
            <a href="{{ route('calendar.ics') }}" style="color:#4f46e5;">Download ICS / Subscribe</a>
        </p>
    </div>
    <div class="footer">
        This is an automated notification from {{ config('app.name') }}. Please do not reply to this email.
    </div>
</div>
</body>
</html>
