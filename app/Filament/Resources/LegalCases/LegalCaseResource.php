<?php

namespace App\Filament\Resources\LegalCases;

use App\Filament\Resources\LegalCases\Pages\CreateLegalCase;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\BillingRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\EventsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\FlowProgressRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\PortalAccessLogsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\TybaSyncLogsRelationManager;
use App\Filament\Resources\LegalCases\Schemas\LegalCaseForm;
use App\Filament\Resources\LegalCases\Tables\LegalCasesTable;
use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Services\CasePendingSummary;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LegalCaseResource extends Resource
{
    protected static ?string $model = LegalCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $modelLabel = 'Caso';

    protected static ?string $pluralModelLabel = 'Casos';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $globalSearchSort = 1;

    /**
     * Busqueda global (Ctrl+K): por numero interno, radicado, titulo o contraparte.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['case_number', 'external_case_number', 'title', 'opposing_party', 'client.first_name', 'client.last_name', 'client.document_number'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->case_number} · ".Str::limit((string) $record->title, 70);
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Cliente' => $record->client?->full_name,
            'Radicado' => $record->external_case_number,
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('client');
    }

    public static function form(Schema $schema): Schema
    {
        return LegalCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        // Dos columnas en pantallas grandes: la informacion del caso ocupa 2/3 y el resumen
        // con las partes queda a la derecha. En el celular todo se apila.
        return $schema
            ->components([
                // Lo primero que necesita el abogado: que corre, que viene y si la vigilancia funciona.
                Section::make('Lo pendiente')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                    ->schema([
                        TextEntry::make('pending_deadline')
                            ->label('Próximo término')
                            ->icon(Heroicon::OutlinedClock)
                            ->weight('medium')
                            ->state(fn (LegalCase $record): ?string => self::pending($record)['deadline']['step'] ?? null)
                            ->color(fn (LegalCase $record): ?string => (self::pending($record)['deadline']['remaining'] ?? 99) <= 3 ? 'danger' : null)
                            ->iconColor(fn (LegalCase $record): ?string => (self::pending($record)['deadline']['remaining'] ?? 99) <= 3 ? 'danger' : 'gray')
                            ->belowContent(fn (LegalCase $record): ?string => self::deadlineDetail(self::pending($record)['deadline']))
                            ->placeholder('Sin términos corriendo'),
                        TextEntry::make('pending_reminder')
                            ->label('Próxima cita')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->iconColor('gray')
                            ->weight('medium')
                            ->state(fn (LegalCase $record): ?string => self::pending($record)['reminder']?->title)
                            ->belowContent(fn (LegalCase $record): ?string => ($reminder = self::pending($record)['reminder'])
                                ? $reminder->due_date->format('d/m/Y').' · '.$reminder->due_date->diffForHumans()
                                : null)
                            ->placeholder('Nada agendado'),
                        TextEntry::make('pending_last_event')
                            ->label('Última actuación')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->iconColor('gray')
                            ->weight('medium')
                            ->state(fn (LegalCase $record): ?string => self::pending($record)['event']?->title)
                            ->belowContent(fn (LegalCase $record): ?string => ($event = self::pending($record)['event'])?->event_date
                                ? $event->event_date->format('d/m/Y').' · '.$event->event_date->diffForHumans()
                                : null)
                            ->placeholder('Aún sin actuaciones'),
                        // El silencio nunca significa "sin novedades": si la consulta fallo, se dice aqui.
                        TextEntry::make('pending_sync')
                            ->label(fn (LegalCase $record): string => $record->isSamai() ? 'Consulta a SAMAI' : 'Consulta a la Rama')
                            ->weight('medium')
                            ->state(fn (LegalCase $record): string => match (true) {
                                $record->sync_failed_at !== null => 'No pudimos consultarlo',
                                blank($record->external_case_number) => 'Sin radicado: no se vigila',
                                $record->last_tyba_sync === null => 'Aún no consultado',
                                default => 'Al día',
                            })
                            ->icon(fn (LegalCase $record): Heroicon => $record->sync_failed_at !== null ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedArrowPath)
                            ->iconColor(fn (LegalCase $record): string => $record->sync_failed_at !== null ? 'danger' : 'gray')
                            ->color(fn (LegalCase $record): ?string => $record->sync_failed_at !== null ? 'danger' : null)
                            ->belowContent(fn (LegalCase $record): ?string => match (true) {
                                $record->sync_failed_at !== null => "{$record->sync_error} (desde {$record->sync_failed_at->format('d/m/Y H:i')}). Revíselo directamente en ".($record->isSamai() ? 'SAMAI' : 'la Rama Judicial').'; reintentamos cada hora.',
                                $record->last_tyba_sync !== null => 'Última consulta exitosa '.$record->last_tyba_sync->diffForHumans(),
                                default => null,
                            }),
                    ]),
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make('Información del caso')
                                ->icon(Heroicon::OutlinedBriefcase)
                                ->columns(['default' => 1, 'sm' => 2])
                                ->schema([
                                    // La descripcion que genera la importacion repite los datos de la Rama:
                                    // en ese caso va dentro de "Datos de la Rama Judicial".
                                    TextEntry::make('description')
                                        ->label('Descripción')
                                        ->columnSpanFull()
                                        ->extraAttributes(['style' => 'white-space: pre-line'])
                                        ->visible(fn (LegalCase $record): bool => filled($record->description) && ! (self::isImportedDescription($record) && filled($record->tyba_data))),
                                    TextEntry::make('external_case_number')
                                        ->label('Radicado judicial')
                                        ->icon('heroicon-m-document-text')
                                        ->fontFamily('mono')
                                        ->copyable()
                                        ->copyMessage('Radicado copiado')
                                        ->placeholder('Sin radicado'),
                                    TextEntry::make('caseType.name')
                                        ->label('Tipo de proceso')
                                        ->badge(),
                                    TextEntry::make('court')
                                        ->label('Juzgado / Despacho')
                                        ->icon('heroicon-m-building-office')
                                        ->placeholder('Sin asignar'),
                                    TextEntry::make('judge')
                                        ->label('Juez')
                                        ->icon('heroicon-m-user')
                                        ->placeholder('Sin asignar'),
                                ]),
                            Section::make('Datos de la Rama Judicial')
                                ->icon(Heroicon::OutlinedBuildingLibrary)
                                ->columns(['default' => 1, 'sm' => 2, 'xl' => 3])
                                ->description('Datos de referencia tal como los publica el despacho.')
                                ->collapsible()
                                ->collapsed()
                                ->visible(fn ($record) => ! empty($record->tyba_data))
                                ->schema([
                                    TextEntry::make('tyba_data.codigo_proceso')
                                        ->label('Código del proceso'),
                                    TextEntry::make('tyba_data.tipo_proceso')
                                        ->label('Tipo de proceso'),
                                    TextEntry::make('tyba_data.clase_proceso')
                                        ->label('Clase de proceso'),
                                    TextEntry::make('tyba_data.subclase')
                                        ->label('Subclase')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.departamento')
                                        ->label('Departamento'),
                                    TextEntry::make('tyba_data.corporacion')
                                        ->label('Corporación')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.especialidad')
                                        ->label('Especialidad'),
                                    TextEntry::make('tyba_data.numero_despacho')
                                        ->label('No. Despacho')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.despacho')
                                        ->label('Despacho')
                                        ->columnSpanFull(),
                                    TextEntry::make('tyba_data.ponente')
                                        ->label('Ponente / Juez')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.fecha_publicacion')
                                        ->label('Fecha de publicación')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.fecha_ultima_actuacion')
                                        ->label('Última actuación')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.ubicacion')
                                        ->label('Sistema')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.recurso')
                                        ->label('Recurso')
                                        ->placeholder('-'),
                                    TextEntry::make('tyba_data.cod_despacho')
                                        ->label('Código del despacho')
                                        ->placeholder('-'),
                                    TextEntry::make('imported_description')
                                        ->label('Detalle importado')
                                        ->state(fn (LegalCase $record): ?string => $record->description)
                                        ->extraAttributes(['style' => 'white-space: pre-line'])
                                        ->visible(fn (LegalCase $record): bool => self::isImportedDescription($record))
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 2]),

                        Group::make([
                            Section::make('Resumen')
                                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('case_number')
                                        ->label('No. de caso')
                                        ->icon('heroicon-m-hashtag')
                                        ->weight('bold')
                                        ->copyable()
                                        ->columnSpanFull(),
                                    TextEntry::make('status')
                                        ->label('Estado')
                                        ->badge()
                                        ->color(fn (string $state): string => $state === 'en_espera' ? 'warning' : 'gray')
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'abierto' => 'Abierto',
                                            'en_progreso' => 'En progreso',
                                            'en_espera' => 'En espera',
                                            'cerrado' => 'Cerrado',
                                            'archivado' => 'Archivado',
                                            default => $state,
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
                                        })
                                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                                    TextEntry::make('started_at')
                                        ->label('Fecha de inicio')
                                        ->icon('heroicon-m-calendar')
                                        ->date('d/m/Y')
                                        ->placeholder('Sin definir'),
                                    TextEntry::make('closed_at')
                                        ->label('Fecha de cierre')
                                        ->icon('heroicon-m-calendar')
                                        ->date('d/m/Y')
                                        ->visible(fn (LegalCase $record): bool => $record->closed_at !== null),
                                ]),
                            Section::make('Partes')
                                ->icon(Heroicon::OutlinedUserGroup)
                                ->schema([
                                    TextEntry::make('client')
                                        ->label('Cliente')
                                        ->icon('heroicon-m-user')
                                        ->weight('medium')
                                        ->formatStateUsing(fn ($record) => "{$record->client->first_name} {$record->client->last_name}"),
                                    Grid::make(2)->schema([
                                        TextEntry::make('client.document_number')
                                            ->label('Documento')
                                            ->icon('heroicon-m-identification'),
                                        TextEntry::make('client.phone')
                                            ->label('Teléfono')
                                            ->icon('heroicon-m-phone')
                                            ->placeholder('Sin teléfono'),
                                    ]),
                                    TextEntry::make('user.name')
                                        ->label('Abogado responsable')
                                        ->icon('heroicon-m-academic-cap')
                                        ->helperText(fn ($record): ?string => $record->user?->email),
                                    TextEntry::make('opposing_party')
                                        ->label('Contraparte')
                                        ->icon('heroicon-m-user-group')
                                        ->placeholder('Sin contraparte'),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 1]),
                    ]),
            ]);
    }

    /**
     * Lo pendiente del caso, calculado una sola vez por peticion.
     *
     * @return array{deadline: array{step: string, deadline: Carbon, remaining: int}|null, reminder: Reminder|null, event: CaseEvent|null}
     */
    private static function pending(LegalCase $record): array
    {
        static $cache = new \WeakMap;

        return $cache[$record] ??= (function () use ($record): array {
            $summary = app(CasePendingSummary::class);

            return [
                'deadline' => $summary->nextDeadline($record),
                'reminder' => $summary->nextReminder($record),
                'event' => $summary->lastEvent($record),
            ];
        })();
    }

    /**
     * @param  array{step: string, deadline: Carbon, remaining: int}|null  $deadline
     */
    private static function deadlineDetail(?array $deadline): ?string
    {
        if ($deadline === null) {
            return null;
        }

        $remaining = $deadline['remaining'];
        $when = match (true) {
            $remaining < 0 => 'venció hace '.abs($remaining).' día(s) hábil(es)',
            $remaining === 0 => 'vence hoy',
            $remaining === 1 => 'vence mañana (1 día hábil)',
            default => "faltan {$remaining} días hábiles",
        };

        return 'Vence el '.$deadline['deadline']->format('d/m/Y').' · '.$when;
    }

    private static function isImportedDescription(LegalCase $record): bool
    {
        return str_starts_with((string) $record->description, 'Importado desde');
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

    /**
     * Colaboradores: solo editan los casos donde tienen el permiso "Editar caso".
     * Filament usa esta respuesta para canEdit(), el boton Editar y la pagina de edicion.
     */
    public static function getEditAuthorizationResponse(Model $record): Response
    {
        if (! auth()->user()?->hasCasePermission($record->getKey(), 'case.edit')) {
            return Response::deny('No tiene permiso para editar este caso.');
        }

        return parent::getEditAuthorizationResponse($record);
    }
}
