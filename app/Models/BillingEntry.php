<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingEntry extends Model
{
    protected $fillable = [
        'legal_case_id',
        'user_id',
        'type',
        'description',
        'hours',
        'rate_per_hour',
        'amount',
        'entry_date',
        'is_billable',
        'is_billed',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hours' => 'decimal:2',
            'rate_per_hour' => 'decimal:2',
            'amount' => 'decimal:2',
            'is_billable' => 'boolean',
            'is_billed' => 'boolean',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
