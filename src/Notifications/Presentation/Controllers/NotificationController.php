<?php

namespace App\Notifications\Presentation\Controllers;

use App\Mail\BrandedNotification;
use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\Exceptions\NotificationNotFoundException;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Notifications\Presentation\Requests\CreateNotificationRequest;
use App\Notifications\Presentation\Resources\NotificationResource;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NotificationController
{
    public function __construct(
        private readonly NotificationApplicationService $notificationService,
    ) {}

    /**
     * Paginated inbox for the authenticated user (infinite-scroll friendly).
     * GET /api/notifications?page=1&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->notificationService->listForUser($request->user(), $perPage, $page);

        return response()->json([
            'data' => NotificationResource::fromCollection($result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'has_more' => $result['current_page'] < $result['last_page'],
            ],
            'unread_count' => $result['unread_count'],
        ]);
    }

    /**
     * Admin/internal endpoint to manually push a notification to a user.
     * POST /api/notifications
     */
    public function store(Request $request): JsonResponse
    {
        $dto = CreateNotificationRequest::fromArray($request->all());
        $errors = $dto->validate();

        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $user = User::find($dto->userId);
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $notification = $this->notificationService->notify(
            user: $user,
            type: NotificationType::fromString($dto->type),
            title: $dto->title,
            message: $dto->message,
            deepLink: $dto->deepLink,
            idempotencyKey: $dto->idempotencyKey,
        );

        if ($notification === null) {
            return response()->json(['error' => 'Notification suppressed by rate limiting'], 429);
        }

        $this->sendEmailToUser($user, $dto->title, $dto->message);

        AuditLogger::log(
            action: 'notification.sent',
            category: 'notification',
            metadata: ['user_id' => $user->id, 'title' => $dto->title, 'type' => $dto->type],
        );

        return response()->json(['data' => NotificationResource::fromEntity($notification)], 201);
    }

    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => ['nullable', 'string', Rule::in(User::ROLES)],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'deep_link' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:60'],
        ]);

        $type = NotificationType::fromString($data['type'] ?? 'system.alert');

        $query = User::query();
        if (! empty($data['role'])) {
            $query->where('role', $data['role']);
        }
        $users = $query->get();

        $sent = 0;
        $skipped = 0;
        $broadcastKey = 'broadcast-' . Str::random(12);

        foreach ($users as $user) {
            $notification = $this->notificationService->notify(
                $user,
                $type,
                $data['title'],
                $data['message'],
                $data['deep_link'] ?? null,
                $broadcastKey . '-' . $user->id,
            );

            if ($notification !== null) {
                $sent++;
                $this->sendEmailToUser($user, $data['title'], $data['message']);
            } else {
                $skipped++;
            }
        }

        AuditLogger::log(
            action: 'notification.broadcast',
            category: 'notification',
            metadata: ['title' => $data['title'], 'role_filter' => $data['role'] ?? null, 'sent' => $sent, 'skipped' => $skipped],
        );

        return response()->json(['sent' => $sent, 'skipped' => $skipped]);
    }

    public function markSeen(Request $request, string $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markSeen($request->user(), $id);

            return response()->json(['data' => NotificationResource::fromEntity($notification)]);
        } catch (NotificationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function markInteracted(Request $request, string $id): JsonResponse
    {
        try {
            $notification = $this->notificationService->markInteracted($request->user(), $id);

            return response()->json(['data' => NotificationResource::fromEntity($notification)]);
        } catch (NotificationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function markAllSeen(Request $request): JsonResponse
    {
        $this->notificationService->markAllSeen($request->user());

        return response()->json(['message' => 'All notifications marked as seen.']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->notificationService->delete($request->user(), $id);

            return response()->json(['message' => 'Notification removed.']);
        } catch (NotificationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Admin endpoint to update an existing notification's content.
     * PUT /api/admin/notifications/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string', 'max:2000'],
            'deep_link' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:60'],
        ]);

        $notification = \App\Models\Notification::find($id);
        if ($notification === null) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->update($data);

        return response()->json([
            'data' => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'deep_link' => $notification->deep_link,
                'status' => $notification->status,
                'updated_at' => $notification->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Admin endpoint to resend an existing notification (optionally modified) to its recipient.
     * POST /api/admin/notifications/{id}/resend
     */
    public function resend(Request $request, string $id): JsonResponse
    {
        $original = \App\Models\Notification::find($id);
        if ($original === null) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'deep_link' => ['nullable', 'string', 'max:255'],
        ]);

        $title = $data['title'] ?? $original->title;
        $message = $data['message'] ?? $original->message;
        $deepLink = $data['deep_link'] ?? $original->deep_link;

        $user = User::find($original->user_id);
        if ($user === null) {
            return response()->json(['error' => 'Original recipient not found'], 404);
        }

        $notification = $this->notificationService->notify(
            user: $user,
            type: NotificationType::fromString($original->type),
            title: $title,
            message: $message,
            deepLink: $deepLink,
            idempotencyKey: 'resend-' . $id . '-' . Str::random(8),
        );

        if ($notification === null) {
            return response()->json(['error' => 'Notification suppressed by rate limiting'], 429);
        }

        $this->sendEmailToUser($user, $title, $message);

        return response()->json([
            'data' => NotificationResource::fromEntity($notification),
            'message' => 'Notification resent successfully.',
        ], 201);
    }

    /**
     * Admin endpoint to list all notifications (for management).
     * GET /api/admin/notifications
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));
        $query = \App\Models\Notification::query()
            ->with('user:id,name,email,role')
            ->orderByDesc('created_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to);
        }

        $result = $query->paginate($perPage);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'has_more' => $result->hasMorePages(),
            ],
        ]);
    }

    /**
     * Admin endpoint to delete any notification regardless of recipient.
     * DELETE /api/admin/notifications/{id}
     */
    public function adminDestroy(string $id): JsonResponse
    {
        $notification = \App\Models\Notification::find($id);

        if ($notification === null) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->delete();

        AuditLogger::log(
            action: 'notification.deleted',
            category: 'notification',
            metadata: ['notification_id' => $id, 'title' => $notification->title],
        );

        return response()->json(['message' => 'Notification deleted.']);
    }

    /**
     * Send the notification content to the user's registered e-mail address.
     * Failures are logged but do not block the in-app notification.
     */
    private function sendEmailToUser(User $user, string $title, string $message): void
    {
        try {
            Mail::to($user->email)->send(
                new BrandedNotification(
                    title: $title,
                    body: $message,
                    recipientName: $user->name,
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send notification e-mail', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
