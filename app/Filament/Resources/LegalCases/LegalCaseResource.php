<?php

namespace App\Filament\Resources\LegalCases;

use App\Filament\Resources\LegalCases\Pages\CreateLegalCase;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\BillingRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\DocumentRequirementsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\EventsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\FlowProgressRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\PortalAccessLogsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\TybaSyncLogsRelationManager;
use App\Filament\Resources\LegalCases\Schemas\LegalCaseForm;
use App\Filament\Resources\LegalCases\Tables\LegalCasesTable;
use App\Models\LegalCase;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LegalCaseResource extends Resource
{
    protected static ?string $model = LegalCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $modelLabel = 'Caso';

    protected static ?string $pluralModelLabel = 'Casos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return LegalCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Caso')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('case_number')
                            ->label('No. Caso')
                            ->icon('heroicon-m-hashtag')
                            ->weight('bold')
                            ->size(TextSize::Large),
                        TextEntry::make('external_case_number')
                            ->label('Radicado Judicial')
                            ->icon('heroicon-m-document-text')
                            ->placeholder('Sin radicado'),
                        TextEntry::make('caseType.name')
                            ->label('Tipo de Proceso')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'abierto' => 'info',
                                'en_progreso' => 'warning',
                                'en_espera' => 'gray',
                                'cerrado' => 'success',
                                'archivado' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'abierto' => 'Abierto',
                                'en_progreso' => 'En Progreso',
                                'en_espera' => 'En Espera',
                                'cerrado' => 'Cerrado',
                                'archivado' => 'Archivado',
                                default => $state,
                            }),
                        TextEntry::make('title')
                            ->label('Título')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull()
                            ->placeholder('Sin descripción'),
                        TextEntry::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'baja' => 'gray',
                                'media' => 'info',
                                'alta' => 'warning',
                                'urgente' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        TextEntry::make('court')
                            ->label('Juzgado / Despacho')
                            ->icon('heroicon-m-building-office')
                            ->placeholder('Sin asignar'),
                        TextEntry::make('judge')
                            ->label('Juez')
                            ->icon('heroicon-m-user')
                            ->placeholder('Sin asignar'),
                        TextEntry::make('started_at')
                            ->label('Fecha de Inicio')
                            ->icon('heroicon-m-calendar')
                            ->date('d/m/Y')
                            ->placeholder('Sin definir'),
                        TextEntry::make('closed_at')
                            ->label('Fecha de Cierre')
                            ->icon('heroicon-m-calendar')
                            ->date('d/m/Y')
                            ->placeholder('Caso abierto'),
                        TextEntry::make('last_tyba_sync')
                            ->label('Ultima sincronizacion')
                            ->icon('heroicon-m-arrow-path')
                            ->since()
                            ->placeholder('Nunca sincronizado')
                            ->visible(fn ($record) => (bool) $record->external_case_number),
                    ]),
                Section::make('Datos Rama Judicial')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columns(4)
                    ->visible(fn ($record) => ! empty($record->tyba_data))
                    ->schema([
                        TextEntry::make('tyba_data.codigo_proceso')
                            ->label('Codigo Proceso'),
                        TextEntry::make('tyba_data.tipo_proceso')
                            ->label('Tipo Proceso'),
                        TextEntry::make('tyba_data.clase_proceso')
                            ->label('Clase Proceso'),
                        TextEntry::make('tyba_data.subclase')
                            ->label('Subclase')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.departamento')
                            ->label('Departamento'),
                        TextEntry::make('tyba_data.corporacion')
                            ->label('Corporacion')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.especialidad')
                            ->label('Especialidad'),
                        TextEntry::make('tyba_data.numero_despacho')
                            ->label('No. Despacho')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.despacho')
                            ->label('Despacho')
                            ->columnSpan(2),
                        TextEntry::make('tyba_data.ponente')
                            ->label('Ponente / Juez')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.fecha_publicacion')
                            ->label('Fecha Publicacion')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.fecha_ultima_actuacion')
                            ->label('Ultima Actuacion')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.ubicacion')
                            ->label('Sistema')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.recurso')
                            ->label('Recurso')
                            ->placeholder('-'),
                        TextEntry::make('tyba_data.cod_despacho')
                            ->label('Cod. Despacho')
                            ->placeholder('-'),
                    ]),
                Section::make('Partes')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('client')
                            ->label('Cliente')
                            ->icon('heroicon-m-user')
                            ->formatStateUsing(fn ($record) => "{$record->client->first_name} {$record->client->last_name}"),
                        TextEntry::make('client.document_number')
                            ->label('Documento')
                            ->icon('heroicon-m-identification'),
                        TextEntry::make('client.phone')
                            ->label('Teléfono')
                            ->icon('heroicon-m-phone')
                            ->placeholder('Sin teléfono'),
                        TextEntry::make('user.name')
                            ->label('Abogado Responsable')
                            ->icon('heroicon-m-academic-cap'),
                        TextEntry::make('user.email')
                            ->label('Correo del Abogado')
                            ->icon('heroicon-m-envelope'),
                        TextEntry::make('opposing_party')
                            ->label('Contraparte')
                            ->icon('heroicon-m-user-group')
                            ->placeholder('Sin contraparte'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return LegalCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FlowProgressRelationManager::class,
            EventsRelationManager::class,
            DocumentRequirementsRelationManager::class,
            DocumentsRelationManager::class,
            BillingRelationManager::class,
            TybaSyncLogsRelationManager::class,
            PortalAccessLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalCases::route('/'),
            'create' => CreateLegalCase::route('/create'),
            'view' => ViewLegalCase::route('/{record}'),
            'edit' => EditLegalCase::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
