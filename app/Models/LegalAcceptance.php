<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'acceptor_type',
    'acceptor_name',
    'acceptor_email',
    'document_type',
    'ip_address',
    'user_agent',
    'user_id',
    'legal_case_id',
])]
class LegalAcceptance extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}
