<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature   = 'mail:test {to} {--message_id= : Also resend a saved message by ID}';
    protected $description = 'Send a test email to verify SMTP settings';

    public function handle()
    {
        $to = $this->argument('to');

        // If a message ID is given, resend it through the real Mailable
        if ($msgId = $this->option('message_id')) {
            $msg = \App\Models\Message::with('sender', 'receiver')->find($msgId);
            if (!$msg) {
                $this->error("Message ID {$msgId} not found.");
                return;
            }
            $this->info("Resending message #{$msgId} to {$to} via " . config('mail.host') . ' ...');
            try {
                Mail::to($to, $msg->receiver->name ?? '')->send(new \App\Mail\MessageNotification($msg));
                $this->info('✅  MessageNotification sent successfully!');
            } catch (\Exception $e) {
                $this->error('❌  Failed: ' . $e->getMessage());
            }
            return;
        }

        // Plain SMTP connectivity test
        $this->info("Sending test email to {$to} via " . config('mail.host') . ' ...');
        try {
            Mail::raw('This is a test email from St. Mark SMS. SMTP is working correctly.', function ($m) use ($to) {
                $m->to($to)->subject('St. Mark SMS — SMTP Test');
            });
            $this->info('✅  Email sent successfully! Check your inbox.');
        } catch (\Exception $e) {
            $this->error('❌  Failed: ' . $e->getMessage());
        }
    }
}
