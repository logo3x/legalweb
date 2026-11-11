<?php

namespace App\Services;

use App\Jobs\SendWebhookDelivery;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Avisos de LegalWeb hacia n8n.
 *
 * Cada evento queda registrado en webhook_deliveries y se envia por la cola
 * (la procesa el cron "queue" cada minuto), con firma HMAC y reintentos.
 * Si N8N_WEBHOOK_URL no esta configurada, no se registra ni se envia nada.
 */
class N8nWebhook
{
    /**
     * Eventos disponibles y su descripcion para el panel.
     *
     * @var array<string, string>
     */
    public const EVENTS = [
        'firm.registered' => 'Se registro una firma nueva',
        'member.joined' => 'Un colaborador acepto la invitacion a una firma',
        'subscription.activated' => 'Se aprobo un pago y se activo el plan',
        'subscription.stage_changed' => 'Cambio la etapa de vencimiento o impago del plan',
        'case.created' => 'Se creo un caso (no incluye los de ejemplo)',
        'case.status_changed' => 'Cambio el estado de un caso',
        'case.judicial_update' => 'La Rama Judicial trajo actuaciones nuevas o cambios del proceso',
        'portal.accepted' => 'Un cliente acepto los terminos y entro al portal',
        'document.client_ready' => 'Un cliente envio o aviso que tiene listo un documento',
        'client.new_processes' => 'La vigilancia por documento encontro procesos nuevos de un cliente',
        'test.ping' => 'Mensaje de prueba enviado desde el panel',
    ];

    public function isConfigured(): bool
    {
        return filled(config('services.n8n.webhook_url'));
    }

    public function isEnabled(string $event): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if ($event === 'test.ping') {
            return true;
        }

        $events = array_map('trim', explode(',', (string) config('services.n8n.events', '*')));

        return in_array('*', $events, true) || in_array($event, $events, true);
    }

    /**
     * Registra el evento y lo encola. Nunca lanza excepciones: un fallo del
     * aviso no debe romper el registro, el pago o la sincronizacion.
     *
     * @param  array<string, mixed>  $data
     */
    public function dispatch(string $event, array $data, ?int $firmId = null): ?WebhookDelivery
    {
        try {
            if (! $this->isEnabled($event) || ! Schema::hasTable('webhook_deliveries')) {
                return null;
            }

            $uuid = (string) Str::uuid();

            $delivery = WebhookDelivery::create([
                'uuid' => $uuid,
                'event' => $event,
                'firm_id' => $firmId,
                'payload' => [
                    'id' => $uuid,
                    'event' => $event,
                    'occurred_at' => now()->toIso8601String(),
                    'source' => 'legalweb',
                    'data' => $data,
                ],
                'status' => WebhookDelivery::STATUS_PENDING,
            ]);

            SendWebhookDelivery::dispatch($delivery->id);

            return $delivery;
        } catch (\Throwable $e) {
            Log::warning('n8n: no se pudo registrar el evento '.$event.': '.$e->getMessage());

            return null;
        }
    }

    /**
     * Datos de la firma que viajan en los eventos.
     *
     * @return array<string, mixed>|null
     */
    public static function firmData(?Firm $firm): ?array
    {
        if (! $firm) {
            return null;
        }

        return [
            'id' => $firm->id,
            'name' => $firm->name,
            'email' => $firm->email,
            'phone' => $firm->phone,
            'city' => $firm->city,
            'onboarding_completed' => (bool) $firm->onboarding_completed,
            'created_at' => $firm->created_at?->toIso8601String(),
        ];
    }

    /**
     * Datos del caso que viajan en los eventos. No incluye datos personales
     * del cliente (Ley 1581): solo identificadores y estado.
     *
     * @return array<string, mixed>
     */
    public static function caseData(LegalCase $case): array
    {
        return [
            'id' => $case->id,
            'case_number' => $case->case_number,
            'external_case_number' => $case->external_case_number,
            'status' => $case->status,
            'case_type' => $case->caseType?->name,
            'court' => $case->court,
            'url' => url("/admin/legal-cases/{$case->id}"),
        ];
    }

    public static function sign(string $body, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $body, $secret);
    }
}
