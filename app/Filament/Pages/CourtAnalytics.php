<?php

namespace App\Filament\Pages;

use App\Services\CourtAnalyticsService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class CourtAnalytics extends Page
{
    protected string $view = 'filament.pages.court-analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Analitica de Despachos';

    protected static ?string $title = 'Analitica de Despachos';

    protected static ?string $slug = 'analitica-despachos';

    protected static ?int $navigationSort = 7;

    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(): array
    {
        $firmId = auth()->user()?->firm_id;

        return $firmId ? app(CourtAnalyticsService::class)->forFirm($firmId) : [];
    }
}
