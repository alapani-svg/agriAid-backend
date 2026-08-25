<?php

namespace App\Notifications\Domain\Events;

use App\Notifications\Domain\Entities\Notification;
use App\Shared\Domain\Events\DomainEvent;

final readonly class NotificationSent implements DomainEvent
{
    public function __construct(
        private Notification $notification
    ) {}

    public function getNotification(): Notification
    {
        return $this->notification;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->notification->getCreatedAt();
    }
}
