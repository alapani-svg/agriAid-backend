<?php

namespace App\Notifications\Infrastructure\Channels;

use App\Models\User;
use App\Notifications\Domain\Entities\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Push delivery channel (mobile/web push via APNs/FCM). No push gateway or
 * device-token registry exists in this project, so this channel only
 * records the delivery intent to the log. Wire a real APNs/FCM client and a
 * device-token table here to activate push delivery.
 */
class PushChannel
{
    public function send(User $user, Notification $notification): bool
    {
        Log::info('agriAid notification push not sent (no gateway configured)', [
            'user_id' => $user->id,
            'notification_id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'deep_link' => $notification->getDeepLink(),
        ]);

        return false;
    }
}
