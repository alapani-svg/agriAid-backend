<?php

namespace App\Notifications\Infrastructure\Channels;

use App\Models\User;
use App\Notifications\Domain\Entities\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers a notification as a plain email using whatever mailer is
 * configured in config/mail.php. Failures are logged and swallowed so a
 * broken mail transport never blocks in-app delivery.
 */
class EmailChannel
{
    public function send(User $user, Notification $notification): bool
    {
        try {
            Mail::raw($notification->getMessage(), function ($message) use ($user, $notification) {
                $message->to($user->email, $user->name)
                    ->subject($notification->getTitle());
            });

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
