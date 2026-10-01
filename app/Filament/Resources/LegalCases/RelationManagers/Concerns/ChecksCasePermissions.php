<?php

namespace App\Filament\Resources\LegalCases\RelationManagers\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Aplica los permisos por caso de los colaboradores (CasePermission) a una pestana del caso.
 *
 * Cada RelationManager indica que permiso exige cada accion de Filament
 * (viewAny, view, create, update, delete, deleteAny...). Los administradores de la firma
 * siempre tienen todos los permisos (User::hasCasePermission).
 */
trait ChecksCasePermissions
{
    /**
     * Permiso de CasePermission::CASE_PERMISSIONS que exige la accion, o null si no exige ninguno.
     */
    abstract protected static function casePermissionFor(string $action): ?string;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! static::userHasCasePermission($ownerRecord, static::casePermissionFor('viewAny'))) {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (! static::userHasCasePermission($this->getOwnerRecord(), static::casePermissionFor($action))) {
            return Response::deny('No tiene permiso para esta accion en este caso.');
        }

        return parent::getAuthorizationResponse($action, $record);
    }

    /**
     * Para acciones personalizadas (Action::make), que Filament no autoriza por defecto.
     */
    protected function canOnCase(string $action): bool
    {
        return static::userHasCasePermission($this->getOwnerRecord(), static::casePermissionFor($action));
    }

    private static function userHasCasePermission(Model $case, ?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        return (bool) auth()->user()?->hasCasePermission($case->getKey(), $permission);
    }
}
