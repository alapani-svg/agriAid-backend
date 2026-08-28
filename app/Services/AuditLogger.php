<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $action, string $category = 'system', array $metadata = [], ?string $auditableType = null, ?int $auditableId = null): void
    {
        $user = Auth::user();
        AuditLog::create([
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? 'System',
            'action' => $action,
            'category' => $category,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
        ]);
    }
}
