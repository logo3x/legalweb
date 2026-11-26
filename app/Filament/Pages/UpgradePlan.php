<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use App\Services\WompiRecurringService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use UnitEnum;

class UpgradePlan extends Page
{
    protected string $view = 'filament.pages.upgrade-plan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Mi Plan';

    protected static ?string $title = 'Planes y Precios';

    protected static ?string $slug = 'planes';

    protected static ?int $navigationSort = 32;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    public function getPlans(): Collection
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getCurrentPlan(): ?Plan
    {
        return auth()->user()->firm?->activeSubscription?->plan;
    }

    /**
     * La seccion de pago automatico: solo administradores de firmas con plan de pago.
     */
    public function canManageAutoRenew(): bool
    {
        $user = auth()->user();

        return WompiRecurringService::enabled()
            && (bool) $user?->isAdmin()
            && $user->firm !== null
            && $user->firm->planLimits()->tier() === 'paid';
    }

    /**
     * Contratos de Wompi que el usuario acepta antes de registrar la tarjeta (null si Wompi no responde).
     *
     * @return array{acceptance_token: string, acceptance_permalink: string, personal_auth_token: string, personal_auth_permalink: string}|null
     */
    public function getWompiAcceptance(): ?array
    {
        try {
            return app(WompiRecurringService::class)->acceptanceTokens();
        } catch (\Throwable $e) {
            Log::warning('Wompi: contratos de aceptacion no disponibles', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Recibe solo el token de la tarjeta (el navegador la tokenizo directamente con Wompi).
     */
    public function registerCard(string $cardToken, string $acceptanceToken, string $personalAuthToken, bool $authorizesCharges): void
    {
        abort_unless($this->canManageAutoRenew(), 403);

        if (! $authorizesCharges) {
            Notification::make()->title('Debe autorizar el cobro automático')->warning()->send();

            return;
        }

        if (! RateLimiter::attempt('register-card:'.auth()->id(), 5, fn (): bool => true, 3600)) {
            Notification::make()->title('Demasiados intentos')->body('Espere un rato antes de volver a intentarlo.')->warning()->send();

            return;
        }

        $firm = auth()->user()->firm;

        try {
            app(WompiRecurringService::class)->registerCard($firm, $cardToken, (string) auth()->user()->email, $acceptanceToken, $personalAuthToken);
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo registrar la tarjeta')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()
            ->title('Pago automático activado')
            ->body('Cobraremos la renovación a su '.$firm->card_brand.' terminada en '.$firm->card_last_four.' el día anterior al vencimiento.')
            ->success()
            ->send();
    }

    public function removeCard(): void
    {
        abort_unless($this->canManageAutoRenew(), 403);

        app(WompiRecurringService::class)->removeCard(auth()->user()->firm);

        Notification::make()->title('Pago automático desactivado')->body('Le avisaremos antes del vencimiento para que pague con el medio que prefiera.')->success()->send();
    }
}
