<?php

namespace App\Models;

use App\Models\Scopes\FirmScope;
use App\Services\N8nWebhook;
use Database\Factories\LegalCaseFactory;
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
