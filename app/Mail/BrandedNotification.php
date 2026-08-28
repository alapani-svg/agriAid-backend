<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrandedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $recipientName = null,
    ) {}

    public function build(): self
    {
        return $this->subject($this->title)
            ->view('emails.branded')
            ->with([
                'title' => $this->title,
                'body' => $this->body,
                'recipientName' => $this->recipientName,
            ]);
    }
}
