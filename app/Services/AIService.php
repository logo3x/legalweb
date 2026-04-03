<?php

namespace App\Services;

use App\Models\LegalCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private ?string $lastProvider = null;

    public function getLastProvider(): ?string
    {
        return $this->lastProvider;
    }

    private function call(string $systemPrompt, string $userMessage): ?string
    {
        $result = $this->callGemini($systemPrompt, $userMessage);

        if ($result) {
            $this->lastProvider = 'Gemini';

            return $this->cleanMarkdown($result);
        }

        $result = $this->callOpenRouter($systemPrompt, $userMessage);

        if ($result) {
            $this->lastProvider = 'OpenRouter';

            return $this->cleanMarkdown($result);
        }

        return null;
    }

    private function cleanMarkdown(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        $text = preg_replace('/^\*\s+/m', '- ', $text);
        $text = preg_replace('/^\d+\.\s+/m', '- ', $text);
        $text = preg_replace('/`(.+?)`/', '$1', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function callGemini(string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (! $apiKey) {
            return null;
        }

        try {
            $model = config('services.gemini.model');
            $baseUrl = config('services.gemini.base_url');

            $response = Http::timeout(30)->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['parts' => [['text' => $userMessage]]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 2000,
                    'temperature' => 0.3,
                ],
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text');
            }
        } catch (\Exception $e) {
            Log::info('Gemini API error: '.$e->getMessage());
        }

        return null;
    }

    private function callOpenRouter(string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = config('services.openrouter.api_key');

        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'LegalWeb',
            ])->post(config('services.openrouter.base_url').'/chat/completions', [
                'model' => config('services.openrouter.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Exception $e) {
            Log::error('OpenRouter API error: '.$e->getMessage());
        }

        return null;
    }

    public function summarizeCase(LegalCase $case): ?string
    {
        $case->load(['client', 'caseType', 'user', 'events', 'flowProgress.flowStep']);

        $events = $case->events->sortByDesc('event_date')->take(10)->map(
            fn ($e) => "- [{$e->event_date->format('d/m/Y')}] {$e->event_type}: {$e->title}"
        )->implode("\n");

        $progress = $case->flowProgress->sortBy('flowStep.order')->map(
            fn ($p) => "- {$p->flowStep->name}: {$p->status}"
        )->implode("\n");

        $context = "DATOS DEL CASO:\n"
            ."Titulo: {$case->title}\n"
            ."Numero interno: {$case->case_number}\n"
            ."Tipo de proceso: {$case->caseType->name}\n"
            ."Estado: {$case->status}\n"
            ."Cliente: {$case->client->full_name}\n"
            ."Abogado: {$case->user->name}\n"
            .'Juzgado: '.($case->court ?? 'Sin asignar')."\n"
            .'Contraparte: '.($case->opposing_party ?? 'No definida')."\n\n"
            ."ACTUACIONES RECIENTES:\n{$events}\n\n"
            ."PROGRESO DEL FLUJO PROCESAL:\n{$progress}";

        return $this->call(
            'Eres un asistente juridico colombiano. Genera un resumen ejecutivo del caso. '
            .'REGLAS: Escribe en texto plano sin formato markdown, sin asteriscos, sin negritas, sin encabezados con #. '
            .'Usa guiones (-) para listas. Maximo 250 palabras. '
            .'ESTRUCTURA: 1) Situacion actual del caso. 2) Proximos pasos recomendados. 3) Puntos de atencion o riesgos. '
            .'No inventes hechos. Solo usa la informacion proporcionada.',
            $context
        );
    }

    public function suggestNextStep(LegalCase $case): ?string
    {
        $case->load(['caseType', 'flowProgress.flowStep']);

        $progress = $case->flowProgress->sortBy('flowStep.order')->map(
            fn ($p) => "- Paso {$p->flowStep->order}: {$p->flowStep->name} [{$p->status}]".($p->flowStep->days_limit ? " (plazo: {$p->flowStep->days_limit} dias)" : '')
        )->implode("\n");

        $context = "Tipo de proceso: {$case->caseType->name}\n"
            ."Estado del caso: {$case->status}\n\n"
            ."PASOS DEL FLUJO:\n{$progress}";

        return $this->call(
            'Eres un asistente juridico colombiano. Analiza el flujo procesal e indica el siguiente paso a seguir. '
            .'REGLAS: Escribe en texto plano sin formato markdown, sin asteriscos, sin negritas, sin encabezados con #. '
            .'Usa guiones (-) para listas. Maximo 150 palabras. Se directo y practico. '
            .'Indica: 1) Cual es el siguiente paso. 2) Que debe hacer el abogado. 3) Si hay plazos proximos a vencer.',
            $context
        );
    }

    public function draftDocument(LegalCase $case, string $documentType): ?string
    {
        $case->load(['client', 'caseType', 'user']);

        $context = "DATOS PARA EL DOCUMENTO:\n"
            ."Tipo de documento: {$documentType}\n"
            ."Caso: {$case->title}\n"
            ."Tipo de proceso: {$case->caseType->name}\n"
            ."Cliente: {$case->client->full_name} ({$case->client->document_type} {$case->client->document_number})\n"
            ."Abogado: {$case->user->name}\n"
            .'Juzgado: '.($case->court ?? 'Por asignar')."\n"
            .'Contraparte: '.($case->opposing_party ?? 'Por definir')."\n"
            .'Radicado: '.($case->external_case_number ?? 'Sin radicado');

        return $this->call(
            'Eres un abogado colombiano redactando un documento juridico. '
            .'REGLAS: Escribe en texto plano sin formato markdown, sin asteriscos, sin negritas, sin encabezados con #. '
            .'Usa formato profesional de documento juridico colombiano. '
            .'Incluye: ciudad y fecha, destinatario, referencia, cuerpo del documento, peticion, firma. '
            .'Es un borrador que el abogado revisara. Usa lenguaje juridico apropiado para Colombia.',
            $context
        );
    }
}
