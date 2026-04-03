<?php

namespace App\Models;

use Database\Factories\CaseFlowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['case_type_id', 'name', 'description', 'is_active'])]
class CaseFlow extends Model
{
    /** @use HasFactory<CaseFlowFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FlowStep::class)->orderBy('order');
    }
}
