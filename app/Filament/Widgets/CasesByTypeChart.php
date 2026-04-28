<?php

namespace App\Filament\Widgets;

use App\Models\CaseType;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CasesByTypeChart extends ApexChartWidget
{
    protected static ?string $chartId = 'casesByTypeChart';

    protected static ?string $heading = 'Casos por Tipo de Proceso';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $firmId = auth()->user()?->firm_id;
        if (! $firmId) {
            return false;
        }

        return CaseType::withCount(['legalCases' => fn ($q) => $q->where('firm_id', $firmId)])
            ->having('legal_cases_count', '>', 0)
            ->exists();
    }

    protected function getOptions(): array
    {
        $firmId = auth()->user()?->firm_id;

        $types = CaseType::withCount(['legalCases' => fn ($q) => $q->where('firm_id', $firmId)])
            ->having('legal_cases_count', '>', 0)
            ->get()
            ->filter(fn ($t) => ! empty($t->name) && (int) $t->legal_cases_count > 0)
            ->values();

        $series = $types->pluck('legal_cases_count')->map(fn ($v) => (int) $v)->values()->toArray();
        $labels = $types->pluck('name')->map(fn ($v) => (string) $v)->values()->toArray();
        $colors = $types->pluck('color')->map(fn ($v) => $v ?: '#6B7280')->values()->toArray();

        if (empty($series)) {
            $series = [1];
            $labels = ['Sin datos'];
            $colors = ['#E5E7EB'];
        }

        return [
            'chart' => ['type' => 'donut', 'height' => 300],
            'series' => $series,
            'labels' => $labels,
            'colors' => $colors,
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
