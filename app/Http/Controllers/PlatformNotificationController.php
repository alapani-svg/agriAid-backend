<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PlatformNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Notification::orderByDesc('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|max:255',
            'channel' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'read_at' => 'nullable|date',
        ]);

        $data['id'] = (string) Str::uuid();

        $notification = Notification::create($data);

        if (! empty($data['user_id'])) {
            $this->sendEmailToUser(User::find($data['user_id']), $data['title'], $data['message']);
        }

        return response()->json($notification, 201);
    }

    /**
     * Deliver a plain-text copy of the platform notification to the user's inbox.
     * E-mail failures are logged but do not block the in-app record.
     */
    private function sendEmailToUser(?User $user, string $title, string $message): void
    {
        if ($user === null) {
            return;
        }

        try {
            Mail::raw("{$message}\n\n— agriAid Team", function ($mail) use ($user, $title) {
                $mail->to($user->email, $user->name)
                    ->subject($title);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send platform notification e-mail', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
