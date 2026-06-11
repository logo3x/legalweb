<?php

namespace App\Models;

use Database\Factories\CaseEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'legal_case_id',
    'title',
    'description',
    'event_date',
    'event_day',
    'event_type',
    'is_milestone',
    'user_id',
])]
class CaseEvent extends Model
{
    /** @use HasFactory<CaseEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'event_day' => 'date',
            'is_milestone' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Auto-poblar event_day desde event_date para garantizar el indice unico
        // (legal_case_id, title, event_day). Asi cualquier insercion siempre
        // tiene event_day calculado, incluso si el codigo cliente lo olvido.
        static::saving(function (self $event) {
            if ($event->event_date && empty($event->event_day)) {
                $event->event_day = Carbon::parse($event->event_date)->toDateString();
            }
        });
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
