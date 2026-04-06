<?php

namespace App\Models;

use App\Models\Scopes\FirmScope;
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
            'is_demo' => 'boolean',
        ];
    }

    public function generatePortalToken(): string
    {
        $this->update([
            'portal_token' => Str::random(64),
            'portal_enabled' => true,
        ]);

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
}
