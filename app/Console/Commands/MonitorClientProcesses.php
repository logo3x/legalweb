<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Scopes\FirmScope;
use App\Notifications\NewProcessesFoundNotification;
use App\Services\N8nWebhook;
use App\Services\TybaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:monitor-client-processes {--limit=25 : Clientes a revisar por ejecucion}')]
#[Description('Busca en la Rama Judicial procesos nuevos de los clientes con vigilancia activa (por cedula o NIT)')]
class MonitorClientProcesses extends Command
{
    public function handle(TybaService $tyba): int
    {
        // Los menos revisados primero: si hay muchos clientes, se reparten entre noches.
        $clients = Client::withoutGlobalScope(FirmScope::class)
            ->with(['user'])
            ->where('process_monitoring_enabled', true)
            ->where('is_demo', false)
            ->whereNotNull('document_number')
            ->where('document_number', '!=', '')
            ->orderByRaw('process_monitoring_last_at IS NOT NULL')
            ->orderBy('process_monitoring_last_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $alerts = 0;

        foreach ($clients as $client) {
            $found = $tyba->searchByDocument((string) $client->document_number, $client->ramaDocumentTypeCode());

            $imported = LegalCase::withoutGlobalScopes()
                ->where('firm_id', $client->firm_id)
                ->whereNotNull('external_case_number')
                ->pluck('external_case_number')
                ->map(fn (string $radicado): string => preg_replace('/\D/', '', $radicado))
                ->all();

            $known = $client->process_monitoring_known ?? [];

            $new = collect($found)
                ->filter(fn (array $p): bool => filled($p['radicado'] ?? null))
                ->reject(fn (array $p): bool => in_array(preg_replace('/\D/', '', $p['radicado']), $imported, true)
                    || in_array($p['radicado'], $known, true))
                ->values()
                ->all();

            $client->forceFill([
                'process_monitoring_last_at' => now(),
                'process_monitoring_known' => array_values(array_unique(array_merge($known, array_column($found, 'radicado')))),
            ])->save();

            if ($new === []) {
                continue;
            }

            $alerts++;
            $recipient = $client->user ?? Firm::find($client->firm_id)?->users()->whereIn('role', ['admin', 'superadmin'])->first();
            $recipient?->notify(new NewProcessesFoundNotification($client, $new));

            app(N8nWebhook::class)->dispatch('client.new_processes', [
                'firm' => N8nWebhook::firmData(Firm::find($client->firm_id)),
                'client_id' => $client->id,
                'processes' => array_map(fn (array $p): array => [
                    'radicado' => $p['radicado'],
                    'despacho' => $p['despacho'] ?? '',
                    'fecha' => $p['fecha'] ?? '',
                ], $new),
            ], $client->firm_id);

            $this->info("{$client->full_name}: ".count($new).' proceso(s) nuevo(s)');
        }

        $this->info("Clientes revisados: {$clients->count()} | con procesos nuevos: {$alerts}");

        return self::SUCCESS;
    }
}
