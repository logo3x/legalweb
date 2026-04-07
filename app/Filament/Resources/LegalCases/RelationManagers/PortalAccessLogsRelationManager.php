<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortalAccessLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'portalAccessLogs';

    protected static ?string $title = 'Accesos al Portal';

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
