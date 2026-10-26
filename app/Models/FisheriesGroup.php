<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class FisheriesGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'invite_code',
        'password',
        'location',
        'pond_count',
        'avatar',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Determine if workspace has a password set.
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Verify plain password against the stored hash.
     */
    public function verifyPassword(?string $plainPassword): bool
    {
        if (empty($this->password)) {
            return true;
        }

        if (empty($plainPassword)) {
            return false;
        }

        return \Illuminate\Support\Facades\Hash::check($plainPassword, $this->password);
    }

    /**
     * Determine if this workspace is unlocked for the current session.
     *
     * Optimized: the workspace-level flag is checked first; only when absent
     * do we fall back to iterating sheet-level flags, which requires a DB query.
     */
    public function isUnlockedForSession(): bool
    {
        if (!$this->hasPassword()) {
            return true;
        }

        $session = request()->hasSession() ? request()->session() : null;
        if (!$session) {
            return false;
        }

        // Fast path: workspace-level flag set (e.g. after entering the password once).
        if ($session->get('workspace_unlocked_' . $this->id, false)) {
            return true;
        }

        // Slow path: check individual sheet unlock flags (requires fetching sheet IDs).
        // Use the already-loaded relationship when available to avoid an extra query.
        $sheetIds = $this->relationLoaded('sheets')
            ? $this->sheets->pluck('id')
            : $this->sheets()->pluck('id');

        foreach ($sheetIds as $sheetId) {
            if ($session->get('sheet_unlocked_' . $sheetId, false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admins must re-authenticate with their own account password for each
     * sheet. The session stores only the
     * verified sheet ID, never a password or password hash.
     */
    public function isAdminSheetVerifiedForSession(int $userId, int $sheetId): bool
    {
        if ($this->getMemberRole($userId) !== 'admin') {
            return true;
        }

        if (!request()->hasSession()) {
            return false;
        }

        return (int) request()->session()->get($this->adminVerificationSessionKey($userId), 0) === $sheetId;
    }

    public function markAdminSheetVerifiedForSession(int $userId, int $sheetId): void
    {
        request()->session()->put($this->adminVerificationSessionKey($userId), $sheetId);
    }

    public function clearAdminSheetVerificationForSession(int $userId): void
    {
        if (request()->hasSession()) {
            request()->session()->forget($this->adminVerificationSessionKey($userId));
        }
    }

    public function adminVerificationSessionKey(int $userId): string
    {
        return 'admin_sheet_verified_' . $this->id . '_' . $userId;
    }

    /**
     * Group creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Members relationship.
     */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    /**
     * Active members relationship.
     */
    public function activeMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id')->where('status', 'Aktif');
    }

    /**
     * Users associated through group_members pivot.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot('role', 'status', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Spreadsheet sheets for this fisheries group.
     */
    public function sheets(): HasMany
    {
        return $this->hasMany(SpreadsheetSheet::class, 'group_id');
    }

    /**
     * Generate standard Workspace invite code (e.g., WS-ABCD-12).
     */
    public static function generateInviteCode(string $category = 'WORKSPACE'): string
    {
        $prefix = 'WS';
        $upperCategory = strtoupper($category);
        if (str_contains($upperCategory, 'PROJ') || str_contains($upperCategory, 'PROYEK')) {
            $prefix = 'PROJ';
        } elseif (str_contains($upperCategory, 'TEAM') || str_contains($upperCategory, 'TIM')) {
            $prefix = 'TEAM';
        } elseif (str_contains($upperCategory, 'FIN') || str_contains($upperCategory, 'KEUANGAN')) {
            $prefix = 'FIN';
        } elseif (str_contains($upperCategory, 'OPS') || str_contains($upperCategory, 'OPERASIONAL')) {
            $prefix = 'OPS';
        } elseif (str_contains($upperCategory, 'SALES') || str_contains($upperCategory, 'PENJUALAN')) {
            $prefix = 'SALES';
        }

        do {
            $mid = strtoupper(Str::random(4));
            $num = rand(10, 99);
            $code = "{$prefix}-{$mid}-{$num}";
        } while (self::where('invite_code', $code)->exists());

        return $code;
    }

    /**
     * Avatar URL accessor.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        // The database path is the source of truth. Rendering must not depend
        // on a transient filesystem check during a Livewire/navigate response,
        // otherwise a valid workspace image is replaced by the fallback after
        // switching sheets. Legacy storage paths remain supported.
        if (str_starts_with($this->avatar, 'storage/')) {
            return asset($this->avatar);
        }

        return asset(ltrim($this->avatar, '/'));
    }

    /**
     * Initialize standard general-purpose spreadsheet sheets with blank A-F columns and empty rows.
     */
    public function initializeStandardSheets(): void
    {
        $categories = [
            ['name' => 'Ringkasan Utama', 'category' => 'ringkasan'],
            ['name' => 'Anggaran & Biaya', 'category' => 'anggaran'],
            ['name' => 'Rencana Kerja', 'category' => 'rencana'],
            ['name' => 'Log Operasional', 'category' => 'operasional'],
            ['name' => 'Catatan & Data', 'category' => 'catatan'],
        ];

        // This method is also called when opening legacy workspaces.  Lock the
        // workspace and check again inside the transaction so two requests do
        // not create two copies of the initial sheets.
        \Illuminate\Support\Facades\DB::transaction(function () use ($categories) {
            $group = static::query()->lockForUpdate()->findOrFail($this->id);
            if ($group->sheets()->exists()) {
                return;
            }

            foreach ($categories as $index => $cat) {
                $sheet = $group->sheets()->create([
                    'name' => $cat['name'],
                    'category' => $cat['category'],
                    'metadata' => [
                        'identity_color' => self::sheetIdentityColorKey($index),
                    ],
                ]);

                $sheet->initializeBlankGrid();
            }
        });
    }

    private static function sheetIdentityColorKey(int $index): string
    {
        return SpreadsheetSheet::cyclicIdentityColorKey($index);
    }

    /**
     * Get normalized role for a user in this workspace (leader, admin, karyawan).
     */
    public function getMemberRole(int $userId): ?string
    {
        if ($this->created_by === $userId) {
            return 'leader';
        }

        $member = $this->members()
            ->where('user_id', $userId)
            ->where('status', 'Aktif')
            ->first();

        if (!$member) {
            return null;
        }

        $role = strtolower(trim($member->role));
        if (in_array($role, ['leader', 'ketua tambak', 'pemilik/pengelola tambak', 'owner'])) {
            return 'leader';
        }
        if (in_array($role, ['admin', 'administrator', 'teknisi kolam', 'staff keuangan', 'manager'])) {
            return 'admin';
        }
        return 'karyawan';
    }

    public function isLeader(int $userId): bool
    {
        return $this->getMemberRole($userId) === 'leader';
    }

    public function isAdmin(int $userId): bool
    {
        $role = $this->getMemberRole($userId);
        return $role === 'leader' || $role === 'admin';
    }

    public function isMember(int $userId): bool
    {
        return $this->getMemberRole($userId) !== null;
    }
}
