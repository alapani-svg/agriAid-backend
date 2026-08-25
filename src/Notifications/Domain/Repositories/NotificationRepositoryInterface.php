<?php

namespace App\Notifications\Domain\Repositories;

use App\Notifications\Domain\Entities\Notification;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;

    public function findById(string $id): ?Notification;

    /**
     * Returns a Laravel paginator-compatible array of Notification entities
     * for a user, newest first.
     *
     * @return array{data: Notification[], total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginateForUser(string $userId, int $perPage, int $page): array;

    public function countUnreadForUser(string $userId): int;

    public function findByIdempotencyKey(string $userId, string $idempotencyKey): ?Notification;

    public function markAllSeenForUser(string $userId): void;

    public function delete(Notification $notification): void;
}
