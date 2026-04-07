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

        $models = [
            config('services.openrouter.model'),
            'qwen/qwen3.6-plus:free',
            'nvidia/nemotron-3-super-120b-a12b:free',
            'stepfun/step-3.5-flash:free',
        ];

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
Eres un abogado litigante colombiano senior con mas de 20 anos de experiencia. Tu tarea es generar un resumen ejecutivo profesional que un abogado pueda usar para entender rapidamente el estado de un caso.

FORMATO DE SALIDA (texto plano, sin markdown, sin asteriscos, sin # ni negritas):

RESUMEN EJECUTIVO
[Descripcion concisa del caso: quien demanda a quien, por que, ante que despacho]

ESTADO ACTUAL DEL PROCESO
- Etapa procesal actual y que significa en terminos practicos
- Ultima actuacion relevante y su implicacion
- Tiempo transcurrido desde el inicio del proceso

PARTES PROCESALES
- Demandante(s) y su posicion
- Demandado(s) y su posicion
- Otros intervinientes si los hay

PROXIMOS PASOS CRITICOS
- Que debe hacer el abogado inmediatamente
- Plazos proximos a vencer (con base legal si aplica)
- Diligencias o audiencias pendientes

RIESGOS Y ALERTAS
- Plazos que podrian estar proximos a vencer
- Posibles problemas procesales detectados
- Oportunidades que no se deben dejar pasar

REGLAS ESTRICTAS:
- Maximo 400 palabras
- Usa SOLO la informacion proporcionada, no inventes hechos
- Si citas una norma, debe ser real: CGP (Ley 1564/2012), CST, CPT, Ley 906/2004, CPACA (Ley 1437/2011), etc.
- Si no estas seguro de un articulo, escribe "verificar norma aplicable"
- Se directo y util, como si le hablaras a un colega abogado
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
Eres un abogado litigante colombiano senior. Analiza el caso y da una recomendacion concreta sobre el siguiente paso a seguir.

FORMATO DE SALIDA (texto plano, sin markdown, sin asteriscos, sin # ni negritas):

SIGUIENTE PASO INMEDIATO
[Accion concreta que debe tomar el abogado, no generalidades]

QUE HACER
- Instrucciones paso a paso de lo que se debe preparar o presentar
- Documentos necesarios
- Ante quien se presenta

PLAZOS
- Plazo legal aplicable (con norma si la conoces)
- Fecha limite estimada si hay informacion suficiente
- Consecuencias de no actuar a tiempo

ESTRATEGIA RECOMENDADA
- Si hay opciones, cual es la mas conveniente y por que
- Posibles objeciones de la contraparte y como prepararse

REGLAS:
- Maximo 200 palabras
- Se directo y practico, como un colega senior aconsejando
- Solo cita normas colombianas reales (CGP, CST, CPT, CPACA, Ley 906/2004, etc.)
- Si no conoces el articulo exacto, menciona solo el nombre de la norma
- Basa tu analisis en las actuaciones y el flujo procesal del caso
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
Eres un abogado colombiano experto en litigio con amplia experiencia redactando documentos juridicos formales para tribunales colombianos. Vas a redactar: {$documentType}.

REGLAS ESTRICTAS DE FORMATO:
1) Escribe en texto plano sin formato markdown, sin asteriscos, sin negritas, sin encabezados con #.
2) Usa TODOS los datos del caso proporcionados. No omitas informacion disponible.
3) Cuando un dato no esta disponible, escribe <<<COMPLETAR: descripcion>>> como placeholder visible para que el abogado lo reemplace. Ejemplo: <<<COMPLETAR: direccion de notificacion del demandado>>>
4) Formato de documento juridico colombiano profesional y formal.

ESTRUCTURA OBLIGATORIA DEL DOCUMENTO:
- Ciudad y fecha
- Destinatario: Senor(a) Juez del despacho correspondiente con direccion
- Referencia: tipo de proceso, radicado, partes
- Identificacion completa del apoderado y poderdante
- Cuerpo del documento segun tipo
- Fundamentos de derecho con normas REALES
- Peticiones/pretensiones concretas y numeradas
- Anexos y pruebas
- Notificaciones con direcciones
- Firma del abogado con tarjeta profesional

{$docInstructions}

VERIFICACION LEGAL OBLIGATORIA:
- Solo cita leyes y articulos que existan realmente en la legislacion colombiana vigente
- Normas permitidas: Constitucion Politica de Colombia, Codigo General del Proceso (Ley 1564/2012), Codigo Civil, Codigo de Procedimiento Civil (para procesos antiguos), Codigo Sustantivo del Trabajo, Codigo Procesal del Trabajo (DL 2158/1948 y Ley 712/2001), Ley 906/2004 (Sistema Penal Acusatorio), Ley 600/2000 (para procesos penales anteriores), CPACA (Ley 1437/2011), Codigo de Comercio, Ley 1581/2012 (Datos Personales), Ley 1098/2006 (Infancia y Adolescencia), Ley 1116/2006 (Insolvencia), Ley 1010/2006 (Acoso Laboral), Ley 25/1992 (Divorcio), Ley 1996/2019 (Capacidad Legal), Ley 2220/2022 (reforma CGP)
- NO inventes numeros de articulos. Si no estas seguro, escribe <<<VERIFICAR: articulo sobre [tema] en [norma]>>> para que el abogado confirme
- Es preferible un placeholder a citar un articulo inexistente
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
