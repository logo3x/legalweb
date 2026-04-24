<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentRequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentRequirements';

    protected static ?string $title = 'Documentos Requeridos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del documento')
                    ->required()
                    ->placeholder('Ej: Cedula de ciudadania, Certificado laboral, Poder')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->rows(2)
                    ->placeholder('Detalles adicionales del documento')
                    ->columnSpanFull(),
                Select::make('responsible')
                    ->label('Quien debe conseguirlo')
                    ->options([
                        'cliente' => 'Cliente',
                        'abogado' => 'Abogado',
                        'firma' => 'Firma',
                        'contraparte' => 'Contraparte',
                        'juzgado' => 'Juzgado',
                        'otro' => 'Otro',
                    ])
                    ->default('cliente')
                    ->required(),
                Select::make('assigned_to')
                    ->label('Asignado a (opcional)')
                    ->relationship('assignedUser', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->searchable()
                    ->preload(),
                TextInput::make('entity')
                    ->label('Entidad donde se consigue')
                    ->placeholder('Ej: Notaria, Registraduria, EPS'),
                TextInput::make('estimated_cost')
                    ->label('Valor aproximado ($)')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('0'),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                    ])
                    ->default('pendiente')
                    ->required()
                    ->live(),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ])
                    ->default('media')
                    ->required(),
                DatePicker::make('due_date')
                    ->label('Fecha limite')
                    ->placeholder('Cuando se necesita'),
                DatePicker::make('received_at')
                    ->label('Fecha de recepcion')
                    ->visible(fn ($get) => $get('status') === 'recibido'),
                TextInput::make('external_url')
                    ->label('Enlace al archivo (Drive, etc)')
                    ->url()
                    ->placeholder('https://drive.google.com/...')
                    ->columnSpanFull()
                    ->helperText('Si el documento esta en la nube (Google Drive, Dropbox, etc), pegue el enlace aqui.'),
                Textarea::make('notes')
                    ->label('Notas adicionales')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Lista de documentos necesarios para el caso. Registre que se necesita, quien debe conseguirlo, donde se consigue, valor aproximado y estado. Agregue el enlace al archivo si lo guarda en la nube.')
            ->columns([
                TextColumn::make('name')
                    ->label('Documento')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cliente' => 'info',
                        'abogado' => 'success',
                        'firma' => 'primary',
                        'contraparte' => 'danger',
                        'juzgado' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('entity')
                    ->label('Entidad')
                    ->placeholder('-')
                    ->limit(25),
                TextColumn::make('estimated_cost')
                    ->label('Valor aprox.')
                    ->money('COP', locale: 'es_CO')
                    ->placeholder('-')
                    ->summarize(Sum::make()->money('COP', locale: 'es_CO')->label('Total')),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'gray',
                        'solicitado' => 'info',
                        'en_tramite' => 'warning',
                        'recibido' => 'success',
                        'no_aplica' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                        default => $state,
                    }),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'media' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('due_date')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && $record->status !== 'recibido' ? 'danger' : null),
                TextColumn::make('external_url')
                    ->label('Archivo')
                    ->formatStateUsing(fn ($state) => $state ? 'Ver' : null)
                    ->url(fn ($record) => $record->external_url)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon(fn ($state) => $state ? 'heroicon-o-link' : null)
                    ->placeholder('-'),
                TextColumn::make('assignedUser.name')
                    ->label('Asignado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                    ]),
                SelectFilter::make('responsible')
                    ->label('Responsable')
                    ->options([
                        'cliente' => 'Cliente',
                        'abogado' => 'Abogado',
                        'firma' => 'Firma',
                        'contraparte' => 'Contraparte',
                        'juzgado' => 'Juzgado',
                        'otro' => 'Otro',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar documento'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
