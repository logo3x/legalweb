<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'category',
    'subject',
    'body',
    'is_active',
])]
class MassEmailTemplate extends Model
{
    public const CATEGORIES = [
        'onboarding' => 'Bienvenida / Onboarding',
        'retencion' => 'Retencion',
        'marketing' => 'Marketing / Promociones',
        'encuesta' => 'Encuestas',
        'novedades' => 'Novedades y anuncios',
        'reactivacion' => 'Reactivacion',
        'general' => 'General',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
