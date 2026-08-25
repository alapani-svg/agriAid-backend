<?php

namespace App\Notifications\Domain\ValueObjects;

/**
 * Delivery/engagement state machine for an in-app notification:
 * Sent -> Delivered -> Seen -> Interacted.
 */
enum NotificationStatus: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case SEEN = 'seen';
    case INTERACTED = 'interacted';

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $status): self
    {
        return self::from($status);
    }

    public function isUnread(): bool
    {
        return $this === self::SENT || $this === self::DELIVERED;
    }
}
