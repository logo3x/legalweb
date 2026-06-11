<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'firm_id',
    'user_id',
    'legal_case_id',
    'action',
    'provider',
    'model',
    'prompt_tokens',
    'completion_tokens',
    'total_tokens',
    'latency_ms',
    'success',
    'error_message',
    'meta',
    'created_at',
])]
class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'resumen' => 'Resumen de caso',
        'siguiente_paso' => 'Sugerir siguiente paso',
        'borrador' => 'Borrador de documento',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}
