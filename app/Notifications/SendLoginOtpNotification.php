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

        return (new MailMessage)
            ->subject('Your agriAid verification code')
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line("Your verification code to {$action} is:")
            ->line($this->code)
            ->line('Enter this 6-digit code in the agriAid app. It expires in 10 minutes.')
            ->line('If you did not request this, you can ignore this email.')
            ->salutation('— The agriAid team');
    }
}
