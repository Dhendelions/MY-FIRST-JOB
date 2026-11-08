<?php

namespace App\Services;

use App\Models\CompanyActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Keys that must never be recorded in properties or audit logs.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        'secret',
        'api_key',
        'auth_token',
    ];

    /**
     * Log a company activity event.
     */
    public static function log(
        string $action,
        string $description,
        ?Model $resource = null,
        array $properties = [],
        ?User $actor = null
    ): CompanyActivityLog {
        $actorUser = $actor ?? Auth::user();

        $safeProperties = static::sanitize($properties);

        return CompanyActivityLog::create([
            'user_id' => $actorUser?->id,
            'user_name' => $actorUser?->name ?? 'Sistem',
            'action' => $action,
            'resource_type' => $resource ? class_basename($resource) : ($properties['resource_type'] ?? null),
            'resource_id' => $resource ? (string) $resource->getKey() : (isset($properties['resource_id']) ? (string)$properties['resource_id'] : null),
            'description' => $description,
            'properties' => !empty($safeProperties) ? $safeProperties : null,
            'created_at' => now('Asia/Jakarta'),
        ]);
    }

    /**
     * Sanitize properties array, recursively stripping passwords, hashes, and secrets.
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string)$key);

            $isSensitive = false;
            foreach (static::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = static::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

