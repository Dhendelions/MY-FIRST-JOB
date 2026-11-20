<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'is_active',
        'is_archived',
        'created_by',
        'password',
        'password_version',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'password_version' => 'integer',
    ];

    /**
     * Determine if this operational year has password protection configured.
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Verify plain password against the year password hash.
     */
    public function verifyPassword(?string $plain): bool
    {
        if (empty($this->password)) {
            return true;
        }

        if (empty($plain)) {
            return false;
        }

        return \Illuminate\Support\Facades\Hash::check($plain, $this->password);
    }

    /**
     * Determine if this year workspace is unlocked for the specified user or current auth session.
     * Owner has unrestricted access without entering passwords.
     */
    public function isUnlockedForUser(?int $userId = null): bool
    {
        $user = $userId ? \App\Models\User::find($userId) : \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return false;
        }

        // Owner never needs password authentication
        if ($user->isOwner()) {
            return true;
        }

        // When no password is set on the operational year, it is accessible without unlock
        if (!$this->hasPassword()) {
            return true;
        }

        $session = request()->hasSession() ? request()->session() : session();
        if (!$session) {
            return false;
        }

        $currVersion = (int) ($this->password_version ?: 1);

        // 1. Check multi-year session store: admin_authenticated_years
        $authenticatedYears = $session->get('admin_authenticated_years', []);
        if (is_array($authenticatedYears)) {
            $yearRecord = $authenticatedYears[(int) $this->year]
                ?? $authenticatedYears[(string) $this->year]
                ?? $authenticatedYears[(int) $this->id]
                ?? $authenticatedYears[(string) $this->id]
                ?? null;

            if (is_array($yearRecord)) {
                $recUserId = (int) ($yearRecord['user_id'] ?? 0);
                $recYearId = (int) ($yearRecord['year_id'] ?? 0);
                $recYear = (int) ($yearRecord['year'] ?? 0);
                $recVersion = (int) ($yearRecord['password_version'] ?? 1);

                if (
                    $recUserId === (int) $user->id
                    && ($recYearId === (int) $this->id || $recYear === (int) $this->year)
                    && $recVersion === $currVersion
                ) {
                    return true;
                }
            }
        }

        // 2. Fallback check single-year store: admin_year_auth
        $authentication = $session->get('admin_year_auth');
        if (
            is_array($authentication)
            && (int) ($authentication['user_id'] ?? 0) === (int) $user->id
            && (
                (int) ($authentication['year_id'] ?? 0) === (int) $this->id
                || (int) ($authentication['year'] ?? 0) === (int) $this->year
            )
            && (int) ($authentication['password_version'] ?? 1) === $currVersion
        ) {
            return true;
        }

        // 3. Fallback check legacy versioned year_unlocked key
        $legVersion = $session->get("year_unlocked_version_{$this->id}_{$user->id}")
            ?? $session->get("year_unlocked_version_{$this->year}_{$user->id}");
        if ($legVersion !== null && (int) $legVersion === $currVersion) {
            return true;
        }

        return false;
    }

    /**
     * Unlock this year workspace for the specified user in session.
     */
    public function unlockForUser(int $userId): void
    {
        $session = request()->hasSession() ? request()->session() : session();
        if ($session) {
            $version = (int) ($this->password_version ?: 1);
            $record = [
                'user_id' => $userId,
                'year_id' => (int) $this->id,
                'year' => (int) $this->year,
                'password_version' => $version,
                'unlocked_at' => now()->toISOString(),
            ];

            // 1. Store in multi-year dictionary
            $authenticatedYears = $session->get('admin_authenticated_years', []);
            if (!is_array($authenticatedYears)) {
                $authenticatedYears = [];
            }
            $authenticatedYears[(int) $this->year] = $record;
            $authenticatedYears[(int) $this->id] = $record;
            $authenticatedYears[(string) $this->year] = $record;
            $session->put('admin_authenticated_years', $authenticatedYears);

            // 2. Also set current active context in admin_year_auth
            $session->put('admin_year_auth', [
                'user_id' => $userId,
                'year_id' => (int) $this->id,
                'year' => (int) $this->year,
                'password_version' => $version,
            ]);

            // 3. Versioned legacy keys
            $session->put("year_unlocked_version_{$this->id}_{$userId}", $version);
            $session->put("year_unlocked_version_{$this->year}_{$userId}", $version);
            $session->put("admin_year_auth_{$this->id}", true);
            $session->put("admin_year_auth_{$this->year}", true);

            // 4. Proactively mark all 12 monthly workspaces belonging to this year as unlocked
            try {
                $groupIds = $this->monthlyWorkspaces()->pluck('group_id')->filter();
                foreach ($groupIds as $groupId) {
                    $session->put("workspace_unlocked_{$groupId}", true);
                }
            } catch (\Throwable $e) {
                // Silently ignore relation loading errors in isolated test contexts
            }

            $session->save();
        }
    }

    /**
     * Lock this year workspace for the specified user in session.
     */
    public function lockForUser(int $userId): void
    {
        $session = request()->hasSession() ? request()->session() : session();
        if ($session) {
            // Remove from multi-year dictionary
            $authenticatedYears = $session->get('admin_authenticated_years', []);
            if (is_array($authenticatedYears)) {
                unset($authenticatedYears[(int) $this->year], $authenticatedYears[(int) $this->id]);
                $session->put('admin_authenticated_years', $authenticatedYears);
            }

            // Forget active year context if matching this year
            $authentication = $session->get('admin_year_auth');
            if (
                is_array($authentication)
                && (int) ($authentication['user_id'] ?? 0) === $userId
                && (int) ($authentication['year_id'] ?? 0) === (int) $this->id
            ) {
                $session->forget('admin_year_auth');
            }

            $session->forget("year_unlocked_{$this->id}_{$userId}");
            $session->forget("admin_year_auth_{$this->id}");
            $session->save();
        }
    }

    /**
     * Relationship: Monthly workspaces for this operational year.
     */
    public function monthlyWorkspaces(): HasMany
    {
        return $this->hasMany(MonthlyWorkspace::class, 'year_id')->orderBy('month');
    }

    /**
     * Relationship: User who created this operational year.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active operational years.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }

    /**
     * Scope: Archived operational years.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Determine if this operational year is archived.
     */
    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    /**
     * Determine if this operational year is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active && !(bool) $this->is_archived;
    }

    /**
     * Get textual status string ('active', 'archived', 'inactive').
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_archived) {
            return 'archived';
        }

        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Archive this operational year.
     */
    public function archive(): bool
    {
        $this->is_archived = true;
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Restore this operational year.
     */
    public function restore(): bool
    {
        $this->is_archived = false;
        $this->is_active = true;
        return $this->save();
    }
}
