<?php

namespace App\Filament\Pages;

use App\Services\GeneratedFileStore;
use App\Services\ReportService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes y Analytics';

    protected static ?int $navigationSort = 6;

    public function getReportData(): array
    {
        $firm = auth()->user()->firm;

        if (! $firm) {
            return [];
        }

        return app(ReportService::class)->getFirmReport($firm);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $data = $this->getReportData();

                    $pdf = Pdf::loadView('reports.firm-report', $data)
                        ->setPaper('letter')
                        ->setOption('isHtml5ParserEnabled', true);

                    $fileName = 'reporte_'.str_replace(' ', '_', strtolower($data['firm']->name)).'_'.now()->format('Y_m_d').'.pdf';
                    $path = app(GeneratedFileStore::class)->pathFor(auth()->user()->firm_id, $fileName);

                    $pdf->save($path);

                    $this->js('window.location.href = '.json_encode(route('download.file', basename($path))));
                }),
        ];
    }
}
