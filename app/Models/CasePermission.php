<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'legal_case_id',
    'permissions',
    'assigned_by',
])]
class CasePermission extends Model
{
    public const CASE_PERMISSIONS = [
        'case.view' => 'Ver caso',
        'case.edit' => 'Editar caso',
        'events.view' => 'Ver actuaciones',
        'events.create' => 'Crear actuaciones',
        'events.edit' => 'Editar actuaciones',
        'documents.view' => 'Ver documentos',
        'documents.upload' => 'Subir documentos',
        'flow.view' => 'Ver flujo de proceso',
        'flow.manage' => 'Gestionar flujo (completar pasos)',
        'portal.share' => 'Compartir portal con cliente',
        'ai.use' => 'Usar asistente IA',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
