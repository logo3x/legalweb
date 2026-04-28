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

    private function call(string $systemPrompt, string $userMessage, int $maxTokens = 2000): ?string
    {
        $result = $this->callGemini($systemPrompt, $userMessage, $maxTokens);

        if ($result) {
            $this->lastProvider = 'Gemini';

            return $this->cleanMarkdown($result);
        }

        $result = $this->callOpenRouter($systemPrompt, $userMessage, $maxTokens);

        if ($result) {
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

    private function callGemini(string $systemPrompt, string $userMessage, int $maxTokens): ?string
    {
        $apiKey = config('services.gemini.api_key');

        if (! $apiKey) {
            return null;
        }

        try {
            $model = config('services.gemini.model');
            $baseUrl = config('services.gemini.base_url');

            $response = Http::timeout(45)->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['parts' => [['text' => $userMessage]]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $maxTokens,
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

    private function callOpenRouter(string $systemPrompt, string $userMessage, int $maxTokens): ?string
    {
        $apiKey = config('services.openrouter.api_key');

        if (! $apiKey) {
            return null;
        }

        $models = array_filter([
            config('services.openrouter.model'),
            'meta-llama/llama-3.3-70b-instruct:free',
            'google/gemini-2.0-flash-exp:free',
            'deepseek/deepseek-chat-v3.1:free',
            'qwen/qwen-2.5-72b-instruct:free',
        ]);

        foreach ($models as $model) {
            try {
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'LegalWeb',
                ])->post(config('services.openrouter.base_url').'/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.3,
                ]);

                if ($response->successful() && $response->json('choices.0.message.content')) {
                    $this->lastProvider = "OpenRouter ({$model})";

                    return $response->json('choices.0.message.content');
                }

                Log::info("OpenRouter model {$model} failed", ['status' => $response->status()]);
            } catch (\Exception $e) {
                Log::info("OpenRouter model {$model} error: ".$e->getMessage());

                continue;
            }
        }

        Log::error('OpenRouter: todos los modelos fallaron');

        return null;
    }

    /**
     * Construir el contexto completo de un caso incluyendo datos de Tyba.
     */
    private function buildCaseContext(LegalCase $case): string
    {
        $tyba = $case->tyba_data ?? [];

        $context = "DATOS DEL CASO:\n"
            ."- Titulo: {$case->title}\n"
            ."- Numero interno: {$case->case_number}\n"
            .'- Radicado judicial: '.($case->external_case_number ?? 'Sin radicado')."\n"
            ."- Tipo de proceso: {$case->caseType->name}\n"
            ."- Estado: {$case->status}\n"
            ."- Prioridad: {$case->priority}\n"
            .'- Juzgado/Despacho: '.($case->court ?? 'Sin asignar')."\n"
            .'- Juez/Ponente: '.($case->judge ?? 'Sin asignar')."\n"
            .'- Contraparte: '.($case->opposing_party ?? 'No definida')."\n"
            .'- Fecha de inicio: '.($case->started_at?->format('d/m/Y') ?? 'Sin definir')."\n"
            .'- Fecha de cierre: '.($case->closed_at?->format('d/m/Y') ?? 'Caso abierto')."\n";

        // Datos de Rama Judicial (Tyba)
        if (! empty($tyba)) {
            $context .= "\nDATOS DE LA RAMA JUDICIAL:\n"
                .'- Clase de proceso: '.($tyba['clase_proceso'] ?? '')."\n"
                .'- Tipo proceso: '.($tyba['tipo_proceso'] ?? '')."\n"
                .'- Subclase: '.($tyba['subclase'] ?? '')."\n"
                .'- Departamento: '.($tyba['departamento'] ?? '')."\n"
                .'- Especialidad: '.($tyba['especialidad'] ?? '')."\n"
                .'- Corporacion: '.($tyba['corporacion'] ?? '')."\n"
                .'- Ubicacion: '.($tyba['ubicacion'] ?? '')."\n"
                .'- Fecha publicacion: '.($tyba['fecha_publicacion'] ?? '')."\n"
                .'- Ultima actuacion: '.($tyba['fecha_ultima_actuacion'] ?? '')."\n";
        }

        // Cliente
        $context .= "\nDATOS DEL CLIENTE:\n"
            ."- Nombre: {$case->client->full_name}\n"
            ."- Documento: {$case->client->document_type} {$case->client->document_number}\n"
            .'- Direccion: '.($case->client->address ?? 'No registrada')."\n"
            .'- Ciudad: '.($case->client->city ?? 'No registrada')."\n"
            .'- Telefono: '.($case->client->phone ?? 'No registrado')."\n"
            .'- Email: '.($case->client->email ?? 'No registrado')."\n";

        // Abogado y firma
        $firm = $case->user->firm;
        $context .= "\nDATOS DEL ABOGADO:\n"
            ."- Nombre: {$case->user->name}\n"
            .'- Email: '.($case->user->email ?? '')."\n"
            .'- Firma: '.($firm?->name ?? '')."\n"
            .'- NIT firma: '.($firm?->nit ?? '')."\n"
            .'- Direccion firma: '.($firm?->address ?? '')."\n"
            .'- Ciudad firma: '.($firm?->city ?? '')."\n";

        // Descripcion del caso (contiene sujetos procesales si fue importado)
        if ($case->description) {
            $context .= "\nDESCRIPCION DEL CASO:\n{$case->description}\n";
        }

        return $context;
    }

    public function summarizeCase(LegalCase $case): ?string
    {
        $case->load(['client', 'caseType', 'user', 'user.firm', 'events', 'flowProgress.flowStep']);

        $events = $case->events->sortByDesc('event_date')->take(15)->map(
            fn ($e) => "- [{$e->event_date->format('d/m/Y')}] {$e->event_type}: {$e->title}".($e->description ? " - {$e->description}" : '')
        )->implode("\n");

        $progress = $case->flowProgress->sortBy('flowStep.order')->map(
            fn ($p) => "- {$p->flowStep->name}: {$p->status}".($p->flowStep->days_limit ? " (plazo: {$p->flowStep->days_limit} dias)" : '').($p->flowStep->legal_basis ? " [Base legal: {$p->flowStep->legal_basis}]" : '')
        )->implode("\n");

        $context = $this->buildCaseContext($case);
        $context .= "\nACTUACIONES RECIENTES (ultimas 15):\n{$events}\n";
        $context .= "\nPROGRESO DEL FLUJO PROCESAL:\n{$progress}";

        $systemPrompt = <<<'PROMPT'
Eres un abogado litigante colombiano senior. Generas un resumen ejecutivo profesional basado UNICAMENTE en la informacion del caso proporcionada.

REGLA CERO - PROHIBIDO ALUCINAR:
- NO inventes hechos, fechas, nombres, leyes, articulos, jurisprudencia ni numeros de radicado.
- Si la informacion proporcionada NO contiene un dato, di "no consta" o "no especificado".
- NO sugieras procedimientos especificos que no esten respaldados por la informacion del caso.
- Si una afirmacion no se puede verificar con los datos del caso, OMITELA o marca con "[verificar]".

FORMATO DE SALIDA (texto plano, sin markdown, sin asteriscos, sin # ni negritas):

RESUMEN EJECUTIVO
[Descripcion concisa basada SOLO en datos del caso: partes, despacho, tipo de proceso]

ESTADO ACTUAL DEL PROCESO
- Etapa segun el flujo procesal del caso
- Ultima actuacion segun la lista de actuaciones
- Tiempo transcurrido (calculado desde fecha_inicio)

PARTES PROCESALES
- Listar EXACTAMENTE como aparecen en los datos del caso
- NO inferir roles ni posiciones

PROXIMOS PASOS CRITICOS
- Solo basados en plazos visibles en el flujo procesal
- Si menciona una norma, debe ser una de las listadas abajo

RIESGOS Y ALERTAS
- Solo plazos que se puedan calcular de los datos
- NO inventes vulnerabilidades procesales

NORMAS PERMITIDAS PARA CITAR (solo estas, con su numero exacto):
- Constitucion Politica de Colombia
- Codigo General del Proceso (Ley 1564/2012)
- Codigo Sustantivo del Trabajo
- Codigo Procesal del Trabajo (DL 2158/1948 y Ley 712/2001)
- Ley 906/2004 (Sistema Penal Acusatorio)
- CPACA (Ley 1437/2011)
- Codigo Civil
- Codigo de Comercio
- Ley 1581/2012 (Datos Personales)
- Ley 25/1992 (Divorcio)
- Ley 2220/2022 (reforma CGP)

REGLAS:
- Maximo 400 palabras
- Si no estas 100% seguro del numero de articulo, escribe solo el nombre de la ley sin articulo
- Tono: colega abogado, directo
- Esto es un BORRADOR ORIENTATIVO que el abogado debe revisar antes de actuar
PROMPT;

        return $this->call($systemPrompt, $context);
    }

    public function suggestNextStep(LegalCase $case): ?string
    {
        $case->load(['client', 'caseType', 'user', 'events', 'flowProgress.flowStep']);

        $events = $case->events->sortByDesc('event_date')->take(10)->map(
            fn ($e) => "- [{$e->event_date->format('d/m/Y')}] {$e->event_type}: {$e->title}"
        )->implode("\n");

        $progress = $case->flowProgress->sortBy('flowStep.order')->map(
            fn ($p) => "- Paso {$p->flowStep->order}: {$p->flowStep->name} [{$p->status}]"
                .($p->flowStep->days_limit ? " (plazo: {$p->flowStep->days_limit} dias)" : '')
                .($p->flowStep->legal_basis ? " [Base: {$p->flowStep->legal_basis}]" : '')
                .($p->completed_at ? " completado: {$p->completed_at->format('d/m/Y')}" : '')
        )->implode("\n");

        $context = $this->buildCaseContext($case);
        $context .= "\nACTUACIONES RECIENTES:\n{$events}\n";
        $context .= "\nFLUJO PROCESAL:\n{$progress}";

        $systemPrompt = <<<'PROMPT'
Eres un abogado litigante colombiano senior. Sugieres el siguiente paso basado UNICAMENTE en los datos del caso proporcionados.

REGLA CERO - PROHIBIDO ALUCINAR:
- NO inventes plazos, fechas, articulos ni jurisprudencia.
- Si el dato necesario no aparece en el caso, di "no consta en el expediente" y sugiere obtenerlo.
- Si no puedes confirmar una accion con los datos disponibles, di "verificar con el expediente".

FORMATO DE SALIDA (texto plano, sin markdown):

SIGUIENTE PASO INMEDIATO
[Una sola accion concreta basada en la etapa actual del flujo y la ultima actuacion]

QUE HACER
- Pasos especificos respaldados por la informacion del caso
- Documentos a preparar (basados en los Documentos Requeridos del caso si los hay)
- Donde se presenta (segun el despacho del caso)

PLAZOS
- Solo plazos visibles en el flujo procesal del caso
- Si hay norma aplicable, citar nombre exacto sin inventar articulo
- Si no hay plazo definido en los datos, decir "verificar plazo aplicable"

ESTRATEGIA RECOMENDADA
- Solo si los datos del caso permiten inferirla
- NO inventes posibles objeciones de la contraparte sin base

REGLAS:
- Maximo 200 palabras
- Esto es un BORRADOR ORIENTATIVO. El abogado debe verificar antes de actuar.
- Solo citar: CGP (Ley 1564/2012), CST, CPT, Ley 906/2004, CPACA (Ley 1437/2011), CC, CCo
- Si dudas del numero de articulo, NO lo cites
PROMPT;

        return $this->call($systemPrompt, $context);
    }

    public function draftDocument(LegalCase $case, string $documentType): ?string
    {
        $case->load(['client', 'caseType', 'user', 'user.firm', 'events', 'flowProgress.flowStep']);

        $lastEvents = $case->events->sortByDesc('event_date')->take(10)->map(
            fn ($e) => "- [{$e->event_date->format('d/m/Y')}] {$e->event_type}: {$e->title}".($e->description ? " ({$e->description})" : '')
        )->implode("\n");

        $currentStep = $case->flowProgress
            ->sortBy('flowStep.order')
            ->firstWhere('status', 'en_progreso');

        $context = $this->buildCaseContext($case);
        $context .= "\nACTUACIONES RECIENTES:\n{$lastEvents}\n";
        $context .= "\nETAPA PROCESAL ACTUAL: ".($currentStep ? $currentStep->flowStep->name : 'No definida')."\n";
        $context .= "\nTIPO DE DOCUMENTO A GENERAR: {$documentType}\n";

        // Instrucciones especificas por tipo de documento
        $docInstructions = $this->getDocumentInstructions($documentType);

        $systemPrompt = <<<PROMPT
Eres un abogado colombiano que genera un BORRADOR INICIAL del documento solicitado: {$documentType}.

REGLA CERO - PROHIBIDO ALUCINAR:
- Este es un BORRADOR para que el abogado lo revise, complete y firme.
- NO inventes hechos, fechas, montos, articulos, jurisprudencia, ni datos de las partes.
- Si un dato del caso NO esta disponible, escribe <<<COMPLETAR: que falta>>> COMO TEXTO LITERAL VISIBLE.
- Si un articulo de norma no es 100% verificable, escribe <<<VERIFICAR: articulo sobre [tema] en [norma]>>>.
- ES PREFERIBLE dejar muchos placeholders que inventar datos.

REGLAS DE FORMATO:
1) Texto plano sin markdown, sin asteriscos, sin #.
2) Formato de documento juridico colombiano formal (encabezado, ref, hechos, fundamentos, peticiones, pruebas, notificaciones, firma).
3) Hechos numerados, peticiones numeradas.
4) Usa SOLO los datos del caso proporcionados, no inventes contexto adicional.

