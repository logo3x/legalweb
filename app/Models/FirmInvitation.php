<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'firm_id',
    'invited_by',
    'email',
    'role',
    'permissions',
    'status',
    'token',
    'accepted_at',
    'expires_at',
])]
class FirmInvitation extends Model
{
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public static function createForEmail(Firm $firm, User $inviter, string $email, string $role, ?array $permissions = null): self
    {
        return self::create([
            'firm_id' => $firm->id,
            'invited_by' => $inviter->id,
            'email' => strtolower(trim($email)),
            'role' => $role,
            'permissions' => $permissions,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
