<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'event',
    'firm_id',
    'payload',
    'status',
    'attempts',
    'response_code',
    'error',
    'sent_at',
])]
class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pendiente';

    public const STATUS_SENT = 'enviado';

    public const STATUS_FAILED = 'fallido';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
