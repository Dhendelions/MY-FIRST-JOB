<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'whatsapp',
        'role_title',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Group memberships of the user.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Fisheries groups the user belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(FisheriesGroup::class, 'group_members', 'user_id', 'group_id')
            ->withPivot('role', 'status', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Groups created by the user.
     */
    public function createdGroups(): HasMany
    {
        return $this->hasMany(FisheriesGroup::class, 'created_by');
    }

    /**
     * Notifications for the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderByDesc('created_at');
    }

    /**
     * Unread notifications.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_read', false);
    }

    /**
     * Sent friendships.
     */
    public function friendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Received friendships.
     */
    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Helper for avatar image URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && file_exists(public_path($this->avatar))) {
            return asset($this->avatar);
        }

        $initials = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$initials}&background=0A192F&color=38BDF8&bold=true&size=128";
    }

    /**
     * Get friendship status with another user.
     * Returns: 'self', 'friends', 'pending_sent', 'pending_received', 'none'
     */
    public function getFriendshipStatusWith(int $targetUserId): string
    {
        if ($this->id === $targetUserId) {
            return 'self';
        }

        $friendship = Friendship::where(function ($q) use ($targetUserId) {
            $q->where('user_id', $this->id)->where('friend_id', $targetUserId);
        })->orWhere(function ($q) use ($targetUserId) {
            $q->where('user_id', $targetUserId)->where('friend_id', $this->id);
        })->first();

        if (!$friendship) {
            return 'none';
        }

        if ($friendship->status === 'accepted') {
            return 'friends';
        }

        if ($friendship->status === 'pending') {
            return $friendship->user_id === $this->id ? 'pending_sent' : 'pending_received';
        }

        return 'none';
    }

    /**
     * Get all accepted friends of this user.
     */
    public function getAcceptedFriends()
    {
        $sentFriendIds = $this->friendships()->where('status', 'accepted')->pluck('friend_id');
        $receivedFriendIds = $this->receivedFriendships()->where('status', 'accepted')->pluck('user_id');
        $allFriendIds = $sentFriendIds->merge($receivedFriendIds)->unique();

        return User::whereIn('id', $allFriendIds)->get();
    }

    /**
     * Get pending friend requests received by this user.
     */
    public function getPendingReceivedRequests()
    {
        return $this->receivedFriendships()->where('status', 'pending')->with('user')->get();
    }
}
