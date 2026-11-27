<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\PlanOverview;
use App\Http\Middleware\EnforceSubscriptionStatus;
use App\Http\Middleware\EnsureOnboardingCompleted;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Sin registro por formulario: las cuentas se crean con Google (que crea la firma) o por invitacion.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // Lo mas usado arriba (Casos, Agenda, Vencimientos, Clientes sin grupo); luego estos grupos.
            ->navigationGroups([
                NavigationGroup::make('Seguimiento'),
                NavigationGroup::make('Herramientas'),
                NavigationGroup::make('Configuración')->collapsed(),
                NavigationGroup::make('Super Admin')->collapsed(),
            ])
            ->sidebarCollapsibleOnDesktop()
            // Navegacion sin recargar la pagina y precarga al pasar el mouse por un enlace:
            // cambiar de seccion se siente instantaneo. Las descargas, pagos, Google, portal
            // y bajas de correo siempre se abren con una carga normal.
            ->spa(hasPrefetching: true)
            ->spaUrlExceptions([
                '*/download/*',
                '*/documents/*',
                '*/wompi/*',
                '*/auth/*',
                '*/portal/*',
                '*/correos/*',
                '*/setup.php*',
            ])
            // Ctrl+K / Cmd+K abre la busqueda de casos y clientes.
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->unsavedChangesAlerts()
            ->brandName('LegalWeb')
            ->favicon(asset('images/logo-icon.svg'))
            ->brandLogo(function () {
                $firm = auth()->user()?->firm;

                if ($firm && $firm->logo_path) {
                    return new HtmlString(
                        '<div style="display:flex;flex-direction:column;align-items:flex-start;gap:.2rem;line-height:1;">'
                        .'<img src="'.e($firm->logo_url).'" alt="'.e($firm->name).'" '
                        .'style="height:3rem;width:auto;max-width:280px;object-fit:contain;display:block;">'
                        .'<span style="font-family:Inter,sans-serif;font-weight:500;font-size:.7rem;color:#64748B;letter-spacing:.02em;display:flex;align-items:center;gap:.25rem;">'
                        .'powered by'
                        .'<img src="'.asset('images/logo-icon.svg').'" alt="" style="height:.85rem;width:auto;display:inline-block;opacity:.7;">'
                        .'<strong style="font-weight:700;color:#1E3A5F;">LegalWeb</strong>'
                        .'</span>'
                        .'</div>'
                    );
                }

                // Marca dibujada en SVG; en las pantallas de acceso se arma con una animacion corta.
                return new HtmlString(view('filament.brand.marca')->render());
            })
            ->brandLogoHeight(fn (): string => auth()->user()?->firm?->logo_path ? '4rem' : '2.4rem')
            ->colors([
                'primary' => Color::Blue, // Escala de azul con buen contraste; su tono 500 equivale al acento de marca #3A86FF.
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            // Analytics del panel sin datos de clientes: solo la ruta (con los numeros de registro ocultos)
            // y un titulo generico, porque el titulo de un caso lleva el nombre del cliente.
            ->renderHook(
                'panels::head.end',
                fn () => new HtmlString('<script async src="https://www.googletagmanager.com/gtag/js?id=G-2Q7KJTB5MT"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());function lwPage(){return {page_location:location.origin+location.pathname.replace(/\/\d+(?=\/|$)/g,"/:id"),page_title:"LegalWeb panel"};}gtag("config","G-2Q7KJTB5MT",Object.assign({send_page_view:false},lwPage()));document.addEventListener("livewire:navigated",function(){var p=lwPage();gtag("set",p);gtag("event","page_view",p);});</script><meta name="csrf-token" content="'.csrf_token().'">'),
            )
            ->renderHook(
                'panels::body.start',
                fn () => view('filament.panel-polish'),
            )
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.tour'),
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn () => view('filament.login-google-button'),
            )
            ->renderHook(
                'panels::auth.register.form.before',
                fn () => view('filament.register-beta-notice'),
            )
            ->renderHook(
                'panels::sidebar.footer',
                fn () => view('filament.sidebar-legal-links'),
            )
            ->plugins([
                FilamentApexChartsPlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PlanOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureOnboardingCompleted::class,
                EnforceSubscriptionStatus::class,
            ]);
    }
}
