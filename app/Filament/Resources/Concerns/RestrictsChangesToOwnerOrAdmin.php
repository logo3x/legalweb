<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Un colaborador puede ver los registros de sus casos asignados, pero solo edita o borra
 * los que el mismo creo. Los administradores de la firma pueden modificarlos todos, y
 * solo ellos usan las acciones masivas, la papelera y el borrado definitivo.
 */
trait RestrictsChangesToOwnerOrAdmin
{
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || (int) $record->user_id === (int) $user->id);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canRestore(Model $record): bool
    {
        return (bool) auth()->user()?->isAdmin();
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
}
