<?php

namespace App\Filament\Resources\LegalCases;

use App\Filament\Resources\LegalCases\Pages\CreateLegalCase;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\EventsRelationManager;
use App\Filament\Resources\LegalCases\Schemas\LegalCaseForm;
use App\Filament\Resources\LegalCases\Tables\LegalCasesTable;
use App\Models\LegalCase;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
                    ->columns(3)
                    ->schema([
                        TextEntry::make('case_number')
                            ->label('No. Caso'),
                        TextEntry::make('external_case_number')
                            ->label('Radicado Judicial')
                            ->default('-'),
                        TextEntry::make('caseType.name')
                            ->label('Tipo de Proceso')
                            ->badge(),
                        TextEntry::make('title')
                            ->label('Título')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
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
                            }),
                        TextEntry::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'baja' => 'gray',
                                'media' => 'info',
                                'alta' => 'warning',
                                'urgente' => 'danger',
                                default => 'gray',
                            }),
                    ]),
                Section::make('Partes Involucradas')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('client')
                            ->label('Cliente')
                            ->formatStateUsing(fn ($record) => "{$record->client->first_name} {$record->client->last_name}"),
                        TextEntry::make('client.document_number')
                            ->label('Documento del Cliente'),
                        TextEntry::make('client.phone')
                            ->label('Teléfono del Cliente')
                            ->default('-'),
                        TextEntry::make('user.name')
                            ->label('Abogado Responsable'),
                        TextEntry::make('opposing_party')
                            ->label('Contraparte')
                            ->default('-'),
                    ]),
                Section::make('Información Judicial')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('court')
                            ->label('Juzgado / Despacho')
                            ->default('-'),
                        TextEntry::make('judge')
                            ->label('Juez')
                            ->default('-'),
                        TextEntry::make('started_at')
                            ->label('Fecha de Inicio')
                            ->date('d/m/Y')
                            ->default('-'),
                        TextEntry::make('closed_at')
                            ->label('Fecha de Cierre')
                            ->date('d/m/Y')
                            ->default('-'),
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
            EventsRelationManager::class,
            DocumentsRelationManager::class,
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
