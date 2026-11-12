<?php

namespace App\Filament\Pages;

use App\Models\Plan;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
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
}
