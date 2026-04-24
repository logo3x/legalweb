<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'legal_case_id',
    'case_event_id',
    'name',
    'description',
    'file_path',
    'file_type',
    'file_size',
    'uploaded_by',
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
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

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

    public function caseEvent(): BelongsTo
    {
        return $this->belongsTo(CaseEvent::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
