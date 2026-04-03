<?php

namespace App\Models;

use Database\Factories\FlowStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['case_flow_id', 'name', 'description', 'order', 'days_limit', 'is_required'])]
class FlowStep extends Model
{
    /** @use HasFactory<FlowStepFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function caseFlow(): BelongsTo
    {
        return $this->belongsTo(CaseFlow::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CaseFlowProgress::class);
    }
}
