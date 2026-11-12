<?php

namespace App\Filament\Pages;

use App\Models\LegalAcceptance;
use App\Services\WhatsAppService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\HtmlString;
use UnitEnum;

class FirmSettings extends Page
{
    protected string $view = 'filament.pages.firm-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Mi Firma';

    protected static ?string $title = 'Mi Firma';

    protected static ?int $navigationSort = 30;

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
            'security_email_enabled' => DbSchema::hasColumn('users', 'security_email_enabled')
                ? (auth()->user()->getAttribute('security_email_enabled') ?? true)
                : true,
            'whatsapp_enabled' => DbSchema::hasColumn('users', 'whatsapp_enabled')
                && (bool) auth()->user()->getAttribute('whatsapp_enabled'),
            'whatsapp_phone' => DbSchema::hasColumn('users', 'whatsapp_phone')
                ? auth()->user()->getAttribute('whatsapp_phone')
                : null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(3)
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
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
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                    ->helperText(new HtmlString(
                                        '<div style="font-size:12px;line-height:1.5;color:#475569;">'
                                        .'<strong style="color:#1E3A5F;">Recomendaciones:</strong><br>'
                                        .'&bull; <strong>Formato:</strong> PNG con fondo transparente (ideal), JPG o WebP.<br>'
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
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
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
                Section::make('Seguridad de la cuenta')
                    ->description('Configuraciones relacionadas con su cuenta personal.')
                    ->visible(fn () => DbSchema::hasColumn('users', 'security_email_enabled'))
                    ->schema([
                        Toggle::make('security_email_enabled')
                            ->label('Recibir alerta por correo cada que inicie sesion')
                            ->helperText('Le llegara un correo con la fecha, IP y navegador del inicio de sesion. Es una capa adicional de seguridad similar a la que usan los bancos. Recomendamos mantenerla activa.')
                            ->default(true)
                            ->live(),
                    ]),

                Section::make('Notificaciones por WhatsApp')
                    ->description('Reciba en su celular las alertas importantes: vencimientos, nuevas actuaciones de la Rama Judicial, documentos del cliente y accesos al portal.')
                    ->visible(fn () => DbSchema::hasColumn('users', 'whatsapp_enabled'))
                    ->schema([
                        Placeholder::make('whatsapp_status')
                            ->label('')
                            ->content(fn (): HtmlString => app(WhatsAppService::class)->isConfigured()
                                ? new HtmlString('<span style="color:#15803d;font-weight:600;">El servicio de WhatsApp esta activo en la plataforma.</span>')
                                : new HtmlString('<span style="color:#92400e;font-weight:600;">El servicio de WhatsApp aun no esta activo en la plataforma. Puede dejar su numero configurado y empezara a recibir mensajes cuando se active.</span>')),
                        Toggle::make('whatsapp_enabled')
                            ->label('Recibir notificaciones por WhatsApp')
                            ->live(),
                        TextInput::make('whatsapp_phone')
                            ->label('Celular de WhatsApp')
                            ->tel()
                            ->placeholder('Ej: 3001234567')
                            ->helperText('Numero colombiano de 10 digitos o internacional con indicativo (+57...).')
                            ->required(fn (Get $get): bool => (bool) $get('whatsapp_enabled'))
                            ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                                if (filled($value) && ! WhatsAppService::normalizePhone($value)) {
                                    $fail('El numero de celular no es valido.');
                                }
                            }),
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

        // Campos personales del usuario (no de la firma).
        $userFields = [];
        if (array_key_exists('security_email_enabled', $data)) {
            $userFields['security_email_enabled'] = (bool) $data['security_email_enabled'];
            unset($data['security_email_enabled']);
        }
        if (array_key_exists('whatsapp_enabled', $data)) {
            $userFields['whatsapp_enabled'] = (bool) $data['whatsapp_enabled'];
            $userFields['whatsapp_phone'] = WhatsAppService::normalizePhone($data['whatsapp_phone'] ?? null);
            unset($data['whatsapp_enabled'], $data['whatsapp_phone']);
        }
        if (! empty($userFields)) {
            try {
                auth()->user()->update($userFields);
            } catch (\Throwable $e) {
                // Si la columna aun no existe en produccion (migracion pendiente),
                // ignoramos silenciosamente y seguimos guardando los datos de la firma.
            }
        }

        // Solo los administradores cambian los datos de la firma (nombre, correo, logo...),
        // que aparecen en el portal y en los correos a los clientes.
        if (! auth()->user()->isAdmin()) {
            Notification::make()->title('Preferencias guardadas')->success()->send();

            return;
        }

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
            Action::make('test_whatsapp')
                ->label('Probar WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->visible(fn (): bool => app(WhatsAppService::class)->isConfigured() && filled(auth()->user()->whatsapp_phone))
                ->requiresConfirmation()
                ->modalHeading('Enviar mensaje de prueba')
                ->modalDescription(fn (): string => 'Se enviara un WhatsApp de prueba a '.auth()->user()->whatsapp_phone.'. Si no tiene plantilla aprobada, el mensaje solo llega si usted escribio al numero de LegalWeb en las ultimas 24 horas.')
                ->action(function (WhatsAppService $whatsApp): void {
                    $user = auth()->user();

                    // Cada mensaje cuesta en Twilio: maximo 3 pruebas por hora por usuario.
                    $limiterKey = 'whatsapp-test:'.$user->id;
                    if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
                        Notification::make()
                            ->title('Demasiadas pruebas')
                            ->body('Puede volver a probar en '.ceil(RateLimiter::availableIn($limiterKey) / 60).' minuto(s).')
                            ->warning()
                            ->send();

                        return;
                    }
                    RateLimiter::hit($limiterKey, 3600);

                    $sent = $whatsApp->sendTemplate(
                        $user->whatsapp_phone,
                        'reminder.due.lawyer',
                        [1 => $user->name, 2 => 'Mensaje de prueba', 3 => now()->format('d/m/Y H:i'), 4 => url('/admin')],
                        "LegalWeb - Hola {$user->name}, este es un mensaje de prueba. Sus notificaciones por WhatsApp estan funcionando.",
                    );

                    Notification::make()
                        ->title($sent ? 'Mensaje enviado' : 'No se pudo enviar')
                        ->body($sent ? 'Revise su WhatsApp.' : 'Twilio rechazo el mensaje. El detalle quedo en el log del sistema.')
                        ->status($sent ? 'success' : 'danger')
                        ->send();
                }),
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
