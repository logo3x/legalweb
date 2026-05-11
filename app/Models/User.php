<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'firm_id', 'role', 'google_id', 'avatar', 'terms_accepted_at', 'terms_ip', 'tour_completed_at', 'last_login_at', 'login_count'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public const AVAILABLE_PERMISSIONS = [
        'cases.view' => 'Ver casos',
        'cases.create' => 'Crear casos',
        'cases.edit' => 'Editar casos',
        'cases.delete' => 'Eliminar casos',
        'clients.view' => 'Ver clientes',
        'clients.create' => 'Crear clientes',
        'clients.edit' => 'Editar clientes',
        'events.create' => 'Crear actuaciones',
        'events.edit' => 'Editar actuaciones',
        'documents.upload' => 'Subir documentos',
        'flow.manage' => 'Gestionar flujo de proceso',
        'portal.share' => 'Compartir portal con cliente',
        'ai.use' => 'Usar asistente IA',
        'reminders.manage' => 'Gestionar agenda',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'terms_accepted_at' => 'datetime',
            'tour_completed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->terms_accepted_at = now();
            $user->terms_ip = request()->ip();
        });
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array($this->role, ['superadmin', 'admin'])) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function casePermissions(): HasMany
    {
        return $this->hasMany(CasePermission::class);
    }

    public function hasCasePermission(int $caseId, string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $cp = $this->casePermissions()->where('legal_case_id', $caseId)->first();

        return $cp ? $cp->hasPermission($permission) : false;
    }

    public function accessibleCaseIds(): array
    {
        if ($this->isAdmin()) {
            return [];
        }

        return $this->casePermissions()->pluck('legal_case_id')->toArray();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function caseEvents(): HasMany
    {
        return $this->hasMany(CaseEvent::class);
    }
}
