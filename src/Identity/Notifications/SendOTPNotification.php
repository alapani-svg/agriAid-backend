<?php

namespace App\Identity\Notifications;

use App\Identity\Domain\Entities\OTP;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;

class SendOTPNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OTP $otp,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // Add SMS channel if user has phone number and prefers SMS
        if ($notifiable->phone && $notifiable->notification_preference === 'sms') {
            $channels[] = 'vonage';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your agriAid Verification Code')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your verification code is: **' . $this->otp->getCode()->getValue() . '**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this code, please ignore this message.')
            ->salutation('The agriAid Team');
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content("Your agriAid verification code is: {$this->otp->getCode()->getValue()}. Valid for 10 minutes. Don't share this code with anyone.");
    }
}
