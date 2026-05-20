<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Message $msg;

    public function __construct(Message $msg)
    {
        $this->msg = $msg;
    }

    public function build(): self
    {
        $subject = $this->msg->subject
            ? '[' . config('app.name') . '] ' . $this->msg->subject
            : '[' . config('app.name') . '] You have a new message';

        return $this->subject($subject)
                    ->view('emails.message_notification');
    }
}
