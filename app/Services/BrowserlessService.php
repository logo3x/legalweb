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
    const radicado = BPL_RADICADO;
    const delay = ms => new Promise(r => setTimeout(r, ms));

    // Paso 1: Ir a la pagina de busqueda
    await page.goto('https://procesojudicial.ramajudicial.gov.co/Justicia21/Administracion/Ciudadanos/frmConsulta', {
        waitUntil: 'networkidle2',
        timeout: 30000
    });

    // Paso 2: Esperar que cargue el campo de codigo
    await page.waitForSelector('#MainContent_txtCodigoProceso', { timeout: 10000 });

    // Paso 3: Escribir el radicado
    await page.type('#MainContent_txtCodigoProceso', radicado, { delay: 50 });

    // Paso 4: Esperar que reCAPTCHA v3 este listo y generar token
    await delay(3000);

    try {
        await page.evaluate(async () => {
            if (typeof grecaptcha !== 'undefined') {
                await new Promise((resolve) => {
                    grecaptcha.ready(async () => {
                        const siteKey = '6Ldf8zAiAAAAAAq1LUwvTCwki5C6uuIg0zVw4of0';
                        const token = await grecaptcha.execute(siteKey, { action: 'submit' });
                        document.getElementById('recaptchaResponse').value = token;
                        resolve();
                    });
                });
            }
        });
    } catch (e) {
        // Continuar sin captcha si falla
    }

    // Paso 5: Click en Consultar via __doPostBack
    await page.evaluate(() => {
        __doPostBack('ctl00$MainContent$btnConsultar', '');
    });

    // Paso 6: Esperar resultados
    await delay(8000);

    // Verificar si hay error de captcha
    const pageContent = await page.content();
    if (pageContent.includes('El valor de la Capcha no coincide')) {
        return { type: 'error', data: 'captcha_failed' };
    }

    // Paso 7: Buscar y click en la lupa del resultado
    try {
        await page.waitForSelector('[id*="grdProceso"] [id*="imgbConsultarGrilla"]', { timeout: 10000 });
        await page.click('[id*="grdProceso"] [id*="imgbConsultarGrilla"]');
    } catch (e) {
        try {
            await page.waitForSelector('input[title="Consultar registro"]', { timeout: 5000 });
            await page.click('input[title="Consultar registro"]');
        } catch (e2) {
            return { type: 'error', data: 'no_results', html: pageContent.substring(0, 500) };
        }
    }

    // Paso 8: Esperar que cargue la pagina del proceso
    await delay(8000);

    // Verificar que estamos en frmConsultaProceso
    const finalUrl = page.url();
    const html = await page.content();

    if (!html.includes('del Proceso')) {
        return { type: 'error', data: 'no_process_page' };
    }

    return { type: 'html', data: html, url: finalUrl };
}
JS;

        // Reemplazar el placeholder del radicado
        $script = str_replace('BPL_RADICADO', json_encode($radicado), $script);

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
