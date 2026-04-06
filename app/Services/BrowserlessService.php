<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrowserlessService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.browserless.api_key', '');
        $this->baseUrl = 'https://production-sfo.browserless.io';
    }

    /**
     * Ejecutar flujo completo en Tyba: buscar radicado → click resultado → obtener HTML del proceso.
     */
    public function fetchTybaProcess(string $radicado): ?string
    {
        if (! $this->apiKey) {
            Log::error('Browserless: API key no configurada');

            return null;
        }

        // Script Puppeteer que automatiza el flujo completo de Tyba
        $script = <<<'JS'
export default async function ({ page }) {
    const radicado = "RADICADO_PLACEHOLDER";

    await page.goto('https://procesojudicial.ramajudicial.gov.co/Justicia21/Administracion/Ciudadanos/frmConsulta', {
        waitUntil: 'networkidle2',
        timeout: 30000
    });

    await page.waitForSelector('#MainContent_txtCodigoProceso', { timeout: 10000 });
    await page.type('#MainContent_txtCodigoProceso', radicado, { delay: 50 });

    // Esperar reCAPTCHA
    await page.waitForFunction('typeof grecaptcha !== "undefined"', { timeout: 10000 }).catch(function() {});
    await page.evaluate(function() {
        return new Promise(function(resolve) {
            if (typeof grecaptcha === 'undefined') { resolve(); return; }
            grecaptcha.ready(function() {
                grecaptcha.execute('6Ldf8zAiAAAAAAq1LUwvTCwki5C6uuIg0zVw4of0', { action: 'submit' }).then(function(token) {
                    document.getElementById('recaptchaResponse').value = token;
                    resolve();
                }).catch(function() { resolve(); });
            });
        });
    });

    // Click Consultar
    await page.evaluate(function() {
        __doPostBack('ctl00\$MainContent\$btnConsultar', '');
    });

    // Esperar resultados
    await page.waitForSelector('[id*="grdProceso"]', { timeout: 15000 }).catch(function() {});

    var pageContent = await page.content();
    if (pageContent.includes('El valor de la Capcha no coincide')) {
        return { type: 'error', data: 'captcha_failed' };
    }

    // Click en la lupa
    try {
        await page.waitForSelector('input[title="Consultar registro"]', { timeout: 10000 });
        await page.click('input[title="Consultar registro"]');
    } catch (e) {
        return { type: 'error', data: 'no_results' };
    }

    // Esperar pagina del proceso
    await page.waitForSelector('#MainContent_txtCodigoProceso[value]', { timeout: 15000 }).catch(function() {});
    await page.waitForFunction('document.querySelector("#MainContent_txtCodigoProceso") && document.querySelector("#MainContent_txtCodigoProceso").value.length > 5', { timeout: 10000 }).catch(function() {});

    var html = await page.content();
    return { type: 'html', data: html, url: page.url() };
}
JS;

        $script = str_replace('RADICADO_PLACEHOLDER', $radicado, $script);

        Log::info('Browserless: iniciando flujo Tyba', ['radicado' => $radicado]);

        $response = Http::timeout(90)
            ->withHeaders(['Content-Type' => 'application/javascript'])
            ->withBody($script, 'application/javascript')
            ->post("{$this->baseUrl}/function?token={$this->apiKey}");

        if (! $response->successful()) {
            Log::error('Browserless: error HTTP', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return null;
        }

        $result = $response->json();

        if (($result['type'] ?? '') === 'error') {
            Log::error('Browserless: error en flujo', ['error' => $result['data'] ?? 'unknown']);

            return null;
        }

        if (($result['type'] ?? '') === 'html') {
            Log::info('Browserless: HTML obtenido', [
                'size' => strlen($result['data'] ?? ''),
                'url' => $result['url'] ?? '',
            ]);

            return $result['data'];
        }

        Log::error('Browserless: respuesta inesperada', ['result' => substr(json_encode($result), 0, 500)]);

        return null;
    }
}
