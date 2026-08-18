<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(Notification::create($data), 201);
    }
}
