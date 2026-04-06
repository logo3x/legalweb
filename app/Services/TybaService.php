<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TybaService
{
    private string $tybaUrl;

    private string $sitekey;

    public function __construct()
    {
        $this->tybaUrl = config('services.tyba.url');
        $this->sitekey = config('services.tyba.sitekey');
    }

    /**
     * Consultar actuaciones de un proceso por radicado.
     *
     * @return array|null Lista de actuaciones o null si falla
     */
    public function consultarProceso(string $radicado): ?array
    {
        $radicado = preg_replace('/[^0-9]/', '', $radicado);

        if (strlen($radicado) < 20) {
            Log::warning('Tyba: radicado invalido', ['radicado' => $radicado]);

            return null;
        }

        // Paso 1: Intentar SIN captcha primero (Tyba no siempre lo exige)
        $session = $this->initSession();

        if (! $session) {
            Log::error('Tyba: no se pudo iniciar sesion');

            return null;
        }

        Log::info('Tyba: intento 1 - sin captcha', ['radicado' => $radicado]);
        $html = $this->submitQuery($radicado, null, $session);

        // Paso 2: Si fallo, intentar con captcha v3
        if (! $html) {
            Log::info('Tyba: intento 1 fallo, probando con captcha v3', ['radicado' => $radicado]);
            $captchaToken = $this->resolveCaptcha();

            if ($captchaToken) {
                $session = $this->initSession();
                if ($session) {
                    $html = $this->submitQuery($radicado, $captchaToken, $session);
                }
            }
        }

        if (! $html) {
            Log::error('Tyba: no se obtuvo respuesta de ningún intento', ['radicado' => $radicado]);

            return null;
        }

        // Paso 4: Extraer actuaciones del HTML
        return $this->parseActuaciones($html, $radicado);
    }

    private function resolveCaptcha(): ?string
    {
        $apiKey = config('services.twocaptcha.api_key');

        if (! $apiKey) {
            Log::error('Tyba: 2Captcha API key no configurada');

            return null;
        }

        // Enviar captcha a 2Captcha (reCAPTCHA v3 score-based)
        $response = Http::timeout(10)->get('https://2captcha.com/in.php', [
            'key' => $apiKey,
            'method' => 'userrecaptcha',
            'googlekey' => $this->sitekey,
            'pageurl' => $this->tybaUrl,
            'version' => 'v3',
            'action' => 'consultar',
            'min_score' => 0.3,
            'json' => 1,
        ]);

        if (! $response->successful() || $response->json('status') !== 1) {
            Log::error('Tyba: error enviando captcha', ['response' => $response->body()]);

            return null;
        }

        $requestId = $response->json('request');

        // Esperar resolución (max 120 segundos)
        for ($i = 0; $i < 24; $i++) {
            sleep(5);

            $result = Http::timeout(10)->get('https://2captcha.com/res.php', [
                'key' => $apiKey,
                'action' => 'get',
                'id' => $requestId,
                'json' => 1,
            ]);

            if ($result->json('status') === 1) {
                return $result->json('request');
            }

            if ($result->json('request') !== 'CAPCHA_NOT_READY') {
                Log::error('Tyba: error resolviendo captcha', ['response' => $result->body()]);

                return null;
            }
        }

        Log::error('Tyba: timeout resolviendo captcha');

        return null;
    }

    private function initSession(): ?array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])
            ->get($this->tybaUrl);

        if (! $response->successful()) {
            return null;
        }

        $html = $response->body();

        // Extraer cookies como array simple name => value
        $cookieJar = $response->cookies();
        $cookies = [];
        foreach ($cookieJar as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // Extraer __VIEWSTATE y __EVENTVALIDATION
        $viewstate = $this->extractHiddenField($html, '__VIEWSTATE');
        $viewstateGenerator = $this->extractHiddenField($html, '__VIEWSTATEGENERATOR');
        $eventValidation = $this->extractHiddenField($html, '__EVENTVALIDATION');

        if (! $viewstate) {
            return null;
        }

        return [
            'viewstate' => $viewstate,
            'viewstate_generator' => $viewstateGenerator,
            'event_validation' => $eventValidation,
            'cookies' => $cookies,
        ];
    }

    private function extractHiddenField(string $html, string $fieldName): ?string
    {
        $pattern = '/id="'.preg_quote($fieldName, '/').'"\s+value="([^"]*)"/';
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function submitQuery(string $radicado, ?string $captchaToken, array $session): ?string
    {
        $formData = [
            '__VIEWSTATE' => $session['viewstate'],
            '__VIEWSTATEGENERATOR' => $session['viewstate_generator'] ?? '',
            '__EVENTVALIDATION' => $session['event_validation'] ?? '',
            'ctl00$MainContent$txtCodigoProceso' => $radicado,
            'ctl00$MainContent$btnConsultar' => 'Consultar',
        ];

        if ($captchaToken) {
            $formData['g-recaptcha-response'] = $captchaToken;
        }

        Log::info('Tyba: enviando consulta', [
            'radicado' => $radicado,
            'con_captcha' => $captchaToken !== null,
        ]);

        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $this->tybaUrl,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->withCookies($session['cookies'], parse_url($this->tybaUrl, PHP_URL_HOST))
            ->asForm()
            ->post($this->tybaUrl, $formData);

        $status = $response->status();
        $body = $response->body();
        $bodyLen = strlen($body);

        Log::info('Tyba: respuesta recibida', [
            'status' => $status,
            'body_length' => $bodyLen,
            'con_captcha' => $captchaToken !== null,
            'tiene_actuaciones' => str_contains($body, 'grdActuaciones'),
            'tiene_proceso' => str_contains($body, 'Información del Proceso'),
        ]);

        if (! $response->successful()) {
            Log::warning('Tyba: HTTP error', ['status' => $status]);

            return null;
        }

        // Solo rechazar si Tyba dice explicitamente que el captcha fallo
        if (str_contains($body, 'El valor de la Capcha no coincide')) {
            Log::warning('Tyba: captcha rechazado explicitamente', ['radicado' => $radicado]);

            return null;
        }

        // Verificar que la respuesta tiene contenido de proceso (no es solo el form vacio)
        if (str_contains($body, 'grdActuaciones') || str_contains($body, 'Información del Proceso')) {
            return $body;
        }

        Log::info('Tyba: respuesta no contiene datos del proceso', [
            'radicado' => $radicado,
            'snippet' => substr(strip_tags($body), 0, 500),
        ]);

        return null;
    }

    private function parseActuaciones(string $html, string $radicado): array
    {
        $actuaciones = [];

        // Buscar tabla de actuaciones
        if (! preg_match('/<table[^>]*id="[^"]*grdActuaciones[^"]*"[^>]*>(.*?)<\/table>/si', $html, $tableMatch)) {
            Log::info('Tyba: no se encontro tabla de actuaciones', ['radicado' => $radicado]);

            return [];
        }

        $tableHtml = $tableMatch[1];

        // Extraer filas (skip header)
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableHtml, $rows);

        if (empty($rows[1])) {
            return [];
        }

        foreach (array_slice($rows[1], 1) as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cells);

            if (empty($cells[1]) || count($cells[1]) < 2) {
                continue;
            }

            $date = strip_tags(trim($cells[1][0] ?? ''));
            $description = strip_tags(trim($cells[1][1] ?? ''));

            if (! $date || ! $description) {
                continue;
            }

            $actuaciones[] = [
                'date' => $date,
                'description' => $description,
                'attachments' => isset($cells[1][2]) ? (int) strip_tags(trim($cells[1][2])) : 0,
            ];
        }

        Log::info('Tyba: actuaciones encontradas', ['radicado' => $radicado, 'count' => count($actuaciones)]);

        return $actuaciones;
    }
}
