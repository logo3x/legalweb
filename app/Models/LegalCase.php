<?php

namespace App\Models;

use App\Models\Scopes\FirmScope;
use App\Services\FirmPlan;
use App\Services\N8nWebhook;
use Database\Factories\LegalCaseFactory;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'case_number',
    'external_case_number',
    'judicial_source',
    'title',
    'description',
    'case_type_id',
    'case_flow_id',
    'client_id',
    'user_id',
    'status',
    'court',
    'judge',
    'opposing_party',
    'priority',
    'started_at',
    'closed_at',
    'portal_token',
    'portal_enabled',
    'firm_id',
    'is_demo',
    'last_tyba_sync',
    'tyba_data',
    'auto_report_enabled',
])]
#[ScopedBy(FirmScope::class)]
class LegalCase extends Model
{
    /** @use HasFactory<LegalCaseFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'closed_at' => 'date',
            'portal_enabled' => 'boolean',
            'auto_report_enabled' => 'boolean',
            'is_demo' => 'boolean',
            'last_tyba_sync' => 'datetime',
            'tyba_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Limite de casos activos del plan (plan Gratuito despues de la prueba).
        // Solo para acciones de usuarios: cron, colas y comandos no se bloquean.
        static::saving(function (LegalCase $case): void {
            $user = auth()->user();

            if (! $user || $user->role === 'superadmin' || $case->is_demo || ! $case->firm_id || ! $case->becomesActive()) {
                return;
            }

            $firm = $case->relationLoaded('firm') ? $case->firm : Firm::find($case->firm_id);

            if (! $firm || $firm->planLimits()->canAddActiveCase()) {
                return;
            }

            FilamentNotification::make()
                ->title('Alcanzo el limite de casos activos')
                ->body(FirmPlan::upgradeMessage('El plan Gratuito permite '.$firm->planLimits()->maxActiveCases().' casos activos. Puede cerrar o archivar un caso, o'))
                ->warning()
                ->persistent()
                ->send();

            throw new Halt;
        });

        static::created(function (LegalCase $case): void {
            if ($case->is_demo) {
                return;
            }

            app(N8nWebhook::class)->dispatch('case.created', [
                'firm' => N8nWebhook::firmData(Firm::find($case->firm_id)),
                'case' => N8nWebhook::caseData($case),
                'lawyer' => ['id' => $case->user_id, 'name' => $case->user?->name],
            ], $case->firm_id);
        });

        static::updated(function (LegalCase $case): void {
            if ($case->is_demo || ! $case->wasChanged('status')) {
                return;
            }

            app(N8nWebhook::class)->dispatch('case.status_changed', [
                'firm' => N8nWebhook::firmData(Firm::find($case->firm_id)),
                'case' => N8nWebhook::caseData($case),
                'previous_status' => $case->getOriginal('status'),
            ], $case->firm_id);
        });
    }

    /**
     * El guardado deja el caso en un estado activo que antes no tenia (nuevo o reabierto).
     */
    public function becomesActive(): bool
    {
        $isActive = in_array($this->status ?? 'abierto', FirmPlan::ACTIVE_STATUSES, true);

        if (! $this->exists) {
            return $isActive;
        }

        return $isActive && $this->isDirty('status') && ! in_array($this->getOriginal('status'), FirmPlan::ACTIVE_STATUSES, true);
    }

    public const SOURCE_RAMA = 'rama';

    public const SOURCE_SAMAI = 'samai';

    public function isSamai(): bool
    {
        return $this->judicial_source === self::SOURCE_SAMAI;
    }

    /**
     * Nombre de la fuente oficial del proceso para mostrar en la interfaz.
     */
    public function judicialSourceLabel(): string
    {
        return $this->isSamai() ? 'SAMAI (Consejo de Estado)' : 'Rama Judicial';
    }

    public function generatePortalToken(): string
    {
        // Preserve existing token so previously shared links keep working.
        // Only generate a new one when none exists yet.
        if (! $this->portal_token) {
            $this->portal_token = Str::random(64);
        }

        $this->portal_enabled = true;
        $this->save();

        return $this->portal_token;
    }

    /**
     * Enlace del portal del cliente, solo si el portal esta habilitado y tiene token.
     */
    public function portalUrl(): ?string
    {
        return $this->portal_enabled && $this->portal_token
            ? route('portal.show', $this->portal_token)
            : null;
    }

    /**
     * Nombre con el que se firman los avisos al cliente: la firma o, si no hay, el abogado.
     */
    public function senderName(): string
    {
        return $this->user?->firm?->name ?? $this->user?->name ?? 'su abogado';
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function caseFlow(): BelongsTo
    {
        return $this->belongsTo(CaseFlow::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function flowProgress(): HasMany
    {
        return $this->hasMany(CaseFlowProgress::class);
    }

    public function portalAccessLogs(): HasMany
    {
        return $this->hasMany(PortalAccessLog::class);
    }

    public function tybaSyncLogs(): HasMany
    {
        return $this->hasMany(TybaSyncLog::class);
    }

    public function billingEntries(): HasMany
    {
        return $this->hasMany(BillingEntry::class);
    }
}
