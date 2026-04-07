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

    public int $timeout = 60;

    public int $tries = 2;

    public function __construct(
        public LegalCase $case,
    ) {}

    public function handle(TybaService $tyba): void
    {
        $radicado = $this->case->external_case_number;

        if (! $radicado) {
            return;
        }

        $info = $tyba->extractProcessInfo($radicado);

        if (! $info) {
            Log::warning('Tyba sync: consulta fallida', ['case' => $this->case->id, 'radicado' => $radicado]);
            $this->case->update(['last_tyba_sync' => now()]);

            return;
        }

        // Actualizar datos del caso
        $updates = [
            'last_tyba_sync' => now(),
            'tyba_data' => collect($info)->except(['sujetos', 'actuaciones'])->toArray(),
        ];

        if (! empty($info['despacho'])) {
            $updates['court'] = $info['despacho'];
        }
        if (! empty($info['ponente'])) {
            $updates['judge'] = mb_convert_case(mb_strtolower($info['ponente']), MB_CASE_TITLE, 'UTF-8');
        }

        $this->case->update($updates);

        // Registrar actuaciones nuevas
        $newCount = 0;

        foreach ($info['actuaciones'] as $a) {
            $date = $this->parseDate($a['fecha']);

            if (! $date) {
                continue;
            }

            $title = $a['tipo'].($a['ciclo'] ? " ({$a['ciclo']})" : '');

            $exists = CaseEvent::where('legal_case_id', $this->case->id)
                ->where('event_date', $date)
                ->where('title', $title)
                ->exists();

            if ($exists) {
                continue;
            }

            CaseEvent::create([
                'legal_case_id' => $this->case->id,
                'title' => $title,
                'event_date' => $date,
                'event_type' => 'actuacion',
                'description' => ($a['anotacion'] ?? '').($a['anotacion'] ? ' | ' : '')."Sincronizado automaticamente. Radicado: {$radicado}",
                'user_id' => $this->case->user_id,
            ]);

            $newCount++;
        }

        Log::info('Tyba sync completado', [
            'case' => $this->case->case_number,
            'radicado' => $radicado,
            'total_actuaciones' => count($info['actuaciones']),
            'nuevas' => $newCount,
        ]);

        // Notificar al abogado si hay nuevas actuaciones
        if ($newCount > 0 && $this->case->user) {
            $this->case->user->notify(new TybaSyncNotification(
                $this->case,
                $newCount,
            ));
        }
    }

    private function parseDate(string $date): ?Carbon
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];

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
