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
            // lastProvider ya fue seteado por callOpenRouter con el modelo usado
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

        // Modelos gratuitos ordenados por preferencia
        $models = [
            config('services.openrouter.model'),
            'qwen/qwen3.6-plus:free',
            'nvidia/nemotron-3-super-120b-a12b:free',
            'stepfun/step-3.5-flash:free',
        ];

        foreach ($models as $model) {
            try {
                $response = Http::timeout(45)->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'LegalWeb',
                ])->post(config('services.openrouter.base_url').'/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.3,
                ]);

                if ($response->successful() && $response->json('choices.0.message.content')) {
                    $this->lastProvider = "OpenRouter ({$model})";

                    return $response->json('choices.0.message.content');
                }

                Log::info("OpenRouter model {$model} failed, trying next", ['status' => $response->status()]);
            } catch (\Exception $e) {
                Log::info("OpenRouter model {$model} error: ".$e->getMessage());

                continue;
            }
        }

        Log::error('OpenRouter: todos los modelos fallaron');

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
            .'VERIFICACION DE DATOS: No inventes hechos, leyes, articulos ni normas. Solo usa la informacion proporcionada. '
            .'Si mencionas una ley o articulo, asegurate de que exista en la legislacion colombiana vigente. '
            .'Si no estas seguro de un dato legal, indica "verificar con la norma aplicable" en vez de inventar.',
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
            .'Indica: 1) Cual es el siguiente paso. 2) Que debe hacer el abogado. 3) Si hay plazos proximos a vencer. '
            .'VERIFICACION: Solo cita normas que existan realmente en la legislacion colombiana (CGP, CPT, Ley 906/2004, CPACA, etc). '
            .'No inventes articulos ni numeros de ley. Si no estas seguro del articulo exacto, menciona solo el nombre de la norma.',
            $context
        );
    }

    public function draftDocument(LegalCase $case, string $documentType): ?string
    {
        $case->load(['client', 'caseType', 'user', 'user.firm', 'events', 'flowProgress.flowStep']);

        $firm = $case->user->firm;

        $lastEvents = $case->events->sortByDesc('event_date')->take(5)->map(
            fn ($e) => "- [{$e->event_date->format('d/m/Y')}] {$e->event_type}: {$e->title}"
        )->implode("\n");

        $currentStep = $case->flowProgress
            ->sortBy('flowStep.order')
            ->firstWhere('status', 'en_progreso');

        $context = "DATOS COMPLETOS PARA EL DOCUMENTO:\n\n"
            ."Tipo de documento a generar: {$documentType}\n\n"
            ."DATOS DEL CASO:\n"
            ."- Titulo: {$case->title}\n"
            ."- Numero interno: {$case->case_number}\n"
            .'- Radicado judicial: '.($case->external_case_number ?? '<<<PENDIENTE DE RADICADO>>>')."\n"
            ."- Tipo de proceso: {$case->caseType->name}\n"
            ."- Estado actual: {$case->status}\n"
            ."- Prioridad: {$case->priority}\n"
            .'- Juzgado/Despacho: '.($case->court ?? '<<<NOMBRE DEL JUZGADO>>>')."\n"
            .'- Juez: '.($case->judge ?? '<<<NOMBRE DEL JUEZ>>>')."\n"
            .'- Fecha de inicio: '.($case->started_at?->format('d/m/Y') ?? '<<<FECHA>>>')."\n\n"
            ."DATOS DEL CLIENTE (demandante/solicitante):\n"
            ."- Nombre completo: {$case->client->full_name}\n"
            ."- Tipo documento: {$case->client->document_type}\n"
            ."- Numero documento: {$case->client->document_number}\n"
            .'- Direccion: '.($case->client->address ?? '<<<DIRECCION DEL CLIENTE>>>')."\n"
            .'- Ciudad: '.($case->client->city ?? '<<<CIUDAD>>>')."\n"
            .'- Telefono: '.($case->client->phone ?? '<<<TELEFONO>>>')."\n"
            .'- Email: '.($case->client->email ?? '<<<EMAIL>>>')."\n\n"
            .'CONTRAPARTE: '.($case->opposing_party ?? '<<<NOMBRE COMPLETO DE LA CONTRAPARTE>>>')."\n\n"
            ."DATOS DEL ABOGADO:\n"
            ."- Nombre: {$case->user->name}\n"
            .'- Email: '.($case->user->email ?? '<<<EMAIL DEL ABOGADO>>>')."\n"
            .'- Tarjeta Profesional: <<<NUMERO DE TARJETA PROFESIONAL>>>'."\n\n"
            ."DATOS DE LA FIRMA:\n"
            .'- Firma: '.($firm?->name ?? '<<<NOMBRE DE LA FIRMA>>>')."\n"
            .'- NIT: '.($firm?->nit ?? '<<<NIT>>>')."\n"
            .'- Direccion: '.($firm?->address ?? '<<<DIRECCION>>>')."\n"
            .'- Ciudad: '.($firm?->city ?? '<<<CIUDAD>>>')."\n\n"
            ."ACTUACIONES RECIENTES:\n{$lastEvents}\n\n"
            .'ETAPA PROCESAL ACTUAL: '.($currentStep ? $currentStep->flowStep->name : 'No definida');

        return $this->call(
            'Eres un abogado colombiano experto redactando un documento juridico formal. '
            .'REGLAS ESTRICTAS: '
            .'1) Escribe en texto plano sin formato markdown, sin asteriscos, sin negritas, sin encabezados con #. '
            .'2) Usa TODOS los datos proporcionados en el documento. No omitas informacion disponible. '
            .'3) Cuando un dato dice <<<TEXTO>>> significa que el abogado debe completarlo. '
            .'Dejalo EXACTAMENTE como <<<TEXTO>>> para que sea visible y el abogado lo reemplace. '
            .'4) Formato de documento juridico colombiano profesional. '
            .'5) Incluye: ciudad y fecha, senor juez/destinatario con despacho, referencia con radicado, '
            .'identificacion del apoderado y poderdante, hechos numerados basados en el caso, '
            .'fundamentos de derecho citando UNICAMENTE normas colombianas reales y vigentes, '
            .'pretensiones/peticiones concretas, pruebas, notificaciones, firma con tarjeta profesional. '
            .'6) VERIFICACION LEGAL OBLIGATORIA: Solo cita leyes, articulos y normas que existan realmente en Colombia. '
            .'Normas validas: Constitucion Politica, Codigo General del Proceso (Ley 1564/2012), Codigo Civil, '
            .'Codigo Sustantivo del Trabajo, Codigo Procesal del Trabajo (DL 2158/1948), Ley 906/2004 (Sistema Penal Acusatorio), '
            .'CPACA (Ley 1437/2011), Codigo de Comercio, Ley 1581/2012 (Datos Personales), Ley 1098/2006 (Infancia), '
            .'Ley 1116/2006 (Insolvencia), Ley 1010/2006 (Acoso Laboral), Ley 25/1992 (Divorcio). '
            .'NO inventes numeros de articulos. Si no estas seguro del articulo exacto, escribe <<<VERIFICAR ARTICULO>>> '
            .'para que el abogado lo confirme. Es preferible dejar un placeholder a citar un articulo inexistente.',
            $context
        );
    }
}