NORMAS PERMITIDAS PARA CITAR (con su numero exacto si lo conoces, sin inventar articulos):
- Constitucion Politica de Colombia
- Codigo General del Proceso (Ley 1564/2012)
- Codigo Civil
- Codigo Sustantivo del Trabajo
- Codigo Procesal del Trabajo (DL 2158/1948 y Ley 712/2001)
- Ley 906/2004 (Sistema Penal Acusatorio)
- CPACA (Ley 1437/2011)
- Codigo de Comercio
- Ley 1581/2012 (Datos Personales)
- Ley 1098/2006 (Infancia y Adolescencia)
- Ley 1116/2006 (Insolvencia)
- Ley 1010/2006 (Acoso Laboral)
- Ley 25/1992 (Divorcio)
- Ley 2220/2022 (reforma CGP)

{$docInstructions}

DISCLAIMER OBLIGATORIO al final del documento:
"Este documento es un borrador generado por inteligencia artificial. Debe ser revisado, verificado y ajustado por un abogado titulado antes de su uso."

PROHIBIDO:
- Inventar nombres de jurisprudencia (sentencias C-XXX, T-XXX, etc) sin verificacion
- Inventar nombres de juzgados o despachos no listados en el caso
- Inventar fechas exactas no proporcionadas
- Asegurar resultados ("se ganara", "es seguro que")
PROMPT;

        return $this->call($systemPrompt, $context, 4000);
    }

    /**
     * Instrucciones especificas segun el tipo de documento.
     */
    private function getDocumentInstructions(string $documentType): string
    {
        return match ($documentType) {
            'Demanda' => <<<'INST'
INSTRUCCIONES PARA DEMANDA:
- Incluir presupuestos procesales: jurisdiccion, competencia, legitimacion, capacidad
- HECHOS numerados cronologicamente, cada uno en un parrafo independiente
- PRETENSIONES numeradas: principal y subsidiarias si aplica
- FUNDAMENTOS DE DERECHO: citar normas sustanciales y procesales aplicables
- PRUEBAS: documentales, testimoniales, periciales, inspecciones
- JURAMENTO ESTIMATORIO si hay pretension economica (art. 206 CGP)
- MEDIDAS CAUTELARES si aplica, con justificacion
- CUANTIA del proceso y competencia
- Direccion de notificacion de todas las partes
INST,
            'Contestacion de demanda' => <<<'INST'
INSTRUCCIONES PARA CONTESTACION DE DEMANDA:
- Pronunciamiento EXPRESO sobre cada hecho (aceptar, negar o indicar que no consta)
- Excepciones previas si las hay (art. 100 CGP): falta de jurisdiccion, prescripcion, cosa juzgada, etc.
- Excepciones de merito/fondo
- HECHOS de la defensa, numerados
- Propuesta de pruebas de la defensa
- Si aplica, DEMANDA DE RECONVENCION
- Plazo: verificar que se presente dentro del termino de traslado
INST,
            'Memorial' => <<<'INST'
INSTRUCCIONES PARA MEMORIAL:
- Identificar claramente el proceso y las partes
- Ser conciso y directo en la solicitud
- Fundamentar juridicamente la peticion
- Si se adjuntan documentos, listarlos como anexos
- Indicar lo que se solicita de manera clara y precisa
INST,
            'Recurso de apelacion' => <<<'INST'
INSTRUCCIONES PARA RECURSO DE APELACION:
- Identificar la providencia que se apela (auto o sentencia, fecha)
- Indicar si se interpone como principal o subsidiario
- SUSTENTACION: explicar los errores de hecho o de derecho de la decision
- Indicar que perjuicio causa la decision al recurrente
- Citar normas procesales del recurso (arts. 320-330 CGP)
- Si es contra auto, verificar que sea apelable (art. 321 CGP)
- Plazo: 3 dias para autos, en audiencia o 10 dias para sentencias segun el caso
INST,
            'Recurso de reposicion' => <<<'INST'
INSTRUCCIONES PARA RECURSO DE REPOSICION:
- Identificar el auto que se repone (fecha, contenido)
- Explicar por que el auto es contrario a derecho
- Puede interponerse como principal y apelacion como subsidiario
- Fundamentar en el art. 318 CGP
- Plazo: 3 dias siguientes a la notificacion del auto
- Solicitar expresamente que se revoque o modifique el auto
INST,
            'Poder' => <<<'INST'
INSTRUCCIONES PARA PODER:
- Identificar plenamente al poderdante (nombre, documento, direccion)
- Identificar al apoderado (nombre, tarjeta profesional, direccion)
- Indicar las facultades: generales o especiales
- Si hay facultades especiales: conciliar, recibir, desistir, sustituir, etc.
- Indicar el proceso o asunto especifico para el cual se confiere
- Firma del poderdante con presentacion personal o autenticacion
- Cumplir requisitos del art. 74 CGP
INST,
            'Derecho de peticion' => <<<'INST'
INSTRUCCIONES PARA DERECHO DE PETICION:
- Fundamentar en el art. 23 de la Constitucion Politica y Ley 1755/2015
- Identificar claramente al peticionario y al destinatario
- Exponer los HECHOS que motivan la peticion
- Formular PETICIONES claras, concretas y respetuosas
- Indicar el plazo de respuesta: 15 dias habiles para peticiones generales, 10 para informacion, 30 para consultas
- Indicar direccion de notificacion
- Advertir las consecuencias del silencio administrativo si aplica
INST,
            'Tutela' => <<<'INST'
INSTRUCCIONES PARA ACCION DE TUTELA:
- Fundamentar en el art. 86 de la Constitucion y Decreto 2591/1991
- Identificar el DERECHO FUNDAMENTAL vulnerado o amenazado
- Demostrar la INMEDIATEZ: la vulneracion es actual o inminente
- Demostrar la SUBSIDIARIEDAD: no existe otro mecanismo de defensa, o se usa como mecanismo transitorio para evitar perjuicio irremediable
- HECHOS detallados y cronologicos
- Identificar la autoridad o particular que vulnera el derecho
- PRETENSIONES concretas: que ordene el juez
- Solicitar medida provisional si hay urgencia (art. 7 Decreto 2591/1991)
- No requiere apoderado, pero si se tiene, incluir poder
- Plazo de fallo: 10 dias habiles en primera instancia
INST,
            'Alegatos de conclusion' => <<<'INST'
INSTRUCCIONES PARA ALEGATOS DE CONCLUSION:
- Hacer un recuento cronologico del proceso
- Analizar las pruebas practicadas y su valor probatorio
- Demostrar como las pruebas soportan la teoria del caso
- Desvirtuar los argumentos y pruebas de la contraparte
- Aplicar la sana critica y las reglas de la experiencia
- Citar jurisprudencia relevante si se conoce
- Concluir con la solicitud de decision favorable
- Ser persuasivo pero objetivo
INST,
            'Incidente' => <<<'INST'
INSTRUCCIONES PARA INCIDENTE:
- Identificar el tipo de incidente (nulidad, desacato, regulacion de honorarios, etc.)
- Fundamentar en las normas procesales aplicables
- HECHOS que dan lugar al incidente
- Si es de nulidad: identificar la causal (art. 133 CGP) y demostrar que no esta saneada
- Solicitar el tramite incidental (art. 129 CGP)
- Aportar pruebas que soporten el incidente
INST,
            default => "Redacta un documento juridico colombiano formal de tipo: {$documentType}. Sigue las convenciones formales del litigio colombiano.",
        };
    }
}
