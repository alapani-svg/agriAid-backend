<?php

namespace App\Notifications\Infrastructure\Channels;

use App\Models\User;
use App\Notifications\Domain\Entities\Notification;
use Illuminate\Support\Facades\Log;

/**
 * SMS delivery channel. No SMS gateway (e.g. Vonage/Twilio) is configured
 * in this project, so this channel only records the delivery intent to the
 * log. Wire a real client here (and read credentials from config/services.php)
 * to activate outbound SMS.
 */
class SmsChannel
{
    public function send(User $user, Notification $notification): bool
    {
        if (! $user->phone) {
            return false;
        }

        Log::info('agriAid notification SMS not sent (no gateway configured)', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'notification_id' => $notification->getId(),
            'title' => $notification->getTitle(),
        ]);

        return false;
    }
}
