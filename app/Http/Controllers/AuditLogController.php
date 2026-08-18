<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(AuditLog::orderByDesc('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'actor_name' => 'nullable|string|max:255',
            'action' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'auditable_type' => 'nullable|string|max:255',
            'auditable_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
            'ip_address' => 'nullable|string|max:45',
        ]);

        return response()->json(AuditLog::create($data), 201);
    }
}
