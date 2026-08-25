<?php

namespace App\Notifications\Application\Services;

use App\Models\User;
use App\Notifications\Domain\Entities\Notification;
use App\Notifications\Domain\Exceptions\NotificationNotFoundException;
use App\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Notifications\Infrastructure\Channels\EmailChannel;
use App\Notifications\Infrastructure\Channels\PushChannel;
use App\Notifications\Infrastructure\Channels\SmsChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotificationApplicationService
{
    /** Simple per-user rate limit: max notifications ingested per minute. */
    private const RATE_LIMIT_PER_MINUTE = 30;

    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly EmailChannel $emailChannel,
        private readonly SmsChannel $smsChannel,
        private readonly PushChannel $pushChannel,
    ) {}

    /**
     * Ingest a trigger event into the notification center.
     *
     * - Idempotency: if $idempotencyKey was already used for this user, the
     *   existing notification is returned instead of creating a duplicate.
     * - Throttling: once a user exceeds RATE_LIMIT_PER_MINUTE ingested
     *   notifications, further ones are silently dropped to protect against
     *   a misbehaving trigger spamming the client.
     * - Routing: always written to the in-app inbox; additionally pushed
     *   through the user's preferred channel (email/sms/both/none).
     */
    public function notify(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        ?string $deepLink = null,
        ?string $idempotencyKey = null,
    ): ?Notification {
        if ($idempotencyKey !== null) {
            $existing = $this->notifications->findByIdempotencyKey((string) $user->id, $idempotencyKey);
            if ($existing !== null) {
                return $existing;
            }
        }

        if (! $this->passesThrottle((string) $user->id)) {
            return null;
        }

        $notification = Notification::create(
            id: (string) Str::uuid(),
            userId: (string) $user->id,
            type: $type,
            title: $title,
            message: $message,
            deepLink: $deepLink,
            idempotencyKey: $idempotencyKey,
        );

        $this->notifications->save($notification);

        $this->routeToPreferredChannel($user, $notification);

        return $notification;
    }

    public function markSeen(User $user, string $notificationId): Notification
    {
        $notification = $this->authorized($user, $notificationId);
        $notification->markSeen();
        $this->notifications->save($notification);

        return $notification;
    }

    public function markInteracted(User $user, string $notificationId): Notification
    {
        $notification = $this->authorized($user, $notificationId);
        $notification->markInteracted();
        $this->notifications->save($notification);

        return $notification;
    }

    public function markAllSeen(User $user): void
    {
        $this->notifications->markAllSeenForUser((string) $user->id);
    }

    public function delete(User $user, string $notificationId): void
    {
        $notification = $this->authorized($user, $notificationId);
        $this->notifications->delete($notification);
    }

    /**
     * @return array{data: Notification[], total: int, per_page: int, current_page: int, last_page: int, unread_count: int}
     */
    public function listForUser(User $user, int $perPage = 20, int $page = 1): array
    {
        $result = $this->notifications->paginateForUser((string) $user->id, $perPage, $page);
        $result['unread_count'] = $this->notifications->countUnreadForUser((string) $user->id);

        return $result;
    }

    private function authorized(User $user, string $notificationId): Notification
    {
        $notification = $this->notifications->findById($notificationId);

        if ($notification === null || $notification->getUserId() !== (string) $user->id) {
            throw new NotificationNotFoundException("Notification not found: {$notificationId}");
        }

        return $notification;
    }

    private function routeToPreferredChannel(User $user, Notification $notification): void
    {
        $preference = $user->notification_preference ?? 'email';

        if ($preference === 'email' || $preference === 'both') {
            $this->emailChannel->send($user, $notification);
        }

        if ($preference === 'sms' || $preference === 'both') {
            $this->smsChannel->send($user, $notification);
        }

        // Web/mobile push is attempted opportunistically regardless of the
        // mail/SMS preference — it is a best-effort, non-intrusive channel.
        $this->pushChannel->send($user, $notification);
    }

    private function passesThrottle(string $userId): bool
    {
        $key = "notifications:throttle:{$userId}:" . now()->format('YmdHi');
        $count = Cache::get($key, 0);

        if ($count >= self::RATE_LIMIT_PER_MINUTE) {
            return false;
        }

        Cache::put($key, $count + 1, now()->addMinutes(2));

        return true;
    }
}
