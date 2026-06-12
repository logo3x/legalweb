<?php

namespace App\Filament\Pages;

use App\Models\LegalCase;
use App\Services\TybaService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class BuscarProcesosTyba extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.buscar-procesos-tyba';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Buscar en Rama Judicial';

    protected static string|UnitEnum|null $navigationGroup = 'Casos';

    protected static ?string $title = 'Importar casos desde la Rama Judicial';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public ?string $searchedTerm = null;

    public string $searchedMode = 'nombre';

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
                Select::make('modo')
                    ->label('Buscar por')
                    ->options([
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
        $modo = $data['modo'] ?? 'nombre';
        $term = trim((string) ($data[$modo] ?? ''));
        $tipo = (int) ($data['tipo_documento'] ?? 1);
        $this->ejecutarBusqueda($modo, $term, $tipo);
    }

    protected function ejecutarBusqueda(string $modo, string $term, int $tipoDocumento = 1): void
    {
        $this->errorMessage = null;
        $this->procesos = [];

        if ($term === '') {
            return;
        }

        $service = app(TybaService::class);
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

        $this->existingRadicados = LegalCase::withoutGlobalScopes()
            ->where('firm_id', auth()->user()->firm_id)
            ->whereNotNull('external_case_number')
            ->pluck('external_case_number')
            ->toArray();
    }
}
