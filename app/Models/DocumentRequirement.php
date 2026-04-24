<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequirement extends Model
{
    protected $fillable = [
        'legal_case_id',
        'name',
        'description',
        'responsible',
        'entity',
        'estimated_cost',
        'status',
        'priority',
        'due_date',
        'received_at',
        'external_url',
        'notes',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'received_at' => 'date',
            'estimated_cost' => 'decimal:2',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
