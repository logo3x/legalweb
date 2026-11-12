<?php

namespace App\Filament\Resources\Concerns;

use App\Models\CasePermission;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Aplica los permisos por caso de los colaboradores (CasePermission) a un recurso
 * independiente cuyos registros pertenecen a un caso (legal_case_id), igual que
 * lo hacen las pestanas del caso con ChecksCasePermissions.
 *
 * Los administradores de la firma siempre tienen todos los permisos.
 */
trait ScopesToCasePermissions
{
    /**
     * Permisos que exige el recurso: ['view' => ..., 'create' => ..., 'edit' => ...].
     *
     * @return array{view: string, create: string, edit: string}
     */
    abstract protected static function casePermissions(): array;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->whereHas('legalCase', fn (Builder $q) => $q->where('firm_id', $user?->firm_id));

        if ($user && ! $user->isAdmin()) {
            $query->whereIn('legal_case_id', static::caseIdsWithPermission(static::casePermissions()['view']));
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || static::caseIdsWithPermission(static::casePermissions()['create']) !== []);
    }

    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->hasCasePermission((int) $record->legal_case_id, static::casePermissions()['edit']);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canDeleteAny();
    }

    public static function canRestoreAny(): bool
    {
        return static::canDeleteAny();
    }

    /**
     * Regla para el selector de caso: un colaborador solo puede elegir casos donde tiene
     * el permiso (o dejar el caso actual del registro que esta editando).
     */
    public static function caseSelectionRule(string $permission): Closure
    {
        return fn (?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record, $permission): void {
            $allowed = static::caseIdsWithPermission($permission);

            if ($allowed === null || in_array((int) $value, $allowed, true) || ($record && (int) $record->legal_case_id === (int) $value)) {
                return;
            }

            $fail('No tiene permiso para usar ese caso.');
        };
    }

    /**
     * Ids de los casos en los que el usuario actual tiene el permiso (todos si es admin).
     *
     * @return array<int, int>|null null para administradores (sin restriccion)
     */
    public static function caseIdsWithPermission(string $permission): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->isAdmin()) {
            return null;
        }

        return CasePermission::where('user_id', $user->id)
            ->get()
            ->filter(fn (CasePermission $cp): bool => $cp->hasPermission($permission))
            ->pluck('legal_case_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
