<?php

namespace App\Models;

use App\Models\Scopes\FirmScope;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'document_type',
    'document_number',
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'city',
    'notes',
    'user_id',
    'firm_id',
    'is_demo',
    'process_monitoring_enabled',
    'process_monitoring_last_at',
    'process_monitoring_known',
])]
#[ScopedBy(FirmScope::class)]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'process_monitoring_enabled' => 'boolean',
            'process_monitoring_last_at' => 'datetime',
            'process_monitoring_known' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    /**
     * Codigo de tipo de documento que usa la consulta de la Rama Judicial.
     */
    public function ramaDocumentTypeCode(): int
    {
        return match ($this->document_type) {
            'CE' => 2,
            'NIT' => 3,
            'PP' => 4,
            'TI' => 5,
            default => 1,
        };
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
