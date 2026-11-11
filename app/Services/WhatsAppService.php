<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envio de mensajes de WhatsApp via la API REST de Twilio.
 *
 * Los mensajes proactivos (fuera de la ventana de 24h) solo llegan si usan
 * una Content Template aprobada por Meta. Si la plantilla no esta
 * configurada se envia el texto libre, que solo llega cuando el
 * destinatario escribio al numero en las ultimas 24h.
 */
class WhatsAppService
{
    public function isConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.auth_token'))
            && filled(config('services.twilio.whatsapp_from'));
    }

    public function hasTemplate(string $templateKey): bool
    {
        return filled(config('services.twilio.templates')[$templateKey] ?? null);
    }

    /**
     * @param  array<int, string>  $variables  Variables posicionales de la plantilla (1..n).
     */
    public function sendTemplate(string $to, string $templateKey, array $variables, string $fallbackText): bool
    {
        $contentSid = config('services.twilio.templates')[$templateKey] ?? null;

        if (! $contentSid) {
            return $this->send($to, $fallbackText);
        }

        $normalized = [];
        foreach ($variables as $position => $value) {
            $normalized[(string) $position] = mb_substr((string) $value, 0, 500);
        }

        return $this->dispatch($to, [
            'ContentSid' => $contentSid,
            'ContentVariables' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function send(string $to, string $message): bool
    {
        return $this->dispatch($to, ['Body' => mb_substr($message, 0, 1500)]);
    }

    /**
     * Normaliza un numero colombiano o internacional a formato E.164.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '57'.$digits;
        }

        if (strlen($digits) < 11 || strlen($digits) > 15) {
            return null;
        }

        return '+'.$digits;
    }

    /**
     * @param  array<string, string>  $params
     */
    private function dispatch(string $to, array $params): bool
    {
        if (! $this->isConfigured()) {
            Log::info('WhatsApp: Twilio no configurado, mensaje omitido');

            return false;
        }

        $phone = self::normalizePhone($to);

        if (! $phone) {
            Log::warning('WhatsApp: numero invalido', ['to' => $to]);

            return false;
        }

        $sid = config('services.twilio.sid');
        $from = (string) config('services.twilio.whatsapp_from');

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, config('services.twilio.auth_token'))
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", array_merge($params, [
                    'From' => str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from,
                    'To' => 'whatsapp:'.$phone,
                ]));

            if ($response->failed()) {
                Log::error('WhatsApp: Twilio rechazo el mensaje', [
                    'to' => $phone,
                    'status' => $response->status(),
                    'code' => $response->json('code'),
                    'message' => $response->json('message'),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp: error de conexion con Twilio: '.$e->getMessage(), ['to' => $phone]);

            return false;
        }
    }
}
