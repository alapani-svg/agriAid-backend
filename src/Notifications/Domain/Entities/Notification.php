<?php

namespace App\Notifications\Domain\Entities;

use App\Notifications\Domain\Events\NotificationSent;
use App\Notifications\Domain\ValueObjects\NotificationStatus;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Shared\Domain\AggregateRoot;

final class Notification extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly NotificationType $type,
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $deepLink,
        private readonly ?string $idempotencyKey,
        private NotificationStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $deliveredAt = null,
        private ?\DateTimeImmutable $seenAt = null,
        private ?\DateTimeImmutable $interactedAt = null,
    ) {}

    /**
     * Ingest a new notification. Delivery to the in-app inbox is immediate
     * (a database write), so the notification starts out "delivered".
     */
    public static function create(
        string $id,
        string $userId,
        NotificationType $type,
        string $title,
        string $message,
        ?string $deepLink = null,
        ?string $idempotencyKey = null,
    ): self {
        $now = new \DateTimeImmutable();

        $notification = new self(
            id: $id,
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            deepLink: $deepLink,
            idempotencyKey: $idempotencyKey,
            status: NotificationStatus::DELIVERED,
            createdAt: $now,
            deliveredAt: $now,
        );

        $notification->recordEvent(new NotificationSent($notification));

        return $notification;
    }

    /**
     * Rehydrate a notification from persistence without re-emitting the
     * NotificationSent domain event.
     */
    public static function fromPersistence(
        string $id,
        string $userId,
        NotificationType $type,
        string $title,
        string $message,
        ?string $deepLink,
        ?string $idempotencyKey,
        NotificationStatus $status,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $deliveredAt,
        ?\DateTimeImmutable $seenAt,
        ?\DateTimeImmutable $interactedAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            deepLink: $deepLink,
            idempotencyKey: $idempotencyKey,
            status: $status,
            createdAt: $createdAt,
            deliveredAt: $deliveredAt,
            seenAt: $seenAt,
            interactedAt: $interactedAt,
        );
    }

    public function markSeen(): void
    {
        if ($this->status === NotificationStatus::SEEN || $this->status === NotificationStatus::INTERACTED) {
            return;
        }

        $this->status = NotificationStatus::SEEN;
        $this->seenAt = new \DateTimeImmutable();
    }

    public function markInteracted(): void
    {
        $this->status = NotificationStatus::INTERACTED;
        $this->interactedAt = new \DateTimeImmutable();

        if ($this->seenAt === null) {
            $this->seenAt = $this->interactedAt;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDeepLink(): ?string
    {
        return $this->deepLink;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function getStatus(): NotificationStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getSeenAt(): ?\DateTimeImmutable
    {
        return $this->seenAt;
    }

    public function getInteractedAt(): ?\DateTimeImmutable
    {
        return $this->interactedAt;
    }

    public function isUnread(): bool
    {
        return $this->status->isUnread();
    }
}
