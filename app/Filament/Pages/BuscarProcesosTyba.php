<?php

namespace App\Filament\Pages;

use App\Models\Client;
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

    /**
     * @var array<string, string>
     */
    public const TIPOS_DOCUMENTO = [
        '1' => 'Cédula de ciudadanía',
        '2' => 'Cédula de extranjería',
        '3' => 'NIT',
        '4' => 'Pasaporte',
        '5' => 'Tarjeta de identidad',
    ];

    protected string $view = 'filament.pages.buscar-procesos-tyba';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Buscar procesos';

    protected static string|UnitEnum|null $navigationGroup = 'Seguimiento';

    protected static ?string $title = 'Buscar procesos en Rama Judicial y SAMAI';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public ?string $searchedTerm = null;

    public string $searchedMode = 'nombre';

    public string $searchedSource = 'todas';

    public array $procesos = [];

    public array $existingRadicados = [];

    public bool $hasSearched = false;

    public ?string $errorMessage = null;

    /**
     * Avisos de la ultima busqueda (por ejemplo, una fuente que no se pudo consultar).
     *
     * @var list<string>
     */
    public array $avisos = [];

    public function mount(): void
    {
        $nombre = request()->query('nombre');
        $radicado = request()->query('radicado');
        $documento = request()->query('documento');
        $tipoDoc = request()->query('tipo_documento');

        $initial = [
            'fuente' => 'todas',
            'corporacion' => '',
            'tipo_parte' => '',
            'modo' => 'auto',
            'termino' => '',
            'nombre' => '',
            'radicado' => '',
            'documento' => '',
            'tipo_documento' => is_string($tipoDoc) && array_key_exists($tipoDoc, self::TIPOS_DOCUMENTO) ? $tipoDoc : '1',
        ];

        if (is_string($radicado) && trim($radicado) !== '') {
            $initial['modo'] = 'radicado';
            $initial['radicado'] = trim($radicado);
        } elseif (is_string($documento) && trim($documento) !== '') {
            $initial['modo'] = 'documento';
            $initial['documento'] = trim($documento);
        } elseif (is_string($nombre) && trim($nombre) !== '') {
            $initial['modo'] = 'nombre';
            $initial['nombre'] = trim($nombre);
        }

        $this->form->fill($initial);

        $term = $initial['modo'] === 'auto' ? '' : $initial[$initial['modo']];

        if ($term === '' || ($initial['modo'] === 'nombre' && mb_strlen($term) < 3) || ! UsageLimiter::attempt('judicial')) {
            return;
        }

        $this->ejecutar('todas', $initial['modo'], $term, (int) $initial['tipo_documento']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('fuente')
                    ->label('Dónde buscar')
                    ->options([
                        'todas' => 'Todas las fuentes (Rama Judicial y SAMAI)',
                        'rama' => 'Rama Judicial (civil, laboral, penal, familia...)',
                        'samai' => 'SAMAI - Consejo de Estado (contencioso administrativo)',
                    ])
                    ->default('todas')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get): void {
                        if ($state === 'samai' && $get('modo') === 'documento') {
                            $set('modo', 'auto');
                        }
                    })
                    ->columnSpanFull(),

                Select::make('modo')
                    ->label('Buscar por')
                    ->options(fn ($get): array => $get('fuente') === 'samai'
                        ? [
                            'auto' => 'Nombre o radicado (se detecta solo)',
                            'nombre' => 'Nombre de la parte',
                            'radicado' => 'Número de radicado',
                        ]
                        : [
                            'auto' => 'Nombre, cédula/NIT o radicado (se detecta solo)',
                            'nombre' => 'Nombre / Razón social',
                            'documento' => 'Cédula o NIT',
                            'radicado' => 'Número de radicado',
                        ])
                    ->default('auto')
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->live()
                    ->columnSpanFull(),

                TextInput::make('termino')
                    ->label('Nombre, cédula/NIT o radicado')
                    ->placeholder('Ej: GARCIA LOPEZ MARIA · 1098765432 · 11001310300120240015800')
                    ->helperText('Escriba un nombre, un radicado de 23 dígitos o la cédula/NIT de un cliente registrado (la Rama Judicial ya no permite buscar cualquier cédula). Detectamos el tipo de búsqueda automáticamente.')
                    ->required(fn ($get): bool => $get('modo') === 'auto')
                    ->minLength(3)
                    ->maxLength(150)
                    ->visible(fn ($get): bool => $get('modo') === 'auto')
                    ->columnSpanFull(),

                TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->placeholder('Ej: GARCIA LOPEZ MARIA')
                    ->helperText('Apellidos primero.')
                    ->required(fn ($get): bool => $get('modo') === 'nombre')
                    ->minLength(3)
                    ->maxLength(150)
                    ->visible(fn ($get): bool => $get('modo') === 'nombre')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->components([
                        Select::make('corporacion')
                            ->label('Corporación o juzgado (SAMAI)')
                            ->options(fn (): array => app(SamaiService::class)->corporations())
                            ->searchable()
                            ->required(fn ($get): bool => $get('fuente') === 'samai' && $get('modo') === 'nombre')
                            ->helperText(fn ($get): string => $get('fuente') === 'todas'
                                ? 'Opcional. SAMAI solo busca nombres dentro de una corporación: elíjala para incluir SAMAI.'
                                : 'SAMAI exige elegir dónde buscar un nombre.'),
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
                    ->visible(fn ($get): bool => $get('fuente') !== 'rama' && in_array($get('modo'), ['auto', 'nombre'], true))
                    ->columnSpanFull(),

                TextInput::make('radicado')
                    ->label('Número de radicado (23 dígitos)')
                    ->placeholder('Ej: 11001310300120240015800')
                    ->helperText('Traemos el expediente completo.')
                    ->required(fn ($get): bool => $get('modo') === 'radicado')
                    ->minLength(20)
                    ->maxLength(30)
                    ->visible(fn ($get): bool => $get('modo') === 'radicado')
                    ->columnSpanFull(),

                Grid::make(3)
                    ->components([
                        Select::make('tipo_documento')
                            ->label('Tipo')
                            ->options(self::TIPOS_DOCUMENTO)
                            ->default('1')
                            ->native(false)
                            ->columnSpan(1),
                        TextInput::make('documento')
                            ->label('Número')
                            ->placeholder('Ej: 1098765432 o 900123456')
                            ->helperText('Sin puntos ni guiones.')
                            ->required(fn ($get): bool => $get('modo') === 'documento')
                            ->minLength(5)
                            ->maxLength(20)
                            ->columnSpan(2),
                    ])
                    ->visible(fn ($get): bool => $get('modo') === 'documento')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Decide si lo escrito es un radicado, un documento o un nombre.
     *
     * @return array{modo: string, termino: string, tipo_documento: int|null}
     */
    public static function detectarTipoBusqueda(string $texto): array
    {
        $texto = trim($texto);
        $soloDigitos = (string) preg_replace('/[\s.\-]/', '', $texto);

        if ($soloDigitos !== '' && ctype_digit($soloDigitos)) {
            if (strlen($soloDigitos) >= 20) {
                return ['modo' => 'radicado', 'termino' => $soloDigitos, 'tipo_documento' => null];
            }

            // NIT de empresa: 9 digitos que empiezan por 8 o 9 (sin digito de verificacion).
            $esNit = strlen($soloDigitos) === 9 && in_array($soloDigitos[0], ['8', '9'], true);

            return ['modo' => 'documento', 'termino' => $soloDigitos, 'tipo_documento' => $esNit ? 3 : 1];
        }

        return ['modo' => 'nombre', 'termino' => (string) preg_replace('/\s+/', ' ', $texto), 'tipo_documento' => null];
    }

    public function buscar(): void
    {
        $data = $this->form->getState();

        if (! UsageLimiter::attempt('judicial')) {
            return;
        }

        $fuente = in_array($data['fuente'] ?? null, ['todas', 'rama', 'samai'], true) ? $data['fuente'] : 'todas';
        $modo = $data['modo'] ?? 'auto';
        $tipo = (int) ($data['tipo_documento'] ?? 1);

        if ($modo === 'auto') {
            $detectado = self::detectarTipoBusqueda((string) ($data['termino'] ?? ''));
            $modo = $detectado['modo'];
            $term = $detectado['termino'];
            $tipo = $detectado['tipo_documento'] ?? $tipo;
        } else {
            $term = trim((string) ($data[$modo] ?? ''));
        }

        $this->ejecutar($fuente, $modo, $term, $tipo, (string) ($data['corporacion'] ?? ''), (string) ($data['tipo_parte'] ?? ''));
    }

    /**
     * Busca en las fuentes elegidas y une los resultados sin repetir radicados.
     */
    protected function ejecutar(string $fuente, string $modo, string $term, int $tipoDocumento = 1, string $corporacion = '', string $tipoParte = ''): void
    {
        $this->errorMessage = null;
        $this->avisos = [];
        $this->procesos = [];

        if ($term === '') {
            return;
        }

        $this->searchedTerm = $term;
        $this->searchedMode = $modo;
        $this->searchedSource = $fuente;
        $this->hasSearched = true;

        // Las fuentes judiciales pueden tardar: se da margen para que el hosting no corte la consulta.
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $resultados = [];
        $errores = [];
        $fuentesConsultadas = 0;

        if ($fuente !== 'samai') {
            $fuentesConsultadas++;

            try {
                $resultados = array_merge($resultados, $this->buscarEnRama($modo, $term, $tipoDocumento));
            } catch (\DomainException $e) {
                $errores[] = $e->getMessage();
            } catch (\Throwable $e) {
                $errores[] = 'No pudimos conectar con la Rama Judicial. Intente en unos minutos.';
                Log::warning('BuscarProcesosTyba: error Rama', ['error' => $e->getMessage(), 'modo' => $modo]);
            }
        }

        if ($fuente !== 'rama') {
            $motivoSinSamai = match (true) {
                $modo === 'documento' => 'SAMAI no permite buscar por cédula o NIT, así que esta búsqueda se hizo solo en la Rama Judicial. Para incluir SAMAI, busque por nombre o radicado.',
                $modo === 'nombre' && $corporacion === '' => 'SAMAI solo busca nombres dentro de una corporación. Elija una en "Corporación o juzgado (SAMAI)" para incluirlo en la búsqueda.',
                default => null,
            };

            if ($motivoSinSamai !== null && $fuente === 'samai') {
                $errores[] = $motivoSinSamai;
            } elseif ($motivoSinSamai !== null) {
                $this->avisos[] = $motivoSinSamai;
            } else {
                $fuentesConsultadas++;

                try {
                    $resultados = array_merge($resultados, $this->buscarEnSamai($modo, $term, $corporacion, $tipoParte));
                } catch (\Throwable $e) {
                    $errores[] = 'No pudimos conectar con SAMAI. Intente en unos minutos.';
                    Log::warning('BuscarProcesosTyba: error SAMAI', ['error' => $e->getMessage(), 'modo' => $modo]);
                }
            }
        }

        $this->procesos = collect($resultados)
            ->filter(fn ($proceso): bool => is_array($proceso) && ! empty($proceso['radicado']))
            ->unique('radicado')
            ->values()
            ->all();

        // Si al menos una fuente respondio, se muestran sus resultados y el fallo queda como aviso.
        if ($errores !== [] && count($errores) < max(1, $fuentesConsultadas)) {
            $this->avisos = array_merge($this->avisos, $errores);
        } elseif ($errores !== []) {
            $this->errorMessage = implode(' ', $errores);
        }

        $this->loadExistingRadicados();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buscarEnRama(string $modo, string $term, int $tipoDocumento): array
    {
        $service = app(TybaService::class);

        $result = match ($modo) {
            'radicado' => (function () use ($service, $term): array {
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
            'documento' => $this->buscarDocumentoEnRama($service, $term, $tipoDocumento),
            default => $service->searchByName($term),
        };

        return collect(is_array($result) ? $result : [])
            ->filter(fn ($proceso): bool => is_array($proceso))
            ->map(fn (array $proceso): array => $proceso + ['fuente' => 'rama'])
            ->values()
            ->all();
    }

    /**
     * La Rama Judicial retiro la consulta publica por cedula/NIT. Si el documento es de un cliente
     * de la firma, se busca por su nombre y se dejan los procesos donde aparece como parte.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buscarDocumentoEnRama(TybaService $service, string $term, int $tipoDocumento): array
    {
        $documento = (string) preg_replace('/[^0-9A-Za-z]/', '', $term);
        $cliente = Client::where('document_number', $documento)->first();
        $nombre = $cliente ? trim("{$cliente->first_name} {$cliente->last_name}") : '';

        $procesos = $service->searchClientProcesses($documento, $tipoDocumento, $nombre);

        if (! $service->documentSearchUnavailable()) {
            return $procesos;
        }

        if (! $cliente) {
            throw new \DomainException('La Rama Judicial ya no permite consultar procesos por cédula o NIT en su sistema público. Busque por nombre (apellidos y nombres) o por número de radicado. Si el documento es de uno de sus clientes, regístrelo en Clientes y lo buscaremos por su nombre.');
        }

        $this->avisos[] = "La Rama Judicial ya no permite consultar por cédula o NIT, así que buscamos por el nombre de su cliente {$nombre} y dejamos solo los procesos donde aparece como parte. Revise que sean de esa persona: puede haber homónimos.";

        return $procesos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buscarEnSamai(string $modo, string $term, string $corporacion, string $tipoParte): array
    {
        $samai = app(SamaiService::class);
        $items = $modo === 'radicado'
            ? array_filter([$samai->findByRadicado($term)])
            : $samai->searchByParty($term, $corporacion, $tipoParte);

        return collect($items)->map(fn (array $item): array => [
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
    }

    protected function loadExistingRadicados(): void
    {
        // Con FirmScope: un colaborador solo recibe los radicados de sus casos.
        $this->existingRadicados = LegalCase::withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull('external_case_number')
            ->pluck('external_case_number')
            ->toArray();
    }
}
