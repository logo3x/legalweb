<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\PlanOverview;
use App\Http\Middleware\EnsureOnboardingCompleted;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
            ->registration()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->brandName('LegalWeb')
            ->brandLogo(function () {
                $firm = auth()->user()?->firm;

                if ($firm && $firm->logo_path) {
                    return new HtmlString(
                        '<img src="'.e($firm->logo_url).'" alt="'.e($firm->name).'" '
                        .'style="height:2.25rem;width:auto;max-width:200px;object-fit:contain;display:block;">'
                    );
                }

                return new HtmlString(
                    '<div style="display:flex;align-items:center;gap:.55rem;line-height:1;">'
                    .'<img src="'.asset('images/logo-icon.svg').'" alt="LegalWeb" style="height:2rem;width:auto;display:block;">'
                    .'<span style="font-family:Poppins,Inter,sans-serif;font-weight:700;font-size:1.05rem;color:#1E3A5F;letter-spacing:-.01em;">LegalWeb</span>'
                    .'</div>'
                );
            })
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => Color::hex('#3A86FF'),
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->renderHook(
                'panels::head.end',
                fn () => new HtmlString('<script async src="https://www.googletagmanager.com/gtag/js?id=G-2Q7KJTB5MT"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","G-2Q7KJTB5MT");</script><meta name="csrf-token" content="'.csrf_token().'">'),
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
            ]);
    }
}
