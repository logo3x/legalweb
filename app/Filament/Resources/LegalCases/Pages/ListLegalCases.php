<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\Client;
use App\Models\ImportLog;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CaseImporter;
use App\Services\CaseSpreadsheetImporter;
use App\Services\UsageLimiter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListLegalCases extends ListRecords
{
    protected static string $resource = LegalCaseResource::class;

    /**
     * Pestanas de acceso rapido sobre la lista de casos.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $active = ['abierto', 'en_progreso', 'en_espera'];

        return [
            'todos' => Tab::make('Todos'),
            'activos' => Tab::make('Activos')
                ->icon('heroicon-o-briefcase')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $active)),
            'novedades' => Tab::make('Con novedades')
                ->icon('heroicon-o-bell-alert')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('events', fn (Builder $q) => $q
                    ->where('event_type', 'actuacion')
                    ->where('created_at', '>=', now()->subDays(7)))),
            'sin_movimiento' => Tab::make('Sin movimiento (60 días)')
                ->icon('heroicon-o-pause-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereIn('status', $active)
                    ->where('created_at', '<', now()->subDays(60))
                    ->whereDoesntHave('events', fn (Builder $q) => $q->where('event_date', '>=', now()->subDays(60)))),
            'cerrados' => Tab::make('Cerrados y archivados')
                ->icon('heroicon-o-archive-box')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['cerrado', 'archivado'])),
        ];
    }

    public array $importResults = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_tyba')
                ->label('Importar proceso')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalWidth('xl')
                ->modalHeading('Importar Proceso desde Rama Judicial')
                ->modalDescription('Ingrese el radicado judicial. Se busca primero en la Rama Judicial (con actuaciones) y, si no esta, en SAMAI para procesos contencioso-administrativos.')
                ->modalSubmitActionLabel('Importar Proceso')
                ->form([
                    TextInput::make('radicado')
                        ->label('Codigo de Proceso (Radicado)')
                        ->required()
                        ->minLength(20)
                        ->maxLength(50)
                        ->placeholder('Ej: 68081310300120240001800')
                        ->helperText('23 digitos del radicado asignado por la Rama Judicial.')
                        ->validationMessages([
                            'required' => 'Debe ingresar el numero de radicado.',
                            'min_length' => 'El radicado debe tener al menos 20 digitos.',
                        ]),
                    Select::make('client_id')
                        ->label('Cliente')
                        ->options(fn () => Client::where('firm_id', auth()->user()->firm_id)
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->first_name} {$c->last_name}"]))
                        ->required()
                        ->validationMessages(['required' => 'Debe seleccionar un cliente.'])
                        ->searchable(),
                    Select::make('user_id')
                        ->label('Abogado Responsable')
                        ->options(fn () => User::where('firm_id', auth()->user()->firm_id)->pluck('name', 'id'))
                        ->default(fn () => auth()->id())
                        ->required()
                        ->validationMessages(['required' => 'Debe seleccionar un abogado responsable.']),
                ])
                ->action(function (array $data) {
                    if (! $this->ensureCaseSlots() || ! UsageLimiter::attempt('judicial')) {
                        return;
                    }

                    $radicado = preg_replace('/[^0-9]/', '', $data['radicado']);
                    $result = $this->importSingleRadicado($radicado, $data['client_id'], $data['user_id']);

                    if ($result['error']) {
                        Notification::make()->title($result['error'])->body($result['detail'] ?? '')->warning()->send();

                        return;
                    }

                    Notification::make()
                        ->title($result['imported'] ? 'Importacion exitosa' : 'Caso creado')
                        ->body($result['message'])
                        ->color($result['imported'] ? 'success' : 'warning')
                        ->persistent()
                        ->send();

                    $this->redirect(LegalCaseResource::getUrl('view', ['record' => $result['case']]));
                }),

            CreateAction::make()
                ->label('Crear caso')
                ->color('gray'),

            ActionGroup::make([
                Action::make('import_excel')
                    ->label('Importar desde Excel')
                    ->icon('heroicon-o-table-cells')
                    ->modalWidth('xl')
                    ->modalHeading('Importar casos desde Excel')
                    ->modalDescription('Suba su lista de procesos en Excel (.xlsx) o CSV. Creamos los clientes que no existan (por documento) y los casos. Si la fila trae radicado, las actuaciones llegan en la próxima sincronización.')
                    ->modalSubmitActionLabel('Importar')
                    ->visible(fn (): bool => LegalCaseResource::canCreate())
                    ->schema([
                        Actions::make([
                            Action::make('download_template')
                                ->label('Descargar plantilla de Excel')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->link()
                                ->action(fn (): BinaryFileResponse => $this->downloadImportTemplate()),
                        ]),
                        FileUpload::make('file')
                            ->label('Archivo')
                            ->disk('local')
                            ->directory('imports')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Columnas mínimas: "Documento del cliente" y "Titulo" o "Radicado". Hasta '.CaseSpreadsheetImporter::MAX_ROWS.' filas por archivo.'),
                    ])
                    ->action(fn (array $data) => $this->importSpreadsheet($data['file'])),

                Action::make('import_masivo')
                    ->label('Importación masiva (radicados)')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->color('gray')
                    ->modalWidth('xl')
                    ->modalHeading('Importacion Masiva de Procesos')
                    ->modalDescription('Pegue una lista de radicados (uno por linea). Se importaran todos los procesos encontrados en la Rama Judicial.')
                    ->modalSubmitActionLabel('Importar Todos')
                    ->extraModalFooterActions([])
                    ->form([
                        Textarea::make('radicados')
                            ->label('Radicados (uno por linea)')
                            ->required()
                            ->rows(8)
                            ->placeholder("68081310300120240001800\n05001310500120230012300\n11001310304120220045600")
                            ->helperText('Puede pegar hasta 20 radicados a la vez. Cada uno en una linea separada. El proceso puede tomar entre 5 y 30 segundos por radicado.'),
                        Select::make('client_id')
                            ->label('Cliente (para todos los casos)')
                            ->options(fn () => Client::where('firm_id', auth()->user()->firm_id)
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->first_name} {$c->last_name}"]))
                            ->required()
                            ->validationMessages(['required' => 'Debe seleccionar un cliente para asociar los casos.'])
                            ->searchable(),
                        Select::make('user_id')
                            ->label('Abogado Responsable')
                            ->options(fn () => User::where('firm_id', auth()->user()->firm_id)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->required()
                            ->validationMessages(['required' => 'Debe seleccionar un abogado responsable.']),
                    ])
                    ->action(function (array $data) {
                        if (! $this->ensureCaseSlots() || ! UsageLimiter::attempt('judicial')) {
                            return;
                        }

                        $lines = preg_split('/[\r\n]+/', trim($data['radicados']));
                        $radicados = collect($lines)
                            ->map(fn ($l) => preg_replace('/[^0-9]/', '', trim($l)))
                            ->filter(fn ($r) => strlen($r) >= 20)
                            ->unique()
                            ->take($this->importableCount(20))
                            ->values();

                        if ($radicados->isEmpty()) {
                            Notification::make()->title('Sin radicados validos')->body('No se encontraron radicados validos (minimo 20 digitos).')->warning()->send();

                            return;
                        }

                        $results = [];

                        foreach ($radicados as $radicado) {
                            $result = $this->importSingleRadicado($radicado, $data['client_id'], $data['user_id']);

                            if ($result['error']) {
                                $results[] = [
                                    'radicado' => $radicado,
                                    'status' => str_contains($result['error'], 'ya existe') ? 'duplicado' : 'error',
                                    'msg' => $result['error'],
                                    'case_number' => null,
                                ];
                            } elseif (! $result['imported']) {
                                $results[] = [
                                    'radicado' => $radicado,
                                    'status' => 'no_encontrado',
                                    'msg' => 'No se encontro en la Rama Judicial ni en SAMAI. No se creo caso.',
                                    'case_number' => null,
                                ];
                            } else {
                                $results[] = [
                                    'radicado' => $radicado,
                                    'status' => 'ok',
                                    'msg' => $result['message'],
                                    'case_number' => $result['case']->case_number,
                                    'case_id' => $result['case']->id,
                                ];
                            }
                        }

                        // Guardar log persistente
                        $imported = collect($results)->where('status', 'ok')->count();
                        $duplicados = collect($results)->where('status', 'duplicado')->count();
                        $noEncontrados = collect($results)->where('status', 'no_encontrado')->count();
                        $errores = collect($results)->where('status', 'error')->count();

                        ImportLog::create([
                            'firm_id' => auth()->user()->firm_id,
                            'user_id' => auth()->id(),
                            'total_radicados' => count($results),
                            'importados' => $imported,
                            'duplicados' => $duplicados,
                            'no_encontrados' => $noEncontrados,
                            'errores' => $errores,
                            'detalle' => $results,
                        ]);

                        $this->importResults = $results;

                        Notification::make()
                            ->title("Importacion completada: {$imported} de ".count($results))
                            ->body('Vea el detalle en "Historial de Importaciones".')
                            ->color($imported > 0 ? 'success' : 'warning')
                            ->persistent()
                            ->send();
                    }),

                Action::make('import_results')
                    ->label('Ver última importación')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('success')
                    ->modalWidth('2xl')
                    ->modalHeading('Resultado de Ultima Importacion')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn () => view('filament.modals.import-results', [
                        'results' => $this->importResults,
                    ]))
                    ->visible(fn () => ! empty($this->importResults)),

                Action::make('import_history')
                    ->label('Historial de importaciones')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalWidth('2xl')
                    ->modalHeading('Historial de Importaciones Masivas')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function () {
                        $logs = ImportLog::where('firm_id', auth()->user()->firm_id)
                            // Los colaboradores solo ven sus propias importaciones.
                            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('user_id', auth()->id()))
                            ->orderByDesc('created_at')
                            ->limit(10)
                            ->get();

                        return view('filament.modals.import-history', ['logs' => $logs]);
                    }),
            ])
                ->label('Más opciones')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }

    /**
     * Crea casos y clientes a partir del Excel o CSV subido y deja el resultado en el historial.
     */
    private function importSpreadsheet(string $storedPath): void
    {
        $disk = Storage::disk('local');
        $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        try {
            if (! in_array($extension, ['xlsx', 'csv', 'txt'], true)) {
                throw new \InvalidArgumentException('Use un archivo .xlsx o .csv. Si tiene un .xls antiguo, ábralo en Excel y guárdelo como .xlsx.');
            }

            $summary = (new CaseSpreadsheetImporter(auth()->user()))->import($disk->path($storedPath), $extension);
        } catch (\InvalidArgumentException $exception) {
            Notification::make()->title('No se pudo leer el archivo')->body($exception->getMessage())->warning()->persistent()->send();

            return;
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()->title('No se pudo leer el archivo')->body('Verifique que sea un Excel (.xlsx) o CSV válido.')->danger()->send();

            return;
        } finally {
            $disk->delete($storedPath);
        }

        ImportLog::create([
            'firm_id' => auth()->user()->firm_id,
            'user_id' => auth()->id(),
            'total_radicados' => $summary['rows'],
            'importados' => $summary['created'],
            'duplicados' => $summary['duplicates'],
            'no_encontrados' => 0,
            'errores' => $summary['errors'],
            'detalle' => $summary['results'],
        ]);

        $this->importResults = $summary['results'];

        Notification::make()
            ->title("Importación desde Excel: {$summary['created']} de {$summary['rows']} casos creados")
            ->body("Clientes nuevos: {$summary['clients_created']}. Ya existían: {$summary['duplicates']}. Con error: {$summary['errors']}. Vea el detalle en Más opciones > Ver última importación.")
            ->color($summary['created'] > 0 ? 'success' : 'warning')
            ->persistent()
            ->send();
    }

    private function downloadImportTemplate(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'lw-plantilla');
        CaseSpreadsheetImporter::writeTemplate($path);

        return response()->download($path, 'plantilla-casos-legalweb.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Avisa si el plan ya no permite mas casos activos.
     */
    private function ensureCaseSlots(): bool
    {
        $message = (new CaseImporter(auth()->user()))->caseLimitMessage();

        if ($message === null) {
            return true;
        }

        Notification::make()
            ->title('Alcanzo el limite de casos activos')
            ->body($message)
            ->warning()
            ->persistent()
            ->send();

        return false;
    }

    /**
     * Cuantos radicados se pueden importar sin pasar el limite de casos activos.
     */
    private function importableCount(int $max): int
    {
        $limits = auth()->user()->firm?->planLimits();
        $allowed = $limits?->maxActiveCases();

        return $allowed === null ? $max : max(0, min($max, $allowed - $limits->activeCasesCount()));
    }

    /**
     * @return array{error: ?string, detail: ?string, imported: bool, message: ?string, case: ?LegalCase}
     */
    private function importSingleRadicado(string $radicado, int $clientId, int $userId): array
    {
        return (new CaseImporter(auth()->user()))->import($radicado, $clientId, $userId);
    }
}
