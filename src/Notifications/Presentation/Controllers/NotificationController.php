<?php

namespace App\Notifications\Presentation\Controllers;

use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\Exceptions\NotificationNotFoundException;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Notifications\Presentation\Requests\CreateNotificationRequest;
use App\Notifications\Presentation\Resources\NotificationResource;
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
     * Send the notification content to the user's registered e-mail address.
     * Failures are logged but do not block the in-app notification.
     */
    private function sendEmailToUser(User $user, string $title, string $message): void
    {
        try {
            Mail::raw("{$message}\n\n— agriAid Team", function ($mail) use ($user, $title) {
                $mail->to($user->email, $user->name)
                    ->subject($title);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send notification e-mail', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
