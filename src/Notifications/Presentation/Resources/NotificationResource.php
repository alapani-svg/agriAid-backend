<?php

namespace App\Notifications\Presentation\Resources;

use App\Notifications\Domain\Entities\Notification;

class NotificationResource
{
    /**
     * @return array<string, mixed>
     */
    public static function fromEntity(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType()->toString(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'deep_link' => $notification->getDeepLink(),
            'status' => $notification->getStatus()->toString(),
            'is_unread' => $notification->isUnread(),
            'created_at' => $notification->getCreatedAt()->format(DATE_ATOM),
            'delivered_at' => $notification->getDeliveredAt()?->format(DATE_ATOM),
            'seen_at' => $notification->getSeenAt()?->format(DATE_ATOM),
            'interacted_at' => $notification->getInteractedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * @param Notification[] $notifications
     * @return array<int, array<string, mixed>>
     */
    public static function fromCollection(array $notifications): array
    {
        return array_map(fn (Notification $n) => self::fromEntity($n), $notifications);
    }
}
