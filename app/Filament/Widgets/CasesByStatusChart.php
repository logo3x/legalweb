<?php

namespace App\Filament\Widgets;

use App\Models\LegalCase;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CasesByStatusChart extends ApexChartWidget
{
    protected static ?string $chartId = 'casesByStatusChart';

    protected static ?string $heading = 'Casos por Estado';

    protected static ?int $sort = 3;

    protected function getOptions(): array
    {
        $firmId = auth()->user()->firm_id;

        $statuses = LegalCase::where('firm_id', $firmId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [
            'abierto' => 'Abierto',
            'en_progreso' => 'En Progreso',
            'en_espera' => 'En Espera',
            'cerrado' => 'Cerrado',
            'archivado' => 'Archivado',
        ];

        $colors = [
            'abierto' => '#3B82F6',
            'en_progreso' => '#F59E0B',
            'en_espera' => '#6B7280',
            'cerrado' => '#10B981',
            'archivado' => '#EF4444',
        ];

        $data = $statuses->mapWithKeys(fn ($count, $status) => [
            $labels[$status] ?? $status => $count,
        ]);

        return [
            'chart' => ['type' => 'bar', 'height' => 300],
            'series' => [
                ['name' => 'Casos', 'data' => $data->values()->toArray()],
            ],
            'xaxis' => ['categories' => $data->keys()->toArray()],
            'colors' => collect($statuses->keys())->map(fn ($s) => $colors[$s] ?? '#6B7280')->toArray(),
            'plotOptions' => [
                'bar' => ['distributed' => true, 'borderRadius' => 4],
            ],
            'legend' => ['show' => false],
        ];
    }
}
