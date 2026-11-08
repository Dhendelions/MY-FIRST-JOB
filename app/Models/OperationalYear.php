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

        // If no password is set on this operational year, access is open
        if (!$this->hasPassword()) {
            return true;
        }

        $session = request()->hasSession() ? request()->session() : session();
        if (!$session) {
            return false;
        }

        $authentication = $session->get('admin_year_auth');

        return is_array($authentication)
            && (int) ($authentication['user_id'] ?? 0) === (int) $user->id
            && (int) ($authentication['year_id'] ?? 0) === (int) $this->id
            && (int) ($authentication['password_version'] ?? 0) === (int) ($this->password_version ?? 1);
    }

    /**
     * Unlock this year workspace for the specified user in session.
     */
    public function unlockForUser(int $userId): void
    {
        $session = request()->hasSession() ? request()->session() : session();
        if ($session) {
            $session->put('admin_year_auth', [
                'user_id' => $userId,
                'year_id' => $this->id,
                'password_version' => (int) ($this->password_version ?? 1),
            ]);
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
            $authentication = $session->get('admin_year_auth');
            if (
                is_array($authentication)
                && (int) ($authentication['user_id'] ?? 0) === $userId
                && (int) ($authentication['year_id'] ?? 0) === (int) $this->id
            ) {
                $session->forget('admin_year_auth');
            }
            $session->forget("year_unlocked_{$this->id}_{$userId}");
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
