<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\Client;
use App\Models\ImportLog;
use App\Models\LegalCase;
use App\Services\CaseImporter;
use App\Services\SamaiService;
use App\Services\TybaService;
use App\Services\UsageLimiter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
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

    /**
     * Cedula/NIT buscado que no es de ningun cliente: la Rama ya no busca por documento, asi que
     * se pide el nombre de la persona para buscar por el (y, si el abogado quiere, guardarla como cliente).
     *
     * @var array{numero: string, tipo: int}|null
     */
    public ?array $documentoSinCliente = null;

    public string $nombresPersona = '';

    public string $apellidosPersona = '';

    public bool $buscoPorPersona = false;

    /**
     * Cliente registrado cuyo documento se busco: los casos importados quedan a su nombre.
     */
    public ?int $clienteBuscadoId = null;

    /**
     * Radicados marcados para importar en lote.
     *
     * @var list<string>
     */
    public array $seleccionados = [];

    /**
     * Maximo de procesos por lote: cada uno consulta la Rama y la pagina tiene un limite de tiempo.
     */
    public const MAX_IMPORTAR_POR_LOTE = 15;

    /**
     * Tipos de documento de la busqueda (1-5) con su codigo en Clientes.
     *
     * @var array<int, string>
     */
    private const TIPO_DOCUMENTO_CLIENTE = [1 => 'CC', 2 => 'CE', 3 => 'NIT', 4 => 'PP', 5 => 'TI'];

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
        $this->documentoSinCliente = null;
        $this->buscoPorPersona = false;
        $this->clienteBuscadoId = null;
        $this->seleccionados = [];

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

        // El documento no es de un cliente: en vez de "0 procesos", se pide el nombre de la persona.
        if ($this->documentoSinCliente !== null) {
            $this->avisos = [];
            $this->errorMessage = null;
            $this->hasSearched = false;
        }

        $this->loadExistingRadicados();
    }

    /**
     * Busca los procesos de la persona con la cedula/NIT indicado usando el nombre que escribe
     * el abogado. No crea ningun cliente: eso queda como opcion despues de ver los resultados.
     */
    public function buscarPorNombreDePersona(): void
    {
        if ($this->documentoSinCliente === null) {
            return;
        }

        $this->validate([
            'nombresPersona' => ['required', 'string', 'min:2', 'max:100'],
            'apellidosPersona' => ['required', 'string', 'min:2', 'max:100'],
        ], attributes: [
            'nombresPersona' => 'nombres',
            'apellidosPersona' => 'apellidos',
        ]);

        if (! UsageLimiter::attempt('judicial')) {
            return;
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $nombre = $this->nombreCompletoPersona();
        $this->errorMessage = null;
        $this->avisos = [];
        $this->procesos = [];
        $this->searchedTerm = $nombre;
        $this->searchedMode = 'nombre';
        $this->searchedSource = 'rama';
        $this->hasSearched = true;
        $this->buscoPorPersona = true;
        $this->seleccionados = [];

        try {
            $this->procesos = collect(app(TybaService::class)->searchByPartyName($nombre))
                ->filter(fn ($proceso): bool => is_array($proceso) && ! empty($proceso['radicado']))
                ->map(fn (array $proceso): array => $proceso + ['fuente' => 'rama'])
                ->unique('radicado')
                ->values()
                ->all();
            $this->avisos[] = "Buscamos por el nombre {$nombre} y dejamos solo los procesos donde aparece como parte. Revise que sean de la persona con documento {$this->documentoSinCliente['numero']}: puede haber homónimos.";
        } catch (\Throwable $e) {
            $this->errorMessage = 'No pudimos conectar con la Rama Judicial. Intente en unos minutos.';
            Log::warning('BuscarProcesosTyba: error Rama por nombre de persona', ['error' => $e->getMessage()]);
        }

        $this->loadExistingRadicados();
    }

    /**
     * Guarda como cliente a la persona buscada (documento y nombre), para no volver a escribirlo
     * y para que la vigilancia nocturna le busque procesos nuevos.
     */
    public function guardarComoCliente(): void
    {
        $cliente = $this->registrarPersonaComoCliente();

        if ($cliente === null) {
            return;
        }

        Notification::make()
            ->title('Cliente guardado')
            ->body("{$cliente->first_name} {$cliente->last_name} ya está en Clientes. La próxima vez puede buscarlo por su documento.")
            ->success()
            ->actions([
                Action::make('completar')
                    ->label('Completar sus datos')
                    ->url(ClientResource::getUrl('edit', ['record' => $cliente])),
            ])
            ->send();
    }

    protected function registrarPersonaComoCliente(): ?Client
    {
        if ($this->documentoSinCliente === null || ! $this->buscoPorPersona) {
            return null;
        }

        $numero = $this->documentoSinCliente['numero'];
        $cliente = Client::where('document_number', $numero)->first() ?? Client::create([
            'firm_id' => auth()->user()->firm_id,
            'user_id' => auth()->id(),
            'document_type' => self::TIPO_DOCUMENTO_CLIENTE[$this->documentoSinCliente['tipo']] ?? 'CC',
            'document_number' => $numero,
            'first_name' => trim($this->nombresPersona),
            'last_name' => trim($this->apellidosPersona),
            'is_demo' => false,
        ]);

        $this->documentoSinCliente = null;
        $this->clienteBuscadoId = $cliente->id;

        return $cliente;
    }

    /**
     * Importa uno, los marcados o todos los procesos nuevos de la busqueda. Si se busco por el
     * documento de un cliente, quedan a su nombre; si la persona no es cliente, se registra al
     * importar; en otro caso el abogado elige (o crea) el cliente en el modal.
     */
    public function importarAction(): Action
    {
        return Action::make('importar')
            ->label(fn (array $arguments): string => match ($arguments['modo'] ?? 'uno') {
                'todos' => 'Importar todos los nuevos ('.count($this->radicadosNuevos()).')',
                'seleccionados' => 'Importar seleccionados ('.count($this->radicadosParaImportar(['modo' => 'seleccionados'])).')',
                default => $this->debeRegistrarPersona() ? 'Registrar cliente e importar' : 'Importar caso',
            })
            ->icon('heroicon-o-arrow-down-tray')
            ->size('sm')
            // Un solo color fuerte por pantalla: con varios resultados, el azul queda para el lote.
            ->color(fn (array $arguments): string => match ($arguments['modo'] ?? 'uno') {
                'seleccionados' => 'primary',
                'todos' => 'gray',
                default => count($this->radicadosNuevos()) > 1 ? 'gray' : 'primary',
            })
            ->visible(fn (): bool => LegalCaseResource::canCreate())
            ->disabled(fn (array $arguments): bool => $this->radicadosParaImportar($arguments) === [])
            ->modalHeading(fn (array $arguments): string => count($this->radicadosParaImportar($arguments)) === 1
                ? 'Importar el proceso '.($this->radicadosParaImportar($arguments)[0] ?? '')
                : 'Importar '.count($this->radicadosParaImportar($arguments)).' procesos')
            ->modalDescription(fn (array $arguments): string => $this->descripcionImportacion($arguments))
            ->modalSubmitActionLabel(fn (): string => $this->debeRegistrarPersona() ? 'Registrar e importar' : 'Importar')
            ->schema(fn (): array => $this->clienteParaImportar() !== null || $this->debeRegistrarPersona() ? [] : $this->esquemaClienteDeImportacion())
            ->action(function (array $data, array $arguments): void {
                $clientId = ($data['destino'] ?? 'existente') === 'nuevo'
                    ? $this->crearClienteDeImportacion($data)->id
                    : (isset($data['client_id']) ? (int) $data['client_id'] : null);

                $this->importar($this->radicadosParaImportar($arguments), $clientId);
            });
    }

    /**
     * Campos del modal para elegir un cliente registrado o crear uno nuevo ahi mismo
     * (con el nombre buscado ya escrito).
     *
     * @return array<int, mixed>
     */
    protected function esquemaClienteDeImportacion(): array
    {
        $hayClientes = Client::query()->exists();
        [$nombres, $apellidos] = $this->nombreBuscadoSeparado();
        $esNuevo = fn (Get $get): bool => $get('destino') === 'nuevo';

        return [
            ToggleButtons::make('destino')
                ->label('¿A nombre de qué cliente?')
                ->options(['existente' => 'Un cliente registrado', 'nuevo' => 'Un cliente nuevo'])
                ->icons(['existente' => 'heroicon-o-user', 'nuevo' => 'heroicon-o-user-plus'])
                ->default($hayClientes ? 'existente' : 'nuevo')
                ->inline()
                ->live()
                ->required(),
            Select::make('client_id')
                ->label('Cliente')
                ->helperText('Los casos importados quedan a nombre de este cliente.')
                ->options(fn (): array => Client::query()
                    ->orderBy('first_name')
                    ->get()
                    ->mapWithKeys(fn (Client $client): array => [$client->id => trim("{$client->first_name} {$client->last_name}").' · '.$client->document_number])
                    ->all())
                ->searchable()
                ->visible(fn (Get $get): bool => ! $esNuevo($get))
                ->required(fn (Get $get): bool => ! $esNuevo($get)),
            Grid::make(2)
                ->visible($esNuevo)
                ->schema([
                    Select::make('document_type')
                        ->label('Tipo de documento')
                        ->options(array_combine(self::TIPO_DOCUMENTO_CLIENTE, ['Cédula de Ciudadanía', 'Cédula de Extranjería', 'NIT', 'Pasaporte', 'Tarjeta de Identidad']))
                        ->default('CC')
                        ->required($esNuevo),
                    TextInput::make('document_number')
                        ->label('Número de documento')
                        ->helperText('Si ya existe un cliente con este documento, se usa ese.')
                        ->required($esNuevo)
                        ->maxLength(20),
                    TextInput::make('first_name')->label('Nombres')->default($nombres)->required($esNuevo)->maxLength(100),
                    TextInput::make('last_name')->label('Apellidos')->default($apellidos)->required($esNuevo)->maxLength(100),
                    TextInput::make('email')
                        ->label('Correo (opcional)')
                        ->helperText('Para avisarle de las novedades de sus procesos.')
                        ->email()
                        ->maxLength(150)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function crearClienteDeImportacion(array $data): Client
    {
        $numero = trim((string) $data['document_number']);

        return Client::where('document_number', $numero)->first() ?? Client::create([
            'firm_id' => auth()->user()->firm_id,
            'user_id' => auth()->id(),
            'document_type' => in_array($data['document_type'] ?? null, self::TIPO_DOCUMENTO_CLIENTE, true) ? $data['document_type'] : 'CC',
            'document_number' => $numero,
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'is_demo' => false,
        ]);
    }

    /**
     * Separa el nombre buscado en nombres y apellidos (en Colombia, los dos ultimos son los apellidos).
     *
     * @return array{0: string, 1: string}
     */
    protected function nombreBuscadoSeparado(): array
    {
        if ($this->searchedMode !== 'nombre' || blank($this->searchedTerm)) {
            return ['', ''];
        }

        $palabras = preg_split('/\s+/', mb_convert_case(mb_strtolower(trim($this->searchedTerm)), MB_CASE_TITLE, 'UTF-8')) ?: [];
        $cantidadNombres = match (true) {
            count($palabras) >= 4 => count($palabras) - 2,
            count($palabras) === 3 => 1,
            count($palabras) === 2 => 1,
            default => count($palabras),
        };

        return [implode(' ', array_slice($palabras, 0, $cantidadNombres)), implode(' ', array_slice($palabras, $cantidadNombres))];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<string>
     */
    protected function radicadosParaImportar(array $arguments): array
    {
        $nuevos = $this->radicadosNuevos();

        return match ($arguments['modo'] ?? 'uno') {
            'todos' => $nuevos,
            'seleccionados' => array_values(array_intersect($nuevos, array_map('strval', $this->seleccionados))),
            default => in_array((string) ($arguments['radicado'] ?? ''), $nuevos, true) ? [(string) $arguments['radicado']] : [],
        };
    }

    /**
     * Radicados de la busqueda que la firma todavia no tiene como caso.
     *
     * @return list<string>
     */
    protected function radicadosNuevos(): array
    {
        return collect($this->procesos)
            ->pluck('radicado')
            ->map(fn ($radicado): string => (string) $radicado)
            ->reject(fn (string $radicado): bool => in_array($radicado, $this->existingRadicados, true))
            ->values()
            ->all();
    }

    protected function debeRegistrarPersona(): bool
    {
        return $this->documentoSinCliente !== null && $this->buscoPorPersona;
    }

    protected function clienteParaImportar(): ?Client
    {
        return $this->clienteBuscadoId ? Client::find($this->clienteBuscadoId) : null;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function descripcionImportacion(array $arguments): string
    {
        $cantidad = count($this->radicadosParaImportar($arguments));
        $cliente = $this->clienteParaImportar();

        $para = match (true) {
            $this->debeRegistrarPersona() => 'Registraremos a '.$this->nombreCompletoPersona()." (documento {$this->documentoSinCliente['numero']}) como cliente y ",
            $cliente !== null => 'Quedarán a nombre de '.trim("{$cliente->first_name} {$cliente->last_name}").'. ',
            default => '',
        };

        $lote = $cantidad > self::MAX_IMPORTAR_POR_LOTE
            ? ' Se importan '.self::MAX_IMPORTAR_POR_LOTE.' por vez: al terminar, vuelva a pulsar para seguir con el resto.'
            : '';

        $accion = $this->debeRegistrarPersona() ? 'traeremos' : 'Traemos';

        return "{$para}{$accion} de la Rama Judicial (o SAMAI) los datos, las partes y las actuaciones de cada proceso. Después LegalWeb consulta las actuaciones nuevas cada noche.{$lote}";
    }

    /**
     * @param  list<string>  $radicados
     */
    protected function importar(array $radicados, ?int $clientId): void
    {
        if ($radicados === []) {
            return;
        }

        $cliente = $this->debeRegistrarPersona()
            ? $this->registrarPersonaComoCliente()
            : ($this->clienteParaImportar() ?? ($clientId ? Client::find($clientId) : null));

        if ($cliente === null) {
            Notification::make()->title('Elija el cliente de los casos')->warning()->send();

            return;
        }

        $importer = new CaseImporter(auth()->user());

        if (($limite = $importer->caseLimitMessage()) !== null) {
            Notification::make()->title('Alcanzó el límite de casos activos')->body($limite)->warning()->persistent()->send();

            return;
        }

        if (! UsageLimiter::attempt('judicial')) {
            return;
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $limits = auth()->user()->firm?->planLimits();
        $cupo = $limits?->maxActiveCases() === null ? PHP_INT_MAX : max(0, $limits->maxActiveCases() - $limits->activeCasesCount());
        $porImportar = array_slice($radicados, 0, min(self::MAX_IMPORTAR_POR_LOTE, $cupo));
        $inicio = microtime(true);
        $resultados = [];

        foreach ($porImportar as $radicado) {
            // Margen para no pasar el limite de tiempo del servidor: lo que falte se importa en otra pasada.
            if (microtime(true) - $inicio > 90) {
                break;
            }

            $resultado = $importer->import($radicado, $cliente->id, auth()->id());

            $resultados[] = [
                'radicado' => $radicado,
                'status' => match (true) {
                    $resultado['error'] !== null && str_contains($resultado['error'], 'ya existe') => 'duplicado',
                    $resultado['error'] !== null => 'error',
                    ! $resultado['imported'] => 'no_encontrado',
                    default => 'ok',
                },
                'msg' => $resultado['error'] ?? $resultado['message'] ?? 'No se encontró en la Rama Judicial ni en SAMAI.',
                'case_number' => $resultado['case']?->case_number,
                'case_id' => $resultado['case']?->id,
            ];
        }

        $this->loadExistingRadicados();
        $this->seleccionados = array_values(array_diff(array_map('strval', $this->seleccionados), $this->existingRadicados));

        $resumen = collect($resultados)->countBy('status');
        $importados = $resumen->get('ok', 0);
        $pendientes = count($radicados) - count($resultados);

        if (count($resultados) > 1) {
            ImportLog::create([
                'firm_id' => auth()->user()->firm_id,
                'user_id' => auth()->id(),
                'total_radicados' => count($resultados),
                'importados' => $importados,
                'duplicados' => $resumen->get('duplicado', 0),
                'no_encontrados' => $resumen->get('no_encontrado', 0),
                'errores' => $resumen->get('error', 0),
                'detalle' => $resultados,
            ]);
        }

        $detalle = array_filter([
            $resumen->get('duplicado') ? $resumen->get('duplicado').' ya estaban importados' : null,
            $resumen->get('no_encontrado') ? $resumen->get('no_encontrado').' no se encontraron en la Rama ni en SAMAI' : null,
            $resumen->get('error') ? $resumen->get('error').' con error' : null,
            $pendientes > 0 ? "{$pendientes} quedan por importar: pulse de nuevo para seguir" : null,
        ]);

        $primerCaso = collect($resultados)->firstWhere('status', 'ok');

        Notification::make()
            ->title(match (true) {
                $importados === 0 => 'No se importó ningún caso',
                $importados === 1 => 'Caso importado a nombre de '.trim("{$cliente->first_name} {$cliente->last_name}"),
                default => "{$importados} casos importados a nombre de ".trim("{$cliente->first_name} {$cliente->last_name}"),
            })
            ->body(implode('. ', array_merge(
                $importados > 0 ? ['Con sus actuaciones; las nuevas se consultan cada noche'] : [],
                $detalle,
            )).'.')
            ->color($importados > 0 ? 'success' : 'warning')
            ->actions($importados === 1 && $primerCaso ? [
                Action::make('ver_caso')
                    ->label('Ver caso')
                    ->url(LegalCaseResource::getUrl('view', ['record' => $primerCaso['case_id']])),
            ] : ($importados > 1 ? [
                Action::make('ver_casos')
                    ->label('Ver casos')
                    ->url(LegalCaseResource::getUrl('index')),
            ] : []))
            ->persistent()
            ->send();
    }

    public function urlRegistrarCliente(): string
    {
        return ClientResource::getUrl('create', array_filter([
            'document_number' => $this->documentoSinCliente['numero'] ?? null,
            'document_type' => self::TIPO_DOCUMENTO_CLIENTE[$this->documentoSinCliente['tipo'] ?? 1] ?? 'CC',
            'first_name' => trim($this->nombresPersona),
            'last_name' => trim($this->apellidosPersona),
        ]));
    }

    protected function nombreCompletoPersona(): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($this->nombresPersona.' '.$this->apellidosPersona));
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

        $this->clienteBuscadoId = $cliente?->id;

        if (! $service->documentSearchUnavailable()) {
            return $procesos;
        }

        if (! $cliente) {
            $this->documentoSinCliente = ['numero' => $documento, 'tipo' => $tipoDocumento];

            return [];
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
