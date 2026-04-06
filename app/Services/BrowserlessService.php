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
     * Ejecutar flujo completo en Tyba via Browserless /function endpoint.
     */
    public function fetchTybaProcess(string $radicado): ?string
    {
        if (! $this->apiKey) {
            Log::error('Browserless: API key no configurada');

            return null;
        }

        $script = <<<'JS'
export default async ({ page }) => {
    const radicado = "RADICADO_PLACEHOLDER";

    await page.goto("https://procesojudicial.ramajudicial.gov.co/Justicia21/Administracion/Ciudadanos/frmConsulta", {
        waitUntil: "networkidle2",
        timeout: 30000
    });

    await page.waitForSelector("#MainContent_txtCodigoProceso", { timeout: 10000 });
    await page.type("#MainContent_txtCodigoProceso", radicado, { delay: 50 });

    // Esperar reCAPTCHA y generar token
    try {
        await page.waitForFunction("typeof grecaptcha !== 'undefined'", { timeout: 10000 });
        await page.evaluate(function() {
            return new Promise(function(resolve) {
                grecaptcha.ready(function() {
                    grecaptcha.execute("6Ldf8zAiAAAAAAq1LUwvTCwki5C6uuIg0zVw4of0", { action: "submit" }).then(function(token) {
                        document.getElementById("recaptchaResponse").value = token;
                        resolve();
                    }).catch(function() { resolve(); });
                });
            });
        });
    } catch (e) {}

    // Click Consultar
    await page.evaluate(function() {
        __doPostBack("ctl00$MainContent$btnConsultar", "");
    });

    // Esperar resultados
    try {
        await page.waitForSelector("[id*='grdProceso']", { timeout: 15000 });
    } catch (e) {
        const html = await page.content();
        if (html.includes("Capcha no coincide")) {
            return { data: { error: "captcha_failed" }, type: "application/json" };
        }
        return { data: { error: "no_results" }, type: "application/json" };
    }

    // Click en la lupa
    try {
        await page.waitForSelector("input[title='Consultar registro']", { timeout: 10000 });
        await page.click("input[title='Consultar registro']");
    } catch (e) {
        return { data: { error: "no_lupa" }, type: "application/json" };
    }

    // Esperar pagina del proceso con datos
    try {
        await page.waitForNavigation({ waitUntil: "networkidle2", timeout: 15000 });
    } catch (e) {}

    try {
        await page.waitForFunction(
            "document.querySelector('#MainContent_txtCodigoProceso') && document.querySelector('#MainContent_txtCodigoProceso').value && document.querySelector('#MainContent_txtCodigoProceso').value.length > 5",
            { timeout: 10000 }
        );
    } catch (e) {}

    const html = await page.content();
    return { data: { html: html, url: page.url() }, type: "application/json" };
};
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
        $data = $result['data'] ?? $result;

        if (isset($data['error'])) {
            Log::error('Browserless: error en flujo', ['error' => $data['error']]);

            return null;
        }

        if (isset($data['html'])) {
            $htmlSize = strlen($data['html']);
            Log::info('Browserless: HTML obtenido', ['size' => $htmlSize, 'url' => $data['url'] ?? '']);

            return $data['html'];
        }

        Log::error('Browserless: respuesta sin HTML', ['keys' => array_keys($result), 'data_keys' => array_keys($data)]);

        return null;
    }
}
