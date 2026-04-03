<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FirmSettings extends Page
{
    protected string $view = 'filament.pages.firm-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuracion';

    protected static ?string $navigationLabel = 'Mi Firma';

    protected static ?string $title = 'Configuracion de la Firma';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public function mount(): void
    {
        $firm = auth()->user()->firm;

        if (! $firm) {
            $this->redirect('/admin');

            return;
        }

        $this->form->fill([
            'name' => $firm->name,
            'nit' => $firm->nit,
            'legal_name' => $firm->legal_name,
            'email' => $firm->email ?? auth()->user()->email,
            'phone' => $firm->phone,
            'address' => $firm->address,
            'city' => $firm->city,
            'department' => $firm->department,
            'website' => $firm->website,
            'description' => $firm->description,
            'logo_path' => $firm->logo_path,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la Firma / Despacho')
                    ->required(),
                TextInput::make('nit')
                    ->label('NIT')
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Numero de Identificacion Tributaria de la firma o persona natural.')
                    ->maxLength(20),
                TextInput::make('legal_name')
                    ->label('Razon Social'),
                TextInput::make('email')
                    ->label('Correo Electronico')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Telefono')
                    ->tel(),
                TextInput::make('address')
                    ->label('Direccion'),
                TextInput::make('city')
                    ->label('Ciudad'),
                TextInput::make('department')
                    ->label('Departamento'),
                TextInput::make('website')
                    ->label('Sitio Web')
                    ->url(),
                Textarea::make('description')
                    ->label('Descripcion de la Firma')
                    ->placeholder('Breve descripcion de su firma, areas de practica, etc.'),
                FileUpload::make('logo_path')
                    ->label('Logo de la Firma (opcional)')
                    ->image()
                    ->directory('logos')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('200')
                    ->imageResizeTargetHeight('200'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $firm = auth()->user()->firm;
        $firm->update(array_merge($data, ['onboarding_completed' => true]));

        Notification::make()
            ->title('Datos de la firma actualizados')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }
}
