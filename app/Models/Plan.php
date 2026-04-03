<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'price_monthly',
    'price_yearly',
    'currency',
    'max_cases',
    'max_users',
    'max_storage_mb',
    'has_portal',
    'has_notifications',
    'is_active',
    'sort_order',
])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'has_portal' => 'boolean',
            'has_notifications' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getFormattedPriceMonthlyAttribute(): string
    {
        return '$'.number_format($this->price_monthly, 0, ',', '.').' COP';
    }
}
