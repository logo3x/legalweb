<?php

namespace App\Jobs;

use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Notifications\TybaSyncNotification;
use App\Services\TybaService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCaseActuaciones implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(
        public LegalCase $case,
    ) {}

    public function handle(TybaService $tyba): void
    {
        $radicado = $this->case->external_case_number;

        if (! $radicado) {
            return;
        }

        // Verificar créditos de la firma
        $firm = $this->case->user?->firm;

        if (! $firm) {
            return;
        }

        $plan = $firm->activeSubscription?->plan;
        $maxQueries = $plan?->max_tyba_queries ?? 0;

        if ($maxQueries > 0 && $firm->tyba_queries_used >= $maxQueries) {
            Log::info('Tyba sync: firma sin creditos', ['firm' => $firm->id, 'case' => $this->case->id]);

            return;
        }

        // Consultar Tyba
        $actuaciones = $tyba->consultarProceso($radicado);

        if ($actuaciones === null) {
            Log::warning('Tyba sync: consulta fallida', ['case' => $this->case->id]);

            return;
        }

        // Incrementar contador de consultas
        $firm->increment('tyba_queries_used');

        // Actualizar última sincronización
        $this->case->update(['last_tyba_sync' => now()]);

        $newCount = 0;

        foreach ($actuaciones as $actuacion) {
            $date = $this->parseDate($actuacion['date']);

            if (! $date) {
                continue;
            }

            // Verificar si ya existe
            $exists = CaseEvent::where('legal_case_id', $this->case->id)
                ->where('event_date', $date)
                ->where('title', $actuacion['description'])
                ->exists();

            if ($exists) {
                continue;
            }

            $event = CaseEvent::create([
                'legal_case_id' => $this->case->id,
                'title' => $actuacion['description'],
                'event_date' => $date,
                'event_type' => 'actuacion',
                'description' => "Sincronizado automaticamente desde la Rama Judicial (Tyba). Radicado: {$radicado}",
                'user_id' => $this->case->user_id,
            ]);

            $newCount++;
        }

        if ($newCount > 0) {
            Log::info("Tyba sync: {$newCount} nuevas actuaciones", ['case' => $this->case->id]);

            // Notificar al abogado
            $client = $this->case->client;
            $lastEvent = CaseEvent::where('legal_case_id', $this->case->id)
                ->latest('event_date')
                ->first();

            if ($lastEvent && $this->case->user) {
                $this->case->user->notify(new TybaSyncNotification(
                    $this->case,
                    $newCount,
                ));
            }
        }
    }

    private function parseDate(string $date): ?Carbon
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/Y H:i:s', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($date));
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }
}
