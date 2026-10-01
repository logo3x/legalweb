<?php

namespace App\Models\Scopes;

use App\Models\CasePermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FirmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // Sin sesion (consola, colas, webhooks) no se filtra.
        if (! $user) {
            return;
        }

        // Un usuario autenticado sin firma no debe ver datos de ninguna firma.
        if (! $user->firm_id) {
            $builder->whereRaw('1 = 0');

            return;
        }

        // Superadmin tambien se filtra por su firma
        // Solo puede ver datos de su propia firma, no de otras

        $table = $model->getTable();

        if (in_array($table, ['clients', 'legal_cases', 'reminders'])) {
            $builder->where($table.'.firm_id', $user->firm_id);
        }

        // Colaboradores (no admin): solo ven casos asignados
        if ($table === 'legal_cases' && ! $user->isAdmin()) {
            $allowedCaseIds = CasePermission::where('user_id', $user->id)->pluck('legal_case_id');
            $builder->whereIn($table.'.id', $allowedCaseIds);
        }
    }
}
