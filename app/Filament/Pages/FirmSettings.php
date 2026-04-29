<?php

namespace App\Filament\Pages;

use App\Models\LegalAcceptance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class FirmSettings extends Page
{
    protected string $view = 'filament.pages.firm-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuracion';

    protected static ?string $navigationLabel = 'Mi Firma';

    protected static ?string $title = 'Mi Firma';

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
                Grid::make(3)
                    ->schema([
                        Section::make('Logo')
                            ->description('Aparecera en el panel, correos y portal del cliente.')
                            ->columnSpan(1)
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Logo de la firma')
                                    ->image()
                                    ->disk('public')
                                    ->directory('logos')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('400')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                    ->helperText(new HtmlString(
                                        '<div style="font-size:12px;line-height:1.5;color:#475569;">'
                                        .'<strong style="color:#1E3A5F;">Recomendaciones:</strong><br>'
                                        .'&bull; <strong>Formato:</strong> PNG con fondo transparente (ideal), SVG, JPG o WebP.<br>'
                                        .'&bull; <strong>Proporcion:</strong> horizontal (ej. 600&times;200 px) o cuadrada (400&times;400 px).<br>'
                                        .'&bull; <strong>Resolucion minima:</strong> 400 px en su lado mas largo.<br>'
                                        .'&bull; <strong>Tamano maximo:</strong> 2 MB.<br>'
                                        .'&bull; Recorte el logo cerca del contenido (sin mucho espacio en blanco alrededor).'
                                        .'</div>'
                                    )),
                            ]),
                        Section::make('Datos principales')
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre de la Firma')
                                    ->placeholder('Ej: Rodriguez & Asociados')
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('email')
                                    ->label('Correo')
                                    ->email()
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('Telefono')
                                    ->tel()
                                    ->placeholder('Ej: 3101234567'),
                            ]),
                    ]),
                Section::make('Datos adicionales')
                    ->description('Esta informacion es opcional. Puede completarla despues.')
                    ->collapsible()
                    ->collapsed(fn () => auth()->user()->firm?->onboarding_completed ?? false)
                    ->columns(2)
                    ->schema([
                        TextInput::make('nit')
                            ->label('NIT'),
                        TextInput::make('legal_name')
                            ->label('Razon Social'),
                        TextInput::make('city')
                            ->label('Ciudad')
                            ->placeholder('Ej: Bogota'),
                        TextInput::make('department')
                            ->label('Departamento')
                            ->placeholder('Ej: Cundinamarca'),
                        TextInput::make('address')
                            ->label('Direccion')
                            ->columnSpanFull(),
                        TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->placeholder('https://'),
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->placeholder('Areas de practica, experiencia, etc.')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
                Section::make('Terminos y Condiciones')
                    ->schema([
                        Placeholder::make('terms_info')
                            ->content(new HtmlString(
                                'Al utilizar LegalWeb usted acepta nuestros <a href="/portal/terminos" target="_blank" style="color: #3A86FF; text-decoration: underline; font-weight: 600;">Terminos y Condiciones</a> y la <a href="/portal/privacidad" target="_blank" style="color: #3A86FF; text-decoration: underline; font-weight: 600;">Politica de Privacidad</a>. Puede consultarlos en cualquier momento.'
                            )),
                        Checkbox::make('accept_terms')
                            ->label(new HtmlString(
                                'He leido y acepto los <a href="/portal/terminos" target="_blank" style="color: #3A86FF; text-decoration: underline;">Terminos y Condiciones</a> y la <a href="/portal/privacidad" target="_blank" style="color: #3A86FF; text-decoration: underline;">Politica de Privacidad y Tratamiento de Datos Personales</a>'
                            ))
                            ->required(fn () => ! (auth()->user()->firm?->onboarding_completed ?? false))
                            ->dehydrated(false)
                            ->visible(fn () => ! (auth()->user()->firm?->onboarding_completed ?? false))
                            ->validationMessages(['accepted' => 'Debe aceptar los terminos y condiciones para continuar.']),
                    ])
                    ->visible(fn () => ! (auth()->user()->firm?->onboarding_completed ?? false)),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $firm = auth()->user()->firm;
        $wasOnboarding = ! $firm->onboarding_completed;
        unset($data['accept_terms']);
        $firm->update(array_merge($data, ['onboarding_completed' => true]));

        if ($wasOnboarding) {
            LegalAcceptance::create([
                'acceptor_type' => 'abogado',
                'acceptor_name' => auth()->user()->name,
                'acceptor_email' => auth()->user()->email,
                'document_type' => 'terminos_registro',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);

            $this->js("
                Swal.fire({
                    icon: 'success',
                    title: '!Bienvenido a LegalWeb!',
                    html: '<b>".e($firm->name)."</b> esta lista.<br>Hemos cargado datos de ejemplo para que explores la plataforma.',
                    confirmButtonText: 'Comenzar',
                    confirmButtonColor: '#3A86FF',
                    showConfirmButton: true,
                    timer: 8000,
                    timerProgressBar: true,
                }).then(() => { window.location.href = '/admin'; });
            ");

            return;
        }

        Notification::make()
            ->title('Datos actualizados')
            ->success()
            ->send();
    }

    public function hasFormActionsInHeader(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('replay_tour')
                ->label('Volver a ver tour')
                ->icon('heroicon-o-academic-cap')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reiniciar el tour guiado')
                ->modalDescription('Le mostraremos nuevamente el tour introductorio con las funcionalidades principales de la plataforma.')
                ->modalSubmitActionLabel('Si, mostrar tour')
                ->action(function () {
                    try {
                        auth()->user()->update(['tour_completed_at' => null]);
                    } catch (\Throwable $e) {
                        // Si la columna no existe (falta migrar), igual disparamos el tour via query param
                    }
                    $this->redirect('/admin?tour=1');
                }),
        ];
    }
}
