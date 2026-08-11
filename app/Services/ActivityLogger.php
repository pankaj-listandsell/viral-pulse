<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Support\Fingerprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): void
    {
        $request = request();

        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_hash' => Fingerprint::ip($request->ip()),
                'user_agent' => str($request->userAgent() ?? '')->limit(250)->toString() ?: null,
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the request it is auditing.
            Log::warning('Activity log write failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
