<?php

namespace App\Console\Commands;

use App\Models\CaseEvent;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Scopes\FirmScope;
use App\Models\User;
use App\Notifications\VerificationReportNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Signature('app:send-verification-reports {--force : Enviar aunque ya se haya enviado esta semana}')]
#[Description('Envia a cada abogado el informe de verificacion semanal de sus procesos (lunes)')]
class SendVerificationReports extends Command
{
    public function handle(): int
    {
        // Una vez por semana: si el cron corre varias veces el lunes, no se repite.
        $weekKey = 'verification-reports:'.now()->format('o-W');

        if (! $this->option('force') && ! Cache::add($weekKey, true, now()->addDays(7))) {
            $this->info('El informe de esta semana ya se envio.');

            return self::SUCCESS;
        }

        $cases = LegalCase::withoutGlobalScope(FirmScope::class)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->where('is_demo', false)
            ->whereNotNull('user_id')
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->get();

        $newEvents = CaseEvent::whereIn('legal_case_id', $cases->pluck('id'))
            ->where('event_type', 'actuacion')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('legal_case_id, COUNT(*) as total')
            ->groupBy('legal_case_id')
            ->pluck('total', 'legal_case_id');

        $syncDays = [];
        $sent = 0;

        foreach ($cases->groupBy('user_id') as $userId => $userCases) {
            $user = User::find($userId);

            if (! $user || ! $user->email) {
                continue;
            }

            $rows = $userCases->map(function (LegalCase $case) use ($newEvents, &$syncDays): array {
                // Plan Gratuito: revision semanal; el resto, diaria. Un dia de margen.
                $syncDays[$case->firm_id] ??= Firm::find($case->firm_id)?->planLimits()->syncEveryDays() ?? 1;
                $staleBefore = now()->subDays($syncDays[$case->firm_id] + 1);

                [$state, $detail] = match (true) {
                    $case->sync_failed_at !== null => ['error', "No se pudo consultar desde el {$case->sync_failed_at->format('d/m/Y H:i')}: {$case->sync_error}"],
                    $case->last_tyba_sync === null => ['stale', 'Todavía no se ha consultado'],
                    $case->last_tyba_sync->lt($staleBefore) => ['stale', 'Última consulta exitosa el '.$case->last_tyba_sync->format('d/m/Y H:i')],
                    default => ['ok', 'Consultado el '.$case->last_tyba_sync->format('d/m/Y H:i')],
                };

                return [
                    'case_number' => (string) $case->case_number,
                    'radicado' => (string) $case->external_case_number,
                    'title' => (string) $case->title,
                    'state' => $state,
                    'detail' => $detail,
                    'new_events' => (int) ($newEvents[$case->id] ?? 0),
                ];
            })
                ->sortBy(fn (array $row): string => ['error' => '0', 'stale' => '1', 'ok' => '2'][$row['state']].$row['case_number'])
                ->values()
                ->all();

            $summary = [
                'total' => count($rows),
                'ok' => count(array_filter($rows, fn (array $r): bool => $r['state'] === 'ok')),
                'error' => count(array_filter($rows, fn (array $r): bool => $r['state'] === 'error')),
                'stale' => count(array_filter($rows, fn (array $r): bool => $r['state'] === 'stale')),
                'new_events' => array_sum(array_column($rows, 'new_events')),
            ];

            try {
                $user->notify(new VerificationReportNotification($rows, $summary));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Informe de verificacion no enviado', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Informes de verificacion enviados: {$sent}");

        return self::SUCCESS;
    }
}
