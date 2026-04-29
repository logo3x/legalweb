<?php

namespace App\Services;

use App\Models\BillingEntry;
use App\Models\CaseEvent;
use App\Models\CaseFlow;
use App\Models\CaseFlowProgress;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\TybaSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DemoDataService
{
    public function seedForFirm(Firm $firm, User $owner): void
    {
        try {
            $this->createCaseTypesAndFlows();
            $this->createDemoClients($firm, $owner);
            $cases = $this->createDemoCases($firm, $owner);
            $this->createDemoReminders($firm, $owner, $cases);
            $this->createDemoBilling($owner, $cases);
            $this->createDemoSyncLogs($cases);
            $this->createDemoDocuments($owner, $cases);
        } catch (\Exception $e) {
            Log::warning('Demo seed parcial: '.$e->getMessage());
        }
    }

    private function createCaseTypesAndFlows(): void
    {
        if (CaseType::count() > 0) {
            return;
        }

        $types = [
            ['name' => 'Civil', 'description' => 'Procesos civiles declarativos y ejecutivos', 'color' => '#3B82F6'],
            ['name' => 'Laboral', 'description' => 'Procesos laborales y de seguridad social', 'color' => '#10B981'],
            ['name' => 'Penal', 'description' => 'Procesos penales - Sistema Penal Acusatorio', 'color' => '#EF4444'],
            ['name' => 'Administrativo', 'description' => 'Procesos contencioso administrativos', 'color' => '#F59E0B'],
            ['name' => 'Comercial', 'description' => 'Procesos comerciales y societarios', 'color' => '#8B5CF6'],
            ['name' => 'Familia', 'description' => 'Procesos de familia, custodia y alimentos', 'color' => '#EC4899'],
        ];

        foreach ($types as $type) {
            CaseType::create($type);
        }

        $civil = CaseType::where('name', 'Civil')->first();
        $this->createFlow($civil, 'Proceso Verbal Civil', 'CGP Art. 368-373', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null],
            ['name' => 'Admisión de demanda', 'order' => 2, 'days_limit' => 10],
            ['name' => 'Notificación al demandado', 'order' => 3, 'days_limit' => 25],
            ['name' => 'Contestación de demanda', 'order' => 4, 'days_limit' => 20],
            ['name' => 'Audiencia inicial', 'order' => 5, 'days_limit' => 30],
            ['name' => 'Audiencia de instrucción y juzgamiento', 'order' => 6, 'days_limit' => 40],
            ['name' => 'Sentencia', 'order' => 7, 'days_limit' => 40],
        ]);

        $laboral = CaseType::where('name', 'Laboral')->first();
        $this->createFlow($laboral, 'Proceso Ordinario Laboral', 'CPT Art. 70-84', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null],
            ['name' => 'Admisión y traslado', 'order' => 2, 'days_limit' => 10],
            ['name' => 'Contestación de demanda', 'order' => 3, 'days_limit' => 10],
            ['name' => 'Audiencia de conciliación', 'order' => 4, 'days_limit' => 30],
            ['name' => 'Audiencia de juzgamiento', 'order' => 5, 'days_limit' => 45],
            ['name' => 'Sentencia', 'order' => 6, 'days_limit' => 30],
        ]);

        $familia = CaseType::where('name', 'Familia')->first();
        $this->createFlow($familia, 'Divorcio Contencioso', 'CGP + Ley 25/1992', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null],
            ['name' => 'Admisión de demanda', 'order' => 2, 'days_limit' => 10],
            ['name' => 'Notificación al cónyuge', 'order' => 3, 'days_limit' => 25],
            ['name' => 'Contestación', 'order' => 4, 'days_limit' => 20],
            ['name' => 'Audiencia de conciliación', 'order' => 5, 'days_limit' => 30],
            ['name' => 'Sentencia', 'order' => 6, 'days_limit' => 30],
        ]);
    }

    private function createDemoClients(Firm $firm, User $owner): void
    {
        $suffix = $firm->id;

        Client::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'is_demo' => true,
            'document_type' => 'CC',
            'document_number' => "10000000{$suffix}1",
            'first_name' => 'Juan',
            'last_name' => 'Pérez García',
            'email' => "juan.perez.demo{$suffix}@ejemplo.com",
            'phone' => '3101234567',
            'city' => 'Bogotá',
        ]);

        Client::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'is_demo' => true,
            'document_type' => 'CC',
            'document_number' => "10000000{$suffix}2",
            'first_name' => 'María',
            'last_name' => 'López Hernández',
            'email' => "maria.lopez.demo{$suffix}@ejemplo.com",
            'phone' => '3209876543',
            'city' => 'Medellín',
        ]);

        Client::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'is_demo' => true,
            'document_type' => 'NIT',
            'document_number' => "9001234{$suffix}",
            'first_name' => 'Empresa',
            'last_name' => 'Ejemplo S.A.S.',
            'email' => "empresa.demo{$suffix}@ejemplo.com",
            'phone' => '6014567890',
            'city' => 'Bogotá',
        ]);
    }

    private function createDemoCases(Firm $firm, User $owner): array
    {
        $civilType = CaseType::where('name', 'Civil')->first();
        $civilFlow = CaseFlow::where('case_type_id', $civilType->id)->first();
        $clients = Client::where('firm_id', $firm->id)->get();

        $case1 = LegalCase::create([
            'firm_id' => $firm->id,
            'is_demo' => true,
            'case_number' => sprintf('LW-%04d-2026', $firm->id * 100 + 1),
            'external_case_number' => sprintf('110013105005%011d', $firm->id * 100 + 1),
            'title' => 'PROCESOS VERBALES - Juan Perez vs Inmobiliaria Centro S.A.S.',
            'description' => "Importado desde Rama Judicial\nTipo: Codigo General del Proceso\nClase: PROCESOS VERBALES\nDepartamento: BOGOTA\nDespacho: JUZGADO 005 CIVIL DEL CIRCUITO DE BOGOTA\nPonente: Roberto Gomez Martinez\n\nSujetos procesales:\n- Demandante/accionante: JUAN PEREZ GARCIA\n- Demandado/indiciado/causante: INMOBILIARIA CENTRO S.A.S.\n- Defensor Privado: CARLOS RAMIREZ LOPEZ",
            'case_type_id' => $civilType->id,
            'case_flow_id' => $civilFlow?->id,
            'client_id' => $clients[0]->id,
            'user_id' => $owner->id,
            'status' => 'en_progreso',
            'priority' => 'alta',
            'court' => 'JUZGADO 005 CIVIL DEL CIRCUITO DE BOGOTA',
            'judge' => 'Roberto Gomez Martinez',
            'opposing_party' => 'INMOBILIARIA CENTRO S.A.S.',
            'started_at' => now()->subMonths(3),
            'last_tyba_sync' => now()->subHours(6),
            'tyba_data' => [
                'codigo_proceso' => '11001310500520230045600',
                'tipo_proceso' => 'Codigo General del Proceso',
                'clase_proceso' => 'PROCESOS VERBALES',
                'subclase' => 'En general / Sin subclase',
                'departamento' => 'BOGOTA',
                'especialidad' => 'Civil',
                'corporacion' => 'Juzgado de Circuito',
                'numero_despacho' => '005',
                'despacho' => 'JUZGADO 005 CIVIL DEL CIRCUITO DE BOGOTA',
                'ponente' => 'Roberto Gomez Martinez',
                'fecha_publicacion' => now()->subMonths(3)->format('d/m/Y'),
                'fecha_ultima_actuacion' => now()->subDays(5)->format('d/m/Y'),
                'ubicacion' => 'Software: Justicia XXI Web',
            ],
        ]);

        $laboralType = CaseType::where('name', 'Laboral')->first();
        $laboralFlow = CaseFlow::where('case_type_id', $laboralType->id)->first();

        $case2 = LegalCase::create([
            'firm_id' => $firm->id,
            'is_demo' => true,
            'case_number' => sprintf('LW-%04d-2026', $firm->id * 100 + 2),
            'external_case_number' => sprintf('050013105013%011d', $firm->id * 100 + 2),
            'title' => 'Demanda laboral - Maria Lopez vs Textiles del Sur S.A.',
            'description' => "Importado desde Rama Judicial\nTipo: Codigo Procesal del Trabajo\nClase: PROCESO ORDINARIO LABORAL\nDepartamento: ANTIOQUIA\nDespacho: JUZGADO 013 LABORAL DEL CIRCUITO DE MEDELLIN\n\nSujetos procesales:\n- Demandante/accionante: MARIA LOPEZ HERNANDEZ\n- Demandado/indiciado/causante: TEXTILES DEL SUR S.A.",
            'case_type_id' => $laboralType->id,
            'case_flow_id' => $laboralFlow?->id,
            'client_id' => $clients[1]->id,
            'user_id' => $owner->id,
            'status' => 'abierto',
            'priority' => 'media',
            'court' => 'JUZGADO 013 LABORAL DEL CIRCUITO DE MEDELLIN',
            'judge' => 'Ana Maria Restrepo Velez',
            'opposing_party' => 'TEXTILES DEL SUR S.A.',
            'started_at' => now()->subWeeks(2),
            'last_tyba_sync' => now()->subHours(6),
            'tyba_data' => [
                'codigo_proceso' => '05001310501320240012300',
                'tipo_proceso' => 'Codigo Procesal del Trabajo',
                'clase_proceso' => 'PROCESO ORDINARIO LABORAL',
                'departamento' => 'ANTIOQUIA',
                'especialidad' => 'Laboral',
                'despacho' => 'JUZGADO 013 LABORAL DEL CIRCUITO DE MEDELLIN',
                'ponente' => 'Ana Maria Restrepo Velez',
                'fecha_publicacion' => now()->subWeeks(2)->format('d/m/Y'),
                'fecha_ultima_actuacion' => now()->subDays(3)->format('d/m/Y'),
            ],
        ]);

        $familiaType = CaseType::where('name', 'Familia')->first();
        $familiaFlow = CaseFlow::where('case_type_id', $familiaType->id)->first();

        $case3 = LegalCase::create([
            'firm_id' => $firm->id,
            'is_demo' => true,
            'case_number' => sprintf('LW-%04d-2026', $firm->id * 100 + 3),
            'title' => 'Divorcio contencioso - Juan Perez vs Sandra Milena Torres',
            'description' => "Tipo: Codigo General del Proceso\nClase: DIVORCIO CONTENCIOSO\nDespacho: JUZGADO 002 DE FAMILIA DE BOGOTA\n\nSujetos procesales:\n- Demandante/accionante: JUAN PEREZ GARCIA\n- Demandado/indiciado/causante: SANDRA MILENA TORRES RUIZ",
            'case_type_id' => $familiaType->id,
            'case_flow_id' => $familiaFlow?->id,
            'client_id' => $clients[0]->id,
            'user_id' => $owner->id,
            'status' => 'en_progreso',
            'priority' => 'media',
            'court' => 'JUZGADO 002 DE FAMILIA DE BOGOTA',
            'judge' => 'Patricia Calderon Rios',
            'opposing_party' => 'SANDRA MILENA TORRES RUIZ',
            'started_at' => now()->subMonths(1),
        ]);

        // Actuaciones caso 1 (civil - mas avanzado)
        $case1Events = [
            ['title' => 'Radicacion de demanda', 'days' => 0, 'type' => 'actuacion'],
            ['title' => 'Auto Admite demanda', 'days' => 8, 'type' => 'auto'],
            ['title' => 'Fijacion Estado', 'days' => 9, 'type' => 'notificacion'],
            ['title' => 'Notificacion personal al demandado', 'days' => 20, 'type' => 'notificacion'],
            ['title' => 'Contestacion de demanda', 'days' => 40, 'type' => 'actuacion'],
            ['title' => 'Agregar Memorial', 'days' => 42, 'type' => 'memorial'],
            ['title' => 'Auto Fija Fecha audiencia inicial', 'days' => 50, 'type' => 'auto'],
            ['title' => 'Fijacion Estado', 'days' => 51, 'type' => 'notificacion'],
            ['title' => 'Audiencia inicial - Art. 372 CGP', 'days' => 70, 'type' => 'audiencia'],
            ['title' => 'Auto Decide excepciones previas', 'days' => 70, 'type' => 'auto'],
            ['title' => 'Auto Ordena practica de pruebas', 'days' => 75, 'type' => 'auto'],
            ['title' => 'Agregar Memorial', 'days' => 80, 'type' => 'memorial'],
        ];

        foreach ($case1Events as $e) {
            CaseEvent::create([
                'legal_case_id' => $case1->id,
                'title' => $e['title'],
                'event_date' => $case1->started_at->copy()->addDays($e['days']),
                'event_type' => $e['type'],
                'description' => 'Sincronizado desde Rama Judicial. Radicado: '.$case1->external_case_number,
                'user_id' => $owner->id,
            ]);
        }

        // Actuaciones caso 2 (laboral - reciente)
        $case2Events = [
            ['title' => 'Radicacion de demanda', 'days' => 0, 'type' => 'actuacion'],
            ['title' => 'Auto Admite demanda', 'days' => 5, 'type' => 'auto'],
            ['title' => 'Fijacion Estado', 'days' => 6, 'type' => 'notificacion'],
            ['title' => 'Traslado al demandado', 'days' => 8, 'type' => 'actuacion'],
        ];

        foreach ($case2Events as $e) {
            CaseEvent::create([
                'legal_case_id' => $case2->id,
                'title' => $e['title'],
                'event_date' => $case2->started_at->copy()->addDays($e['days']),
                'event_type' => $e['type'],
                'description' => 'Sincronizado desde Rama Judicial. Radicado: '.$case2->external_case_number,
                'user_id' => $owner->id,
            ]);
        }

        // Actuaciones caso 3 (familia)
        $case3Events = [
            ['title' => 'Radicacion de demanda', 'days' => 0, 'type' => 'actuacion'],
            ['title' => 'Auto Admite demanda', 'days' => 7, 'type' => 'auto'],
            ['title' => 'Fijacion Estado', 'days' => 8, 'type' => 'notificacion'],
            ['title' => 'Auto Ordena notificacion', 'days' => 10, 'type' => 'auto'],
            ['title' => 'Notificacion personal', 'days' => 18, 'type' => 'notificacion'],
            ['title' => 'Agregar Memorial', 'days' => 25, 'type' => 'memorial'],
        ];

        foreach ($case3Events as $e) {
            CaseEvent::create([
                'legal_case_id' => $case3->id,
                'title' => $e['title'],
                'event_date' => $case3->started_at->copy()->addDays($e['days']),
                'event_type' => $e['type'],
                'user_id' => $owner->id,
            ]);
        }

        // Flujo procesal para cada caso
        foreach ([$case1, $case2, $case3] as $idx => $case) {
            if ($case->case_flow_id) {
                $steps = FlowStep::where('case_flow_id', $case->case_flow_id)->orderBy('order')->get();
                $completedCount = $idx === 0 ? 4 : ($idx === 1 ? 2 : 3);
                foreach ($steps as $i => $step) {
                    CaseFlowProgress::create([
                        'legal_case_id' => $case->id,
                        'flow_step_id' => $step->id,
                        'status' => $i < $completedCount ? 'completado' : ($i === $completedCount ? 'en_progreso' : 'pendiente'),
                        'completed_at' => $i < $completedCount ? now()->subDays(($completedCount - $i) * 12) : null,
                        'completed_by' => $i < $completedCount ? $owner->id : null,
                    ]);
                }
            }
        }

        return [$case1, $case2, $case3];
    }

    private function createDemoReminders(Firm $firm, User $owner, array $cases): void
    {
        // Caso 1 (Civil) - audiencia proxima
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => $cases[0]->id ?? null,
            'title' => 'Audiencia inicial - Juzgado 5 Civil',
            'description' => 'Preparar alegatos y revisar pruebas documentales antes de la audiencia.',
            'type' => 'audiencia',
            'priority' => 'alta',
            'due_date' => now()->addDays(5)->setHour(9)->setMinute(0),
            'remind_at' => now()->addDays(4)->setHour(8)->setMinute(0),
        ]);

        // Caso 1 (Civil) - tarea
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => $cases[0]->id ?? null,
            'title' => 'Reunion con cliente para revisar estrategia',
            'description' => 'Repasar pruebas adicionales y testigos para audiencia de instruccion.',
            'type' => 'reunion',
            'priority' => 'media',
            'due_date' => now()->addDays(10)->setHour(15)->setMinute(0),
            'remind_at' => now()->addDays(9)->setHour(8)->setMinute(0),
        ]);

        // Caso 2 (Laboral) - vencimiento urgente
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => $cases[1]->id ?? null,
            'title' => 'Vencimiento termino para contestar demanda',
            'description' => 'Revisar expediente y preparar contestacion con excepciones.',
            'type' => 'vencimiento',
            'priority' => 'urgente',
            'due_date' => now()->addDays(2)->setHour(17)->setMinute(0),
            'remind_at' => now()->addDay()->setHour(8)->setMinute(0),
        ]);

        // Caso 2 (Laboral) - audiencia
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => $cases[1]->id ?? null,
            'title' => 'Audiencia de conciliacion laboral',
            'description' => 'Llevar liquidacion de prestaciones, contrato laboral y comprobantes de pago.',
            'type' => 'audiencia',
            'priority' => 'alta',
            'due_date' => now()->addDays(20)->setHour(10)->setMinute(0),
            'remind_at' => now()->addDays(19)->setHour(8)->setMinute(0),
        ]);

        // Caso 3 (Familia) - audiencia conciliacion
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => $cases[2]->id ?? null,
            'title' => 'Audiencia de conciliacion en divorcio',
            'description' => 'Asistir con cliente, llevar pruebas de bienes comunes y propuesta de custodia compartida.',
            'type' => 'audiencia',
            'priority' => 'alta',
            'due_date' => now()->addDays(15)->setHour(11)->setMinute(0),
            'remind_at' => now()->addDays(14)->setHour(8)->setMinute(0),
        ]);

        // Caso 3 (Familia) - tarea pendiente
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => $cases[2]->id ?? null,
            'title' => 'Solicitar declaracion extrajuicio en notaria',
            'description' => 'Cliente debe ir a la notaria con dos testigos para la declaracion de bienes.',
            'type' => 'tarea',
            'priority' => 'media',
            'due_date' => now()->addDays(7)->setHour(17)->setMinute(0),
            'remind_at' => now()->addDays(6)->setHour(8)->setMinute(0),
        ]);

        // Recordatorio vencido (sin caso) para mostrar el estado de alerta
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'legal_case_id' => null,
            'title' => 'Renovar pagos de tarjeta profesional',
            'description' => 'Tramite anual ante el Consejo Superior de la Judicatura.',
            'type' => 'tarea',
            'priority' => 'media',
            'due_date' => now()->subDays(3)->setHour(17)->setMinute(0),
            'remind_at' => now()->subDays(4)->setHour(8)->setMinute(0),
        ]);
    }

    private function createDemoBilling(User $owner, array $cases): void
    {
        // Caso 1: civil avanzado - varias entradas
        $case1 = $cases[0];
        $billingData = [
            ['type' => 'hora', 'desc' => 'Estudio de expediente y preparacion de demanda', 'hours' => 4, 'rate' => 150000, 'days_ago' => 85],
            ['type' => 'hora', 'desc' => 'Radicacion de demanda en juzgado', 'hours' => 1.5, 'rate' => 150000, 'days_ago' => 82],
            ['type' => 'gasto', 'desc' => 'Copias autenticadas del contrato (4 folios)', 'hours' => null, 'rate' => null, 'amount' => 12000, 'days_ago' => 82],
            ['type' => 'hora', 'desc' => 'Revision auto admisorio y preparacion de notificacion', 'hours' => 2, 'rate' => 150000, 'days_ago' => 74],
            ['type' => 'gasto', 'desc' => 'Servicio de notificacion personal', 'hours' => null, 'rate' => null, 'amount' => 85000, 'days_ago' => 62],
            ['type' => 'hora', 'desc' => 'Revision contestacion de demanda', 'hours' => 3, 'rate' => 150000, 'days_ago' => 42],
            ['type' => 'hora', 'desc' => 'Preparacion para audiencia inicial', 'hours' => 5, 'rate' => 150000, 'days_ago' => 15],
            ['type' => 'hora', 'desc' => 'Audiencia inicial - Art. 372 CGP', 'hours' => 3, 'rate' => 200000, 'days_ago' => 12],
            ['type' => 'concepto', 'desc' => 'Honorarios fijos mensuales - Marzo 2026', 'hours' => null, 'rate' => null, 'amount' => 500000, 'days_ago' => 30],
        ];

        foreach ($billingData as $b) {
            $amount = $b['amount'] ?? ($b['hours'] * $b['rate']);
            BillingEntry::create([
                'legal_case_id' => $case1->id,
                'user_id' => $owner->id,
                'type' => $b['type'],
                'description' => $b['desc'],
                'hours' => $b['hours'],
                'rate_per_hour' => $b['rate'],
                'amount' => $amount,
                'entry_date' => now()->subDays($b['days_ago']),
                'is_billable' => true,
                'is_billed' => $b['days_ago'] > 40,
            ]);
        }

        // Caso 2: laboral reciente - pocas entradas
        BillingEntry::create([
            'legal_case_id' => $cases[1]->id,
            'user_id' => $owner->id,
            'type' => 'hora',
            'description' => 'Consulta inicial y estudio del caso',
            'hours' => 2,
            'rate_per_hour' => 120000,
            'amount' => 240000,
            'entry_date' => now()->subDays(14),
            'is_billable' => true,
        ]);

        BillingEntry::create([
            'legal_case_id' => $cases[1]->id,
            'user_id' => $owner->id,
            'type' => 'hora',
            'description' => 'Redaccion de demanda laboral',
            'hours' => 6,
            'rate_per_hour' => 120000,
            'amount' => 720000,
            'entry_date' => now()->subDays(12),
            'is_billable' => true,
        ]);

        BillingEntry::create([
            'legal_case_id' => $cases[1]->id,
            'user_id' => $owner->id,
            'type' => 'gasto',
            'description' => 'Copias autenticadas de comprobantes de pago',
            'amount' => 18000,
            'entry_date' => now()->subDays(10),
            'is_billable' => true,
        ]);

        // Caso 3 (Familia / Divorcio) - mezcla horas, gastos y honorarios
        $case3 = $cases[2];
        $billingCase3 = [
            ['type' => 'hora', 'desc' => 'Consulta inicial y entrevista con cliente', 'hours' => 1.5, 'rate' => 130000, 'days_ago' => 28],
            ['type' => 'hora', 'desc' => 'Estudio del registro civil de matrimonio y bienes comunes', 'hours' => 2, 'rate' => 130000, 'days_ago' => 25],
            ['type' => 'gasto', 'desc' => 'Copia autenticada del registro civil de matrimonio', 'amount' => 19000, 'days_ago' => 25],
            ['type' => 'hora', 'desc' => 'Redaccion de demanda de divorcio contencioso', 'hours' => 5, 'rate' => 130000, 'days_ago' => 22],
            ['type' => 'gasto', 'desc' => 'Radicacion fisica en juzgado y copias del expediente', 'amount' => 28000, 'days_ago' => 21],
            ['type' => 'concepto', 'desc' => 'Honorarios fijos - 30% al iniciar el proceso', 'amount' => 1500000, 'days_ago' => 22],
            ['type' => 'hora', 'desc' => 'Revision auto admisorio y preparacion notificacion personal', 'hours' => 2, 'rate' => 130000, 'days_ago' => 14],
            ['type' => 'gasto', 'desc' => 'Servicio de notificacion personal', 'amount' => 90000, 'days_ago' => 12],
        ];

        foreach ($billingCase3 as $b) {
            $amount = $b['amount'] ?? ($b['hours'] * $b['rate']);
            BillingEntry::create([
                'legal_case_id' => $case3->id,
                'user_id' => $owner->id,
                'type' => $b['type'],
                'description' => $b['desc'],
                'hours' => $b['hours'] ?? null,
                'rate_per_hour' => $b['rate'] ?? null,
                'amount' => $amount,
                'entry_date' => now()->subDays($b['days_ago']),
                'is_billable' => true,
                'is_billed' => $b['days_ago'] > 20,
            ]);
        }
    }

    private function createDemoDocuments(User $owner, array $cases): void
    {
        // Caso 1 (Civil - Arrendamiento)
        $case1 = $cases[0];
        $docs1 = [
            ['name' => 'Cedula de ciudadania del cliente', 'resp' => 'cliente', 'entity' => 'Registraduria Nacional', 'cost' => 0, 'status' => 'recibido', 'priority' => 'alta'],
            ['name' => 'Contrato de arrendamiento original', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'recibido', 'priority' => 'alta'],
            ['name' => 'Certificado de existencia demandado', 'resp' => 'abogado', 'entity' => 'Camara de Comercio', 'cost' => 22000, 'status' => 'recibido', 'priority' => 'alta'],
            ['name' => 'Estados de cuenta con los pagos pendientes', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'recibido', 'priority' => 'media'],
            ['name' => 'Poder especial autenticado', 'resp' => 'cliente', 'entity' => 'Notaria', 'cost' => 35000, 'status' => 'recibido', 'priority' => 'urgente'],
            ['name' => 'Copia de comunicaciones previas', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'en_tramite', 'priority' => 'media'],
        ];

        foreach ($docs1 as $d) {
            Document::create([
                'legal_case_id' => $case1->id,
                'name' => $d['name'],
                'responsible' => $d['resp'],
                'entity' => $d['entity'],
                'estimated_cost' => $d['cost'],
                'status' => $d['status'],
                'priority' => $d['priority'],
                'received_at' => $d['status'] === 'recibido' ? now()->subDays(rand(20, 80)) : null,
                'assigned_to' => $owner->id,
            ]);
        }

        // Caso 2 (Laboral)
        $case2 = $cases[1];
        $docs2 = [
            ['name' => 'Cedula de ciudadania del cliente', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'recibido', 'priority' => 'alta'],
            ['name' => 'Contrato laboral', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'recibido', 'priority' => 'urgente'],
            ['name' => 'Certificado laboral con funciones y salario', 'resp' => 'cliente', 'entity' => 'Empleador', 'cost' => 0, 'status' => 'solicitado', 'priority' => 'alta'],
            ['name' => 'Comprobantes de pago ultimos 12 meses', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'en_tramite', 'priority' => 'alta'],
            ['name' => 'Carta de terminacion del contrato', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'recibido', 'priority' => 'urgente'],
            ['name' => 'Historia clinica laboral (si aplica)', 'resp' => 'cliente', 'entity' => 'EPS', 'cost' => 15000, 'status' => 'pendiente', 'priority' => 'baja'],
        ];

        foreach ($docs2 as $d) {
            Document::create([
                'legal_case_id' => $case2->id,
                'name' => $d['name'],
                'responsible' => $d['resp'],
                'entity' => $d['entity'],
                'estimated_cost' => $d['cost'],
                'status' => $d['status'],
                'priority' => $d['priority'],
                'received_at' => $d['status'] === 'recibido' ? now()->subDays(rand(5, 14)) : null,
                'assigned_to' => $owner->id,
            ]);
        }

        // Caso 3 (Familia - Divorcio)
        $case3 = $cases[2];
        $docs3 = [
            ['name' => 'Registro civil de matrimonio', 'resp' => 'cliente', 'entity' => 'Notaria', 'cost' => 19000, 'status' => 'recibido', 'priority' => 'urgente'],
            ['name' => 'Cedulas de ambos conyuges', 'resp' => 'cliente', 'entity' => null, 'cost' => 0, 'status' => 'recibido', 'priority' => 'alta'],
            ['name' => 'Registros civiles de hijos menores', 'resp' => 'cliente', 'entity' => 'Notaria', 'cost' => 38000, 'status' => 'en_tramite', 'priority' => 'alta'],
            ['name' => 'Declaracion extrajuicio de bienes', 'resp' => 'cliente', 'entity' => 'Notaria', 'cost' => 45000, 'status' => 'solicitado', 'priority' => 'media'],
            ['name' => 'Certificado de tradicion del inmueble', 'resp' => 'abogado', 'entity' => 'Oficina de Instrumentos Publicos', 'cost' => 17000, 'status' => 'pendiente', 'priority' => 'media'],
            ['name' => 'Poder especial', 'resp' => 'cliente', 'entity' => 'Notaria', 'cost' => 35000, 'status' => 'recibido', 'priority' => 'urgente'],
        ];

        foreach ($docs3 as $d) {
            Document::create([
                'legal_case_id' => $case3->id,
                'name' => $d['name'],
                'responsible' => $d['resp'],
                'entity' => $d['entity'],
                'estimated_cost' => $d['cost'],
                'status' => $d['status'],
                'priority' => $d['priority'],
                'received_at' => $d['status'] === 'recibido' ? now()->subDays(rand(10, 30)) : null,
                'assigned_to' => $owner->id,
            ]);
        }
    }

    private function createDemoSyncLogs(array $cases): void
    {
        // Logs de sincronizacion para caso 1
        TybaSyncLog::create([
            'legal_case_id' => $cases[0]->id,
            'status' => 'ok',
            'nuevas_actuaciones' => 12,
            'mensaje' => '12 nueva(s) actuacion(es) de 12 totales',
            'origen' => 'manual',
            'created_at' => now()->subDays(7),
        ]);

        TybaSyncLog::create([
            'legal_case_id' => $cases[0]->id,
            'status' => 'sin_cambios',
            'nuevas_actuaciones' => 0,
            'mensaje' => 'Sin novedades. 12 actuaciones verificadas',
            'origen' => 'automatico',
            'created_at' => now()->subDays(1),
        ]);

        TybaSyncLog::create([
            'legal_case_id' => $cases[0]->id,
            'status' => 'ok',
            'nuevas_actuaciones' => 1,
            'mensaje' => '1 nueva(s) actuacion(es) de 12 totales',
            'origen' => 'automatico',
            'created_at' => now()->subHours(6),
        ]);

        // Logs para caso 2
        TybaSyncLog::create([
            'legal_case_id' => $cases[1]->id,
            'status' => 'ok',
            'nuevas_actuaciones' => 4,
            'mensaje' => '4 nueva(s) actuacion(es) de 4 totales',
            'origen' => 'manual',
            'created_at' => now()->subDays(3),
        ]);
    }

    private function createFlow(CaseType $type, string $name, string $description, array $steps): void
    {
        $flow = CaseFlow::create([
            'case_type_id' => $type->id,
            'name' => $name,
            'description' => $description,
        ]);

        foreach ($steps as $step) {
            FlowStep::create(array_merge($step, ['case_flow_id' => $flow->id]));
        }
    }
}
