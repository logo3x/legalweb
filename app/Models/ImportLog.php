<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'firm_id',
        'user_id',
        'total_radicados',
        'importados',
        'duplicados',
        'no_encontrados',
        'errores',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
