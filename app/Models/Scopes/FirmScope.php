<?php

namespace App\Models\Scopes;

use App\Models\CasePermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

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

        if ($user->isAdmin() || ! in_array($table, ['clients', 'legal_cases', 'reminders'])) {
            return;
        }

        // Colaboradores (no admin): solo lo relacionado con sus casos asignados.
        $allowedCaseIds = CasePermission::where('user_id', $user->id)->pluck('legal_case_id');

        if ($table === 'legal_cases') {
            $builder->whereIn('legal_cases.id', $allowedCaseIds);
        }

        // Clientes de sus casos asignados o que tiene asignados como abogado (incluye los que crea).
        if ($table === 'clients') {
            $builder->where(fn (Builder $q) => $q
                ->where('clients.user_id', $user->id)
                ->orWhereIn('clients.id', DB::table('legal_cases')
                    ->whereIn('id', $allowedCaseIds)
                    ->whereNotNull('client_id')
                    ->select('client_id')));
        }

        // Recordatorios propios o de sus casos asignados.
        if ($table === 'reminders') {
            $builder->where(fn (Builder $q) => $q
                ->where('reminders.user_id', $user->id)
                ->orWhereIn('reminders.legal_case_id', $allowedCaseIds));
        }
    }
}
