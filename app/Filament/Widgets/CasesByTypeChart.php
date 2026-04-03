<?php

namespace App\Filament\Widgets;

use App\Models\CaseType;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CasesByTypeChart extends ApexChartWidget
{
    protected static ?string $chartId = 'casesByTypeChart';

    protected static ?string $heading = 'Casos por Tipo de Proceso';

    protected static ?int $sort = 2;

    protected function getOptions(): array
    {
        $firmId = auth()->user()->firm_id;

        $types = CaseType::withCount(['legalCases' => fn ($q) => $q->where('firm_id', $firmId)])
            ->having('legal_cases_count', '>', 0)
            ->get();

        return [
            'chart' => ['type' => 'donut', 'height' => 300],
            'series' => $types->pluck('legal_cases_count')->toArray(),
            'labels' => $types->pluck('name')->toArray(),
            'colors' => $types->pluck('color')->toArray(),
            'legend' => ['position' => 'bottom'],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'labels' => [
                            'show' => true,
                            'total' => ['show' => true, 'label' => 'Total'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
