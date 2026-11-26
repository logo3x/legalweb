<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use App\Filament\Resources\LegalCases\RelationManagers\Concerns\ChecksCasePermissions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PortalAccessLogsRelationManager extends RelationManager
{
    use ChecksCasePermissions {
        canViewForRecord as checkedCanViewForRecord;
    }

    protected static function casePermissionFor(string $action): ?string
    {
        return match ($action) {
            'viewAny', 'view' => 'portal.share',
            default => 'portal.share',
        };
    }

    protected static string $relationship = 'portalAccessLogs';

    protected static ?string $title = 'Accesos al portal';

    /**
     * Solo aparece si el caso tiene o tuvo portal: en los demas casos es una pestana vacia mas.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord->portal_enabled && ! $ownerRecord->portalAccessLogs()->exists()) {
            return false;
        }

        return static::checkedCanViewForRecord($ownerRecord, $pageClass);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Registro de cada vez que un cliente accede al portal de su caso. Se registra la fecha, la IP y la accion realizada (visitar portal, aceptar terminos, consultar caso). Util para trazabilidad y cumplimiento del secreto profesional (Art. 74 CP).')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP'),
                TextColumn::make('action')
                    ->label('Accion')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accept_terms' => 'success',
                        'view' => 'info',
                        'landing' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'accept_terms' => 'Acepto terminos',
                        'view' => 'Consulto caso',
                        'landing' => 'Visito portal',
                        default => $state,
                    }),
                TextColumn::make('user_agent')
                    ->label('Dispositivo')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10]);
    }
}
