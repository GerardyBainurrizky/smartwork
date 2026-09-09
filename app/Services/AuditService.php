<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuditService
{
    public function log(string $action, string $module, $subject = null, array $details = []): void
    {
        $user = auth()->user();
        $entry = [
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'details' => $details,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('audit')->info(json_encode($entry, JSON_UNESCAPED_UNICODE));
    }
}