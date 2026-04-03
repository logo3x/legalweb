<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class Onboarding extends Page
{
    protected string $view = 'filament.pages.onboarding';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Configurar mi Firma';

    public ?array $data = [];

    public function mount(): void
    {
        $firm = auth()->user()->firm;

        if (! $firm || $firm->onboarding_completed) {
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
                    ->label('Razón Social'),
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('address')
                    ->label('Dirección'),
                TextInput::make('city')
                    ->label('Ciudad'),
                TextInput::make('department')
                    ->label('Departamento'),
                TextInput::make('website')
                    ->label('Sitio Web')
                    ->url(),
                Textarea::make('description')
                    ->label('Descripción de la Firma')
                    ->placeholder('Breve descripción de su firma, áreas de práctica, etc.'),
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
            ->title('Firma configurada correctamente')
            ->body('Hemos cargado 3 casos de ejemplo para que explore la plataforma.')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
