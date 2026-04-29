<?php

namespace App\Filament\Pages;

use App\Models\LegalCase;
use App\Services\TybaService;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BuscarProcesosTyba extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.buscar-procesos-tyba';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Buscar en Rama Judicial';

    protected static ?string $title = 'Buscar Procesos por Nombre';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public ?string $searchedName = null;

    public array $procesos = [];

    public array $existingRadicados = [];

    public bool $hasSearched = false;

    public function mount(): void
    {
        $nombre = request()->query('nombre');

        $this->form->fill([
            'nombre' => is_string($nombre) ? trim($nombre) : '',
        ]);

        if (is_string($nombre) && strlen(trim($nombre)) >= 3) {
            $this->ejecutarBusqueda(trim($nombre));
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre completo a buscar')
                    ->placeholder('Ej: GARCIA LOPEZ MARIA')
                    ->helperText('Sugerencia: escriba apellidos primero. La busqueda se hace directamente en la Rama Judicial de Colombia.')
                    ->required()
                    ->minLength(3)
                    ->maxLength(150)
                    ->autofocus()
                    ->columnSpanFull(),
            ]);
    }

    public function buscar(): void
    {
        $data = $this->form->getState();
        $this->ejecutarBusqueda(trim($data['nombre'] ?? ''));
    }

    protected function ejecutarBusqueda(string $nombre): void
    {
        if (strlen($nombre) < 3) {
            return;
        }

        $this->searchedName = $nombre;
        $this->hasSearched = true;
        $procesos = app(TybaService::class)->searchByName($nombre);
        $this->procesos = is_array($procesos)
            ? $procesos
            : (method_exists($procesos, 'toArray') ? $procesos->toArray() : []);

        $this->existingRadicados = LegalCase::withoutGlobalScopes()
            ->where('firm_id', auth()->user()->firm_id)
            ->whereNotNull('external_case_number')
            ->pluck('external_case_number')
            ->toArray();
    }
}
