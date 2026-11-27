<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PlanOverview extends Widget
{
    protected string $view = 'filament.widgets.plan-overview';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    /**
     * Se muestra apenas abre el escritorio, sin esperar a que cargue el resto.
     */
    protected static bool $isLazy = false;

    /**
     * Solo cuando hay algo que decidir: prueba, plan Gratuito o cerca del limite de casos.
     * En un plan pagado holgado es ruido en el Escritorio (el detalle esta en Planes).
     */
    public static function canView(): bool
    {
        $limits = auth()->user()?->firm?->planLimits();

        if (! $limits) {
            return false;
        }

        if (in_array($limits->tier(), ['trial', 'free'], true)) {
            return true;
        }

        $max = $limits->maxActiveCases();

        return $max !== null && $limits->activeCasesCount() >= $max * 0.8;
    }
}
