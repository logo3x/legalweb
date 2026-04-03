<?php

namespace App\Services;

use App\Models\LegalCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private function call(string $systemPrompt, string $userMessage): ?string
    {
        // Intentar Gemini primero
        $result = $this->callGemini($systemPrompt, $userMessage);

        if ($result) {
            return $result;
        }

        // Fallback a OpenRouter
        return $this->callOpenRouter($systemPrompt, $userMessage);
    }

    private function callGemini(string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (! $apiKey) {
            return null;
        }

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

        Log::info('Gemini API failed, falling back to OpenRouter', ['status' => $response->status()]);

        return null;
    }

    private function callOpenRouter(string $systemPrompt, string $userMessage): ?string
    {
        $apiKey = config('services.openrouter.api_key');

        if (! $apiKey) {
            return null;
        }

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

        Log::error('OpenRouter API failed', ['status' => $response->status()]);

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

        $context = "Caso: {$case->title}\n"
            ."Numero: {$case->case_number}\n"
            ."Tipo: {$case->caseType->name}\n"
            ."Estado: {$case->status}\n"
            ."Cliente: {$case->client->full_name}\n"
            ."Abogado: {$case->user->name}\n"
            .'Juzgado: '.($case->court ?? 'No asignado')."\n"
            .'Contraparte: '.($case->opposing_party ?? 'No definida')."\n\n"
            ."Actuaciones recientes:\n{$events}\n\n"
            ."Progreso del flujo:\n{$progress}";

        return $this->call(
            'Eres un asistente legal colombiano experto. Genera un resumen ejecutivo claro y conciso del caso en espanol. Incluye: situacion actual, proximos pasos sugeridos y puntos de atencion. Maximo 300 palabras. No inventes hechos, basa tu resumen solo en la informacion proporcionada.',
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
            ."Pasos del flujo:\n{$progress}";

        return $this->call(
            'Eres un asistente legal colombiano. Basandote en el progreso del flujo procesal, sugiere cual es el siguiente paso a seguir, que debe tener en cuenta el abogado, y si hay terminos proximos a vencer. Responde en espanol, de forma clara y practica. Maximo 200 palabras.',
            $context
        );
    }

    public function draftDocument(LegalCase $case, string $documentType): ?string
    {
        $case->load(['client', 'caseType', 'user']);

        $context = "Tipo de documento: {$documentType}\n"
            ."Caso: {$case->title}\n"
            ."Tipo de proceso: {$case->caseType->name}\n"
            ."Cliente: {$case->client->full_name} ({$case->client->document_type} {$case->client->document_number})\n"
            ."Abogado: {$case->user->name}\n"
            .'Juzgado: '.($case->court ?? 'No asignado')."\n"
            .'Contraparte: '.($case->opposing_party ?? 'No definida')."\n"
            .'Radicado: '.($case->external_case_number ?? 'Sin radicado');

        return $this->call(
            'Eres un asistente legal colombiano experto en redaccion juridica. Genera un borrador del documento solicitado basandote en la informacion del caso. Usa formato profesional y lenguaje juridico apropiado para Colombia. Incluye encabezado, cuerpo y cierre. Es un borrador que el abogado revisara y ajustara. No uses formato markdown, escribe en texto plano con saltos de linea.',
            $context
        );
    }
}
