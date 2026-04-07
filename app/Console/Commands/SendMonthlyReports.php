<?php

namespace App\Console\Commands;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Notifications\MonthlyReportNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-monthly-reports')]
#[Description('Genera y envia reportes mensuales de casos a los clientes')]
class SendMonthlyReports extends Command
{
    public function handle(): void
    {
        $previousMonth = now()->subMonth();
        $startOfMonth = $previousMonth->copy()->startOfMonth();
        $endOfMonth = $previousMonth->copy()->endOfMonth();
        $periodo = $previousMonth->translatedFormat('F Y');

        // Obtener casos con reporte automatico habilitado
        $cases = LegalCase::withoutGlobalScopes()
            ->with(['client', 'user', 'user.firm', 'caseType', 'flowProgress.flowStep'])
            ->where('auto_report_enabled', true)
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->whereHas('client', fn ($q) => $q->whereNotNull('email')->where('email', '!=', ''))
            ->get();

        $this->info("Casos a reportar: {$cases->count()}");
        $sent = 0;

        foreach ($cases as $case) {
            $client = $case->client;
            $firm = $case->user?->firm;

            if (! $client?->email || ! $firm) {
                continue;
            }

            // Actuaciones del mes
            $actuaciones = CaseEvent::where('legal_case_id', $case->id)
                ->whereBetween('event_date', [$startOfMonth, $endOfMonth])
                ->orderBy('event_date')
                ->get();

            // Vencimientos pendientes
            $vencimientos = Reminder::where('legal_case_id', $case->id)
                ->where('is_completed', false)
                ->where('due_date', '>=', now())
                ->orderBy('due_date')
                ->limit(5)
                ->get();

            // Sincronizaciones del mes
            $syncCount = TybaSyncLog::where('legal_case_id', $case->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Flujo procesal
            $flowProgress = $case->flowProgress->sortBy('flowStep.order');

            $resumen = [
                'nuevas_actuaciones' => $actuaciones->count(),
                'recordatorios_pendientes' => $vencimientos->count(),
                'sincronizaciones' => $syncCount,
            ];

            // Generar PDF
            $pdf = Pdf::loadView('reports.monthly-case-report', [
                'case' => $case,
                'client' => $client,
                'firm' => $firm,
                'periodo' => $periodo,
                'generated_at' => now(),
                'actuaciones' => $actuaciones,
                'vencimientos' => $vencimientos,
                'flowProgress' => $flowProgress,
                'resumen' => $resumen,
            ])->setPaper('letter');

            $fileName = "reporte_{$case->case_number}_{$previousMonth->format('Y_m')}.pdf";
            $path = storage_path("app/public/generated/{$fileName}");

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $pdf->save($path);

            // Enviar al cliente
            $client->notify(new MonthlyReportNotification($case, $path, $periodo));

            $sent++;
            $this->info("Enviado: {$case->case_number} -> {$client->email}");
        }

        $this->info("Total reportes enviados: {$sent}");
    }
}
