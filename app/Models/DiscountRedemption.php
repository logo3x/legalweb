<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'discount_code_id',
    'firm_id',
    'user_id',
    'plan_id',
    'original_amount',
    'discount_amount',
    'final_amount',
    'wompi_transaction_id',
    'redeemed_at',
])]
class DiscountRedemption extends Model
{
    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
        ];
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
