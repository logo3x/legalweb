<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';

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
                    ->placeholder('Detalles adicionales')
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
                    ->label('Enlace al archivo (RECOMENDADO)')
                    ->url()
                    ->placeholder('https://drive.google.com/... o https://1drv.ms/...')
                    ->columnSpanFull()
                    ->helperText('Recomendado: guarde el archivo en su Google Drive, OneDrive o Dropbox y pegue el enlace aqui. Asi mantiene el control y la privacidad de su informacion.'),
                FileUpload::make('file_path')
                    ->label('Subir archivo (alternativa)')
                    ->disk('public')
                    ->directory('documents')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull()
                    ->helperText('Solo si no puede usar la nube. Max 10 MB. Recuerde: usted es responsable de la informacion subida.'),
                Textarea::make('notes')
                    ->label('Notas adicionales')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('Lista de documentos del caso. Los documentos que el cliente debe aportar aparecen en amarillo. Cuando el cliente confirma desde el portal, recibira notificacion en la campanita.')
            ->columns([
                TextColumn::make('name')
                    ->label('Documento')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description)
                    ->weight(fn ($record) => $record->responsible === 'cliente' && in_array($record->status, ['pendiente', 'solicitado', 'en_tramite']) ? 'bold' : null)
                    ->icon(fn ($record) => $record->responsible === 'cliente' && in_array($record->status, ['pendiente', 'solicitado', 'en_tramite']) ? 'heroicon-s-exclamation-circle' : null)
                    ->iconColor('warning'),
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'cliente' => 'info',
                        'abogado' => 'success',
                        'firma' => 'primary',
                        'contraparte' => 'danger',
                        'juzgado' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '-'),
                TextColumn::make('entity')
                    ->label('Entidad')
                    ->placeholder('-')
                    ->limit(25)
                    ->toggleable(),
                TextColumn::make('estimated_cost')
                    ->label('Valor aprox.')
                    ->money('COP', locale: 'es_CO')
                    ->placeholder('-')
                    ->toggleable()
                    ->summarize(Sum::make()->money('COP', locale: 'es_CO')->label('Total')),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pendiente' => 'gray',
                        'solicitado' => 'info',
                        'en_tramite' => 'warning',
                        'recibido' => 'success',
                        'no_aplica' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                        default => '-',
                    }),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
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
                    ->toggleable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && $record->status !== 'recibido' ? 'danger' : null),
                TextColumn::make('file_path')
                    ->label('Archivo')
                    ->formatStateUsing(fn ($state, $record) => $state ? 'Descargar' : ($record->external_url ? 'Ver enlace' : null))
                    ->url(fn ($record) => $record->file_path
                        ? asset('storage/'.$record->file_path)
                        : $record->external_url)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon(fn ($state, $record) => ($state || $record->external_url) ? 'heroicon-o-document' : null)
                    ->placeholder('-'),
                TextColumn::make('assignedUser.name')
                    ->label('Asignado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('uploader.name')
                    ->label('Subido por')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label('Agregar documento')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! empty($data['file_path'])) {
                            $data['uploaded_by'] = auth()->id();

                            $fullPath = storage_path('app/public/'.$data['file_path']);
                            if (file_exists($fullPath)) {
                                $data['file_size'] = filesize($fullPath);
                                $data['file_type'] = pathinfo($data['file_path'], PATHINFO_EXTENSION);
                            }

                            if (($data['status'] ?? '') === 'pendiente') {
                                $data['status'] = 'recibido';
                                $data['received_at'] = now();
                            }
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn ($record) => ! empty($record->file_path))
                    ->action(fn ($record) => Storage::disk('public')->download($record->file_path, $record->name)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
