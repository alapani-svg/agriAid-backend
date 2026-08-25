<?php

namespace App\Notifications\Infrastructure\Repositories;

use App\Notifications\Domain\Entities\Notification;
use App\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use App\Notifications\Domain\ValueObjects\NotificationStatus;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Models\Notification as EloquentNotification;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function save(Notification $notification): void
    {
        $eloquent = EloquentNotification::query()
            ->where('id', $notification->getId())
            ->first();

        if ($eloquent === null) {
            $eloquent = new EloquentNotification();
            $eloquent->id = $notification->getId();
        }

        $eloquent->user_id = $notification->getUserId();
        $eloquent->type = $notification->getType()->toString();
        $eloquent->title = $notification->getTitle();
        $eloquent->message = $notification->getMessage();
        $eloquent->deep_link = $notification->getDeepLink();
        $eloquent->idempotency_key = $notification->getIdempotencyKey();
        $eloquent->status = $notification->getStatus()->toString();
        $eloquent->delivered_at = $notification->getDeliveredAt();
        $eloquent->seen_at = $notification->getSeenAt();
        $eloquent->interacted_at = $notification->getInteractedAt();
        $eloquent->created_at = $notification->getCreatedAt();

        $eloquent->save();
    }

    public function findById(string $id): ?Notification
    {
        $eloquent = EloquentNotification::find($id);

        return $eloquent === null ? null : $this->toDomain($eloquent);
    }

    public function paginateForUser(string $userId, int $perPage, int $page): array
    {
        $paginator = EloquentNotification::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn ($e) => $this->toDomain($e))->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function countUnreadForUser(string $userId): int
    {
        return EloquentNotification::query()
            ->where('user_id', $userId)
            ->whereIn('status', [NotificationStatus::SENT->value, NotificationStatus::DELIVERED->value])
            ->count();
    }

    public function findByIdempotencyKey(string $userId, string $idempotencyKey): ?Notification
    {
        $eloquent = EloquentNotification::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        return $eloquent === null ? null : $this->toDomain($eloquent);
    }

    public function markAllSeenForUser(string $userId): void
    {
        EloquentNotification::query()
            ->where('user_id', $userId)
            ->whereIn('status', [NotificationStatus::SENT->value, NotificationStatus::DELIVERED->value])
            ->update(['status' => NotificationStatus::SEEN->value, 'seen_at' => now()]);
    }

    public function delete(Notification $notification): void
    {
        EloquentNotification::where('id', $notification->getId())->delete();
    }

    private function toDomain(EloquentNotification $eloquent): Notification
    {
        return Notification::fromPersistence(
            id: $eloquent->id,
            userId: (string) $eloquent->user_id,
            type: NotificationType::fromString($eloquent->type),
            title: $eloquent->title,
            message: $eloquent->message,
            deepLink: $eloquent->deep_link,
            idempotencyKey: $eloquent->idempotency_key,
            status: NotificationStatus::fromString($eloquent->status),
            createdAt: \DateTimeImmutable::createFromInterface($eloquent->created_at),
            deliveredAt: $eloquent->delivered_at ? \DateTimeImmutable::createFromInterface($eloquent->delivered_at) : null,
            seenAt: $eloquent->seen_at ? \DateTimeImmutable::createFromInterface($eloquent->seen_at) : null,
            interactedAt: $eloquent->interacted_at ? \DateTimeImmutable::createFromInterface($eloquent->interacted_at) : null,
        );
    }
}
