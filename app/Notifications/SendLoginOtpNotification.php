<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendLoginOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose = 'login',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = match ($this->purpose) {
            'password_reset' => 'reset your password',
            'email_verification' => 'verify your email',
            default => 'sign in to agriAid',
        };

        $name = $notifiable->name ?? 'there';

        return (new MailMessage)
            ->subject('Your agriAid verification code')
            ->view('mail.otp', [
                'name' => $name,
                'code' => $this->code,
                'action' => $action,
                'purpose' => $this->purpose,
            ]);
    }
}
