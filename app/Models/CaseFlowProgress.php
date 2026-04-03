<?php

namespace App\Models;

use Database\Factories\CaseFlowProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_case_id',
    'flow_step_id',
    'status',
    'completed_at',
    'completed_by',
    'notes',
])]
class CaseFlowProgress extends Model
{
    /** @use HasFactory<CaseFlowProgressFactory> */
    use HasFactory;

    protected $table = 'case_flow_progress';

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function flowStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
