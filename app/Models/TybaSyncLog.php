<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TybaSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'legal_case_id',
        'status',
        'nuevas_actuaciones',
        'mensaje',
        'origen',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}
