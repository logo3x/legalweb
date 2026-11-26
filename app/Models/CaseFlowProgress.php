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
    'started_at',
    'deadline_notices',
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
            'started_at' => 'datetime',
            'deadline_notices' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // El plazo del paso corre desde que se inicia: se fija la fecha la primera vez que pasa a
        // "en_progreso", y no cambia con ediciones posteriores.
        static::saving(function (CaseFlowProgress $progress): void {
            if ($progress->status === 'en_progreso' && $progress->started_at === null) {
                $progress->started_at = now();
            }
        });
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
