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

        // Paso 1: Acceder directamente a frmConsultaProceso (sin captcha)
        $baseUrl = dirname($this->tybaUrl);
        $processUrl = $baseUrl.'/frmConsultaProceso.aspx?IdProceso='.$radicado;
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $response = Http::timeout(30)->withHeaders(['User-Agent' => $ua])->get($processUrl);

        if (! $response->successful() || ! str_contains($response->body(), 'del Proceso')) {
            Log::error('Tyba: no se pudo acceder al proceso', ['radicado' => $radicado]);

            return null;
        }

        $pageHtml = $response->body();

        // Si las actuaciones ya están en la respuesta inicial
        if (str_contains($pageHtml, 'MainContent_grdActuaciones')) {
            $actuaciones = $this->parseActuaciones($pageHtml, $radicado);
            if (! empty($actuaciones)) {
                return $actuaciones;
            }
        }

        // Paso 2: POST para cargar actuaciones via UpdatePanel
        $cookieJar = $response->cookies();
        $cookies = [];
        foreach ($cookieJar as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // Extraer todos los campos del form
        $formFields = [];
        preg_match_all('/<input[^>]*name="([^"]*)"[^>]*>/si', $pageHtml, $inputs, PREG_SET_ORDER);
        foreach ($inputs as $input) {
            if (preg_match('/\bdisabled\b/i', $input[0]) || preg_match('/type=["\']submit["\']/i', $input[0]) || preg_match('/type=["\']image["\']/i', $input[0])) {
                continue;
            }
            $value = '';
            if (preg_match('/value="([^"]*)"/', $input[0], $vm)) {
                $value = $vm[1];
            }
            $formFields[$input[1]] = $value;
        }

        // Selects no-disabled
        preg_match_all('/<select[^>]*name="([^"]*)"[^>]*>(.*?)<\/select>/si', $pageHtml, $selects, PREG_SET_ORDER);
        foreach ($selects as $select) {
            if (preg_match('/\bdisabled\b/i', $select[0])) {
                continue;
            }
            $value = '';
            if (preg_match('/selected="selected"[^>]*value="([^"]*)"/', $select[2], $sm)) {
                $value = $sm[1];
            } elseif (preg_match('/value="([^"]*)"[^>]*selected="selected"/', $select[2], $sm)) {
                $value = $sm[1];
            }
            $formFields[$select[1]] = $value;
        }

        // Simular click en "Consultar" del tab Actuaciones (submit normal, no __doPostBack)
        $formFields['ctl00$MainContent$btnConsultar'] = 'Consultar';
        $formFields['ctl00$MainContent$ddlCicloBusqueda'] = '0';
        // ScriptManager para UpdatePanel async
        $formFields['ctl00$ctl09'] = 'ctl00$MainContent$UpdatePanel_Actuaciones|ctl00$MainContent$btnConsultar';

        $domain = parse_url($processUrl, PHP_URL_HOST);
        $postUrl = $baseUrl.'/frmConsultaProceso.aspx';

        $postResp = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => $ua,
                'Referer' => $processUrl,
                'X-MicrosoftAjax' => 'Delta=true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->withCookies($cookies, $domain)
            ->asForm()
            ->post($postUrl, $formFields);

        if ($postResp->successful()) {
            $postHtml = $postResp->body();
            Log::error('Tyba: POST actuaciones', [
                'size' => strlen($postHtml),
                'has_grid' => str_contains($postHtml, 'MainContent_grdActuaciones'),
                'snippet' => substr($postHtml, 0, 200),
            ]);

            if (str_contains($postHtml, 'MainContent_grdActuaciones')) {
                return $this->parseActuaciones($postHtml, $radicado);
            }

            // Si no funciono con async, intentar POST normal (sin ScriptManager/AJAX)
            unset($formFields['ctl00$ctl09']);
            $formFields['__EVENTTARGET'] = '';
            $formFields['__EVENTARGUMENT'] = '';
            $postResp2 = Http::timeout(30)
                ->withHeaders(['User-Agent' => $ua, 'Referer' => $processUrl])
                ->withCookies($cookies, $domain)
                ->asForm()
                ->post($postUrl, $formFields);

            if ($postResp2->successful()) {
                $postHtml2 = $postResp2->body();
                Log::error('Tyba: POST normal actuaciones', [
                    'size' => strlen($postHtml2),
                    'has_grid' => str_contains($postHtml2, 'MainContent_grdActuaciones'),
                ]);

                if (str_contains($postHtml2, 'MainContent_grdActuaciones')) {
                    return $this->parseActuaciones($postHtml2, $radicado);
                }
            }
        }

        Log::error('Tyba: no se pudieron cargar actuaciones', ['radicado' => $radicado]);

        return null;
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
            'action' => 'submit',
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

        // Extraer TODOS los campos del form (ASP.NET valida contra ViewState)
        $formFields = [];

        // Inputs (hidden, text, etc.) - solo los que no estan disabled
        preg_match_all('/<input[^>]*name="([^"]*)"[^>]*>/si', $html, $inputs, PREG_SET_ORDER);
        foreach ($inputs as $input) {
            $tag = $input[0];
            $name = $input[1];

            // Skip disabled
            if (preg_match('/\bdisabled\b/i', $tag)) {
                continue;
            }

            // Skip submit buttons (los manejaremos via __doPostBack)
            if (preg_match('/type=["\']submit["\']/i', $tag)) {
                continue;
            }

            $value = '';
            if (preg_match('/value="([^"]*)"/', $tag, $vm)) {
                $value = $vm[1];
            }
            $formFields[$name] = $value;
        }

        // Selects (dropdowns) - solo los que no estan disabled
        preg_match_all('/<select[^>]*name="([^"]*)"[^>]*>(.*?)<\/select>/si', $html, $selects, PREG_SET_ORDER);
        foreach ($selects as $select) {
            $tag = $select[0];
            $name = $select[1];
            $options = $select[2];

            if (preg_match('/\bdisabled\b/i', $tag)) {
                continue;
            }

            // Obtener valor del option selected
            $value = '';
            if (preg_match('/selected="selected"[^>]*value="([^"]*)"/', $options, $sm)) {
                $value = $sm[1];
            } elseif (preg_match('/value="([^"]*)"[^>]*selected="selected"/', $options, $sm)) {
                $value = $sm[1];
            }
            $formFields[$name] = $value;
        }

        // Textareas
        preg_match_all('/<textarea[^>]*name="([^"]*)"[^>]*>(.*?)<\/textarea>/si', $html, $textareas, PREG_SET_ORDER);
        foreach ($textareas as $ta) {
            if (! preg_match('/\bdisabled\b/i', $ta[0])) {
                $formFields[$ta[1]] = $ta[2] ?? '';
            }
        }

        if (empty($formFields['__VIEWSTATE'])) {
            return null;
        }

        return [
            'form_fields' => $formFields,
            'cookies' => $cookies,
        ];
    }

    private function extractHiddenField(string $html, string $fieldName): ?string
    {
        // Buscar por id="fieldName" ... value="..." (atributos en cualquier orden)
        $escaped = preg_quote($fieldName, '/');

        // Patron 1: id antes de value
        if (preg_match('/id="'.$escaped.'"[^>]*value="([^"]*)"/si', $html, $matches)) {
            return $matches[1];
        }

        // Patron 2: value antes de id
        if (preg_match('/value="([^"]*)"[^>]*id="'.$escaped.'"/si', $html, $matches)) {
            return $matches[1];
        }

        // Patron 3: buscar por name en vez de id
        if (preg_match('/name="'.$escaped.'"[^>]*value="([^"]*)"/si', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return array{html: ?string, captcha_required: bool}
     */
    private function submitQuery(string $radicado, ?string $captchaToken, array $session): array
    {
        // Incluir TODOS los campos del formulario
        $formData = $session['form_fields'];

        // ASP.NET __doPostBack: el boton no se envia como valor,
        // sino via __EVENTTARGET (como lo hace el JavaScript del form)
        $formData['__EVENTTARGET'] = 'ctl00$MainContent$btnConsultar';
        $formData['__EVENTARGUMENT'] = '';

        // Campo del radicado + tab activa (1 = Proceso)
        $formData['ctl00$MainContent$txtCodigoProceso'] = $radicado;
        $formData['ctl00$MainContent$txttp'] = '1';

        // Token de captcha
        if ($captchaToken) {
            $formData['recaptchaResponse'] = $captchaToken;
            $formData['g-recaptcha-response'] = $captchaToken;
        }

        Log::warning('Tyba: enviando consulta', [
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

        Log::warning('Tyba: respuesta recibida', [
            'status' => $status,
            'body_length' => strlen($body),
            'con_captcha' => $captchaToken !== null,
            'tiene_actuaciones' => str_contains($body, 'grdActuaciones'),
            'tiene_proceso' => str_contains($body, 'del Proceso'),
        ]);

        if (! $response->successful()) {
            Log::warning('Tyba: HTTP error', ['status' => $status]);

            return ['html' => null, 'captcha_required' => false];
        }

        // Si Tyba dice que el captcha fallo, marcarlo para reintento con 2Captcha
        if (str_contains($body, 'El valor de la Capcha no coincide') || str_contains($body, 'captcha no es válido')) {
            Log::warning('Tyba: captcha requerido por el servidor');

            return ['html' => null, 'captcha_required' => true];
        }

        // Verificar que la respuesta tiene datos del proceso
        if (str_contains($body, 'grdActuaciones') || str_contains($body, 'del Proceso')) {
            Log::warning('Tyba: datos del proceso obtenidos');

            return ['html' => $body, 'captcha_required' => false];
        }

        // Respuesta sin datos - loguear snippet para debug
        Log::warning('Tyba: respuesta sin datos del proceso', [
            'radicado' => $radicado,
            'snippet' => substr(strip_tags($body), 0, 500),
        ]);

        return ['html' => null, 'captcha_required' => false];
    }

    private function parseActuaciones(string $html, string $radicado): array
    {
        $actuaciones = [];

        // Buscar tabla MainContent_grdActuaciones
        if (! preg_match('/<table[^>]*id="MainContent_grdActuaciones"[^>]*>(.*?)<\/table>/si', $html, $tableMatch)) {
            Log::error('Tyba: tabla MainContent_grdActuaciones no encontrada', ['radicado' => $radicado]);

            return [];
        }

        $tableHtml = $tableMatch[1];

        // Extraer filas (skip header)
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableHtml, $rows);

        if (empty($rows[1])) {
            return [];
        }

        // Columnas: (icono), Ciclo, Tipo Actuación, Fecha Actuación, Fecha Registro
        foreach (array_slice($rows[1], 1) as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cells);

            if (empty($cells[1]) || count($cells[1]) < 4) {
                continue;
            }

            $ciclo = strip_tags(trim($cells[1][1] ?? ''));
            $tipoActuacion = strip_tags(trim($cells[1][2] ?? ''));
            $fechaActuacion = strip_tags(trim($cells[1][3] ?? ''));
            $fechaRegistro = strip_tags(trim($cells[1][4] ?? ''));

            if (! $fechaActuacion || ! $tipoActuacion) {
                continue;
            }

            $description = $tipoActuacion.($ciclo ? " ({$ciclo})" : '');

            $actuaciones[] = [
                'date' => $fechaActuacion,
                'description' => $description,
                'attachments' => 0,
            ];
        }

        Log::error('Tyba: actuaciones encontradas', ['radicado' => $radicado, 'count' => count($actuaciones)]);

        return $actuaciones;
    }
}
