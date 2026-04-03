<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PlanOverview extends Widget
{
    protected string $view = 'filament.widgets.plan-overview';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';
}
