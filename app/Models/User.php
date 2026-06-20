<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'role_id', 'full_name', 'email', 'phone', 'bio', 'avatar_path', 'password_hash',
    ];

    protected $hidden = ['password_hash'];

    /**
     * Laravel Auth reads getAuthPassword() — returns password_hash column.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Expose 'password' attribute so Auth::attempt() hashing check works.
     * بدون هذا، Auth::attempt() لا يجد خاصية 'password' ويفشل.
     */
    public function getPasswordAttribute(): string
    {
        return $this->password_hash;
    }

    public function getAuthIdentifierName(): string
    {
        return 'email';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    public function isAdmin(): bool
    {
        return $this->role && strtolower($this->role->name) === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role && strtolower($this->role->name) === 'user';
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'created_by');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'created_by');
    }

    public function attendingMeetings()
    {
        return $this->belongsToMany(Meeting::class, 'meeting_user');
    }

    public function handledServiceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'handled_by');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_user_id');
    }

    public function receivedMessages()
    {
        return $this->morphMany(Message::class, 'receiver');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
