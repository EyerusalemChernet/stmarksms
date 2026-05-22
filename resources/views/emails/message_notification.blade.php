<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Message</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #4f46e5; padding: 24px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; }
        .header p  { color: #c7d2fe; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 28px 32px; }
        .meta { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #475569; }
        .meta strong { color: #1e293b; }
        .message-body { font-size: 14px; line-height: 1.8; color: #1e293b; white-space: pre-wrap; }
        .btn { display: inline-block; margin-top: 24px; padding: 10px 22px; background: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px; }
        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 32px; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>You have received a new message</p>
    </div>

    <div class="body">
        <p style="margin-top:0;color:#475569;font-size:14px;">
            Hello <strong>{{ $msg->receiver->name ?? 'there' }}</strong>,
        </p>
        <p style="color:#475569;font-size:14px;">
            <strong>{{ $msg->sender->name ?? 'Someone' }}</strong> sent you a message on the school portal.
        </p>

        <div class="meta">
            <div><strong>From:</strong> {{ $msg->sender->name ?? '-' }}</div>
            @if($msg->subject)
            <div style="margin-top:6px;"><strong>Subject:</strong> {{ $msg->subject }}</div>
            @endif
            <div style="margin-top:6px;"><strong>Date:</strong> {{ $msg->created_at->format('d M Y, H:i') }}</div>
        </div>

        <div class="message-body">{{ $msg->body }}</div>

        <a href="{{ url('/inbox') }}" class="btn">View in Portal</a>
    </div>

    <div class="footer">
        This is an automated notification from {{ config('app.name') }}. Please do not reply to this email.
    </div>
</div>
</body>
</html>
