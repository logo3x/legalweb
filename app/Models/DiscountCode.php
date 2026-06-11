<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'description',
    'type',
    'amount',
    'max_uses',
    'current_uses',
    'valid_from',
    'valid_until',
    'applies_to_plan_id',
    'is_active',
    'created_by',
])]
class DiscountCode extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT => 'Porcentaje (%)',
        self::TYPE_FIXED => 'Monto fijo (COP)',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'applies_to_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    /**
     * Devuelve mensaje de error si el codigo no se puede usar, null si esta OK.
     */
    public function validateForPlan(?Plan $plan = null): ?string
    {
        if (! $this->is_active) {
            return 'Este codigo esta desactivado.';
        }
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return 'Este codigo aun no esta vigente.';
        }
        if ($this->valid_until && $this->valid_until->isPast()) {
            return 'Este codigo ya expiro.';
        }
        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return 'Este codigo ya alcanzo su limite de usos.';
        }
        if ($this->applies_to_plan_id && $plan && $this->applies_to_plan_id !== $plan->id) {
            return 'Este codigo no aplica al plan seleccionado.';
        }

        return null;
    }

    /**
     * Calcula el monto a descontar (en COP) sobre el precio base.
     */
    public function calculateDiscount(int $originalAmount): int
    {
        if ($this->type === self::TYPE_PERCENT) {
            return (int) round($originalAmount * min(100, $this->amount) / 100);
        }

        // Fixed: no puede ser mayor al precio
        return min($originalAmount, (int) $this->amount);
    }

    public function readableValue(): string
    {
        return $this->type === self::TYPE_PERCENT
            ? "{$this->amount}%"
            : '$'.number_format($this->amount, 0, ',', '.').' COP';
    }
}
