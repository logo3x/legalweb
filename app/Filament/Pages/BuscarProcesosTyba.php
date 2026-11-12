<?php

namespace App\Filament\Pages;

use App\Models\LegalCase;
use App\Services\SamaiService;
use App\Services\TybaService;
use App\Services\UsageLimiter;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class BuscarProcesosTyba extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.buscar-procesos-tyba';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Buscar procesos';

    protected static string|UnitEnum|null $navigationGroup = 'Seguimiento';

    protected static ?string $title = 'Buscar procesos en Rama Judicial y SAMAI';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public ?string $searchedTerm = null;

    public string $searchedMode = 'nombre';

    public string $searchedSource = 'rama';

    public array $procesos = [];

    public array $existingRadicados = [];

    public bool $hasSearched = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $nombre = request()->query('nombre');
        $radicado = request()->query('radicado');
        $documento = request()->query('documento');
        $tipoDoc = request()->query('tipo_documento');

        $initial = [
            'fuente' => 'rama',
            'corporacion' => '',
            'tipo_parte' => '',
            'modo' => 'nombre',
            'nombre' => '',
            'radicado' => '',
            'documento' => '',
            'tipo_documento' => $tipoDoc ?: '1',
        ];

        if (is_string($radicado) && trim($radicado) !== '') {
            $initial['modo'] = 'radicado';
            $initial['radicado'] = trim($radicado);
        } elseif (is_string($documento) && trim($documento) !== '') {
            $initial['modo'] = 'documento';
            $initial['documento'] = trim($documento);
        } elseif (is_string($nombre) && trim($nombre) !== '') {
            $initial['nombre'] = trim($nombre);
        }

        $this->form->fill($initial);

        $hasInitialSearch = $initial['nombre'] !== '' || $initial['radicado'] !== '' || $initial['documento'] !== '';

        if ($hasInitialSearch && ! UsageLimiter::attempt('judicial')) {
            return;
        }

        if ($initial['nombre'] !== '' && strlen($initial['nombre']) >= 3) {
            $this->ejecutarBusqueda('nombre', $initial['nombre']);
        } elseif ($initial['radicado'] !== '') {
            $this->ejecutarBusqueda('radicado', $initial['radicado']);
        } elseif ($initial['documento'] !== '') {
            $this->ejecutarBusqueda('documento', $initial['documento'], (int) $initial['tipo_documento']);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('fuente')
                    ->label('Fuente')
                    ->options([
                        'rama' => 'Rama Judicial (civil, laboral, penal, familia...)',
                        'samai' => 'SAMAI - Consejo de Estado (contencioso administrativo)',
                    ])
                    ->default('rama')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get): void {
                        if ($state === 'samai' && $get('modo') === 'documento') {
                            $set('modo', 'nombre');
                        }
                    })
                    ->columnSpanFull(),

                Select::make('modo')
                    ->label('Buscar por')
                    ->options(fn ($get): array => $get('fuente') === 'samai'
                        ? ['nombre' => 'Nombre de la parte', 'radicado' => 'Numero de radicado']
                        : [
                            'nombre' => 'Nombre / Razon social',
                            'radicado' => 'Numero de radicado',
                            'documento' => 'Cedula o NIT',
                        ])
                    ->default('nombre')
                    ->native(false)
                    ->live()
                    ->columnSpanFull(),

                TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->placeholder('Ej: GARCIA LOPEZ MARIA')
                    ->helperText('Apellidos primero. Busca directamente en la Rama Judicial.')
                    ->minLength(3)
                    ->maxLength(150)
                    ->visible(fn ($get) => $get('modo') === 'nombre')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->components([
                        Select::make('corporacion')
                            ->label('Corporacion o juzgado')
                            ->options(fn (): array => app(SamaiService::class)->corporations())
                            ->searchable()
                            ->required(fn ($get): bool => $get('fuente') === 'samai' && $get('modo') === 'nombre')
                            ->helperText('SAMAI exige elegir donde buscar.'),
                        Select::make('tipo_parte')
                            ->label('Rol de la parte')
                            ->options([
                                '' => 'Cualquiera',
                                'demandante' => 'Demandante o titular',
                                'demandado' => 'Demandado',
                                'apoderado' => 'Apoderado',
                            ])
                            ->native(false),
                    ])
                    ->visible(fn ($get) => $get('fuente') === 'samai' && $get('modo') === 'nombre')
                    ->columnSpanFull(),

                TextInput::make('radicado')
                    ->label('Numero de radicado (23 digitos)')
                    ->placeholder('Ej: 11001310300120240015800')
                    ->helperText('Traemos el expediente completo de la Rama.')
                    ->minLength(20)
                    ->maxLength(30)
                    ->visible(fn ($get) => $get('modo') === 'radicado')
                    ->columnSpanFull(),

                Grid::make(3)
                    ->components([
                        Select::make('tipo_documento')
                            ->label('Tipo')
                            ->options([
                                '1' => 'Cedula ciudadania',
                                '2' => 'Cedula extranjeria',
                                '3' => 'NIT',
                                '4' => 'Pasaporte',
                                '5' => 'Tarjeta identidad',
                            ])
                            ->default('1')
                            ->native(false)
                            ->columnSpan(1),
                        TextInput::make('documento')
                            ->label('Numero')
                            ->placeholder('Ej: 1098765432 o 900123456')
                            ->helperText('Sin puntos ni guiones.')
                            ->minLength(5)
                            ->maxLength(20)
                            ->columnSpan(2),
                    ])
                    ->visible(fn ($get) => $get('modo') === 'documento')
                    ->columnSpanFull(),
            ]);
    }

    public function buscar(): void
    {
        $data = $this->form->getState();

        if (! UsageLimiter::attempt('judicial')) {
            return;
        }

        $modo = $data['modo'] ?? 'nombre';
        $term = trim((string) ($data[$modo] ?? ''));
        $tipo = (int) ($data['tipo_documento'] ?? 1);

        if (($data['fuente'] ?? 'rama') === 'samai') {
            $this->ejecutarBusquedaSamai($modo, $term, (string) ($data['corporacion'] ?? ''), (string) ($data['tipo_parte'] ?? ''));

            return;
        }

        $this->ejecutarBusqueda($modo, $term, $tipo);
    }

    protected function ejecutarBusquedaSamai(string $modo, string $term, string $corporacion, string $tipoParte): void
    {
        $this->errorMessage = null;
        $this->procesos = [];

        if ($term === '') {
            return;
        }

        $this->searchedTerm = $term;
        $this->searchedMode = $modo;
        $this->searchedSource = 'samai';
        $this->hasSearched = true;

        try {
            $samai = app(SamaiService::class);
            $items = $modo === 'radicado'
                ? array_filter([$samai->findByRadicado($term)])
                : $samai->searchByParty($term, $corporacion, $tipoParte);

            $this->procesos = collect($items)->map(fn (array $item): array => [
                'radicado' => $item['radicado'],
                'despacho' => trim($item['despacho'].' - '.$item['corporacion_nombre'], ' -'),
                'departamento' => $item['clase'],
                'fecha' => $item['fecha_ingreso'],
                'ultima_actuacion' => '',
                'sujetos' => trim('Demandante: '.$item['demandante'].' | Demandado: '.$item['demandado']),
                'es_privado' => $item['es_privado'],
                'estado' => $item['vigente'],
                'url' => $item['url'],
                'fuente' => 'samai',
            ])->values()->all();
        } catch (\Throwable $e) {
            $this->errorMessage = 'No pudimos conectar con SAMAI. Intente en unos minutos.';
            Log::warning('BuscarProcesosTyba: error SAMAI', ['error' => $e->getMessage(), 'modo' => $modo]);
        }

        $this->loadExistingRadicados();
    }

    protected function loadExistingRadicados(): void
    {
        // Con FirmScope: un colaborador solo recibe los radicados de sus casos.
        $this->existingRadicados = LegalCase::withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull('external_case_number')
            ->pluck('external_case_number')
            ->toArray();
    }

    protected function ejecutarBusqueda(string $modo, string $term, int $tipoDocumento = 1): void
    {
        $this->errorMessage = null;
        $this->procesos = [];

        if ($term === '') {
            return;
        }

        $service = app(TybaService::class);
        $this->searchedSource = 'rama';
        $this->searchedTerm = $term;
        $this->searchedMode = $modo;
        $this->hasSearched = true;

        try {
            $result = match ($modo) {
                'radicado' => (function () use ($service, $term) {
                    $info = $service->extractProcessInfo($term);
                    if (! $info) {
                        return [];
                    }
                    $sujetos = is_array($info['sujetos'] ?? null)
                        ? implode(' | ', array_map(
                            fn ($s) => is_array($s) ? ($s['tipo'] ?? '').': '.($s['nombre'] ?? '') : (string) $s,
                            array_slice($info['sujetos'], 0, 4)
                        ))
                        : (string) ($info['sujetos'] ?? '');

                    return [[
                        'radicado' => $info['codigo_proceso'] ?? $term,
                        'despacho' => $info['despacho'] ?? '',
                        'departamento' => $info['departamento'] ?? '',
                        'fecha' => $info['fecha_publicacion'] ?? '',
                        'ultima_actuacion' => $info['fecha_ultima_actuacion'] ?? '',
                        'sujetos' => $sujetos,
                        'es_privado' => $info['es_privado'] ?? false,
                    ]];
                })(),
                'documento' => $service->searchByDocument($term, $tipoDocumento),
                default => $service->searchByName($term),
            };

            $this->procesos = is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            $this->errorMessage = 'No pudimos conectar con la Rama Judicial. Intente en unos minutos.';
            Log::warning('BuscarProcesosTyba: error', ['error' => $e->getMessage(), 'modo' => $modo]);
        }

        $this->loadExistingRadicados();
    }
}
