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
     * Ejecutar flujo completo en Tyba via Browserless BrowserQL.
     * Todo en una sola llamada: navegar, escribir, captcha, consultar, lupa, extraer HTML.
     */
    public function fetchTybaProcess(string $radicado): ?string
    {
        if (! $this->apiKey) {
            Log::error('Browserless: API key no configurada');

            return null;
        }

        $query = <<<'GRAPHQL'
mutation TybaFullFlow {
    goto(
        url: "https://procesojudicial.ramajudicial.gov.co/Justicia21/Administracion/Ciudadanos/frmConsulta"
        waitUntil: networkIdle
        timeout: 30000
    ) {
        status
        time
    }

    waitForInput: waitForSelector(
        selector: "#MainContent_txtCodigoProceso"
        timeout: 10000
    ) {
        time
    }

    typeRadicado: type(
        selector: "#MainContent_txtCodigoProceso"
        text: "RADICADO_PLACEHOLDER"
        delay: 100
    ) {
        time
    }

    pauseBeforeSolve: waitForTimeout(time: 2000) {
        time
    }

    solveCaptcha: solve(
        type: recaptchaV3
        timeout: 30000
    ) {
        found
        solved
        time
    }

    pauseAfterSolve: waitForTimeout(time: 1000) {
        time
    }

    clickConsultar: click(
        selector: "#MainContent_btnConsultar"
        timeout: 5000
    ) {
        time
    }

    waitForLupa: waitForSelector(
        selector: "input[title='Consultar registro']"
        timeout: 25000
    ) {
        time
    }

    pauseBeforeLupa: waitForTimeout(time: 500) {
        time
    }

    clickLupa: click(
        selector: "input[title='Consultar registro']"
    ) {
        time
    }

    waitForProcessPage: waitForTimeout(time: 10000) {
        time
    }

    processHtml: html {
        html
    }
}
GRAPHQL;

        $query = str_replace('RADICADO_PLACEHOLDER', $radicado, $query);

        Log::info('Browserless BQL: flujo completo', ['radicado' => $radicado]);

        $response = Http::timeout(180)
            ->post("{$this->baseUrl}/chrome/bql?token={$this->apiKey}&proxy=residential&humanlike=true&blockAds=true", [
                'query' => $query,
                'variables' => new \stdClass,
                'operationName' => 'TybaFullFlow',
            ]);

        if (! $response->successful()) {
            Log::error('Browserless BQL: error HTTP', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return null;
        }

        $result = $response->json();

        // Extraer data incluso si hay errores parciales
        $data = $result['data'] ?? [];

        if (! empty($result['errors'])) {
            $errors = array_map(fn ($e) => $e['message'] ?? 'unknown', $result['errors']);
            Log::warning('Browserless BQL: errores parciales', ['errors' => $errors]);
        }

        // Log del captcha
        $captcha = $data['solveCaptcha'] ?? [];
        Log::info('Browserless BQL: captcha', [
            'found' => $captcha['found'] ?? false,
            'solved' => $captcha['solved'] ?? false,
            'time' => $captcha['time'] ?? 0,
        ]);

        $html = $data['processHtml']['html'] ?? null;

        if (! $html) {
            Log::error('Browserless BQL: sin HTML');

            return null;
        }

        // Verificar captcha rechazado
        if (str_contains($html, 'Capcha no coincide')) {
            Log::error('Browserless BQL: captcha rechazado por servidor');

            return null;
        }

        Log::info('Browserless BQL: HTML obtenido', [
            'size' => strlen($html),
            'has_detalles' => str_contains($html, 'del Proceso'),
            'has_despacho' => str_contains($html, 'MainContent_txtNomDespacho'),
            'has_grid' => str_contains($html, 'grdProceso'),
        ]);

        return $html;
    }
}
