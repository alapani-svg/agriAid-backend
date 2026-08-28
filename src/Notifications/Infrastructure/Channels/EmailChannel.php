<?php

namespace App\Notifications\Infrastructure\Channels;

use App\Mail\BrandedNotification;
use App\Models\User;
use App\Notifications\Domain\Entities\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers a notification as a branded HTML email using the agriAid
 * email template. Failures are logged and swallowed so a broken mail
 * transport never blocks in-app delivery.
 */
class EmailChannel
{
    public function send(User $user, Notification $notification): bool
    {
        try {
            Mail::to($user->email)->send(
                new BrandedNotification(
                    title: $notification->getTitle(),
                    body: $notification->getMessage(),
                    recipientName: $user->name,
                )
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('agriAid notification email failed', [
                'user_id' => $user->id,
                'notification_id' => $notification->getId(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
