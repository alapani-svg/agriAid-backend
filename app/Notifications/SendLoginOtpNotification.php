<?php

namespace App\Notifications;

use App\Support\AgriAidBrand;
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

        // Ensure logo file exists under public/images before the view embeds it
        try {
            AgriAidBrand::logoPath();
        } catch (\Throwable $e) {
            // View will fall back to text wordmark if embed fails
        }

        return (new MailMessage)
            ->subject('Your agriAid verification code')
            ->view('mail.otp', [
                'name' => $name,
                'code' => $this->code,
                'action' => $action,
                'purpose' => $this->purpose,
                'logoPath' => public_path('images/agriAid-logo.png'),
            ]);
    }
}
