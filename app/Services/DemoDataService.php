<?php

namespace App\Services;

use App\Models\CaseEvent;
use App\Models\CaseFlow;
use App\Models\CaseFlowProgress;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;

class DemoDataService
{
    public function seedForFirm(Firm $firm, User $owner): void
    {
        $this->createCaseTypesAndFlows();
        $this->createDemoClients($firm, $owner);
        $cases = $this->createDemoCases($firm, $owner);
        $this->createDemoReminders($firm, $owner, $cases);
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
        Client::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'is_demo' => true,
            'document_type' => 'CC',
            'document_number' => '1234567890',
            'first_name' => 'Juan',
            'last_name' => 'Pérez García',
            'email' => 'juan.perez@ejemplo.com',
            'phone' => '3101234567',
            'city' => 'Bogotá',
        ]);

        Client::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'is_demo' => true,
            'document_type' => 'CC',
            'document_number' => '9876543210',
            'first_name' => 'María',
            'last_name' => 'López Hernández',
            'email' => 'maria.lopez@ejemplo.com',
            'phone' => '3209876543',
            'city' => 'Medellín',
        ]);

        Client::create([
            'firm_id' => $firm->id,
            'user_id' => $owner->id,
            'is_demo' => true,
            'document_type' => 'NIT',
            'document_number' => '900123456-1',
            'first_name' => 'Empresa',
            'last_name' => 'Ejemplo S.A.S.',
            'email' => 'contacto@empresa.com',
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
            'case_number' => 'LW-0001-2026',
            'title' => 'Cobro de obligación contractual - Contrato de arrendamiento',
            'case_type_id' => $civilType->id,
            'case_flow_id' => $civilFlow?->id,
            'client_id' => $clients[0]->id,
            'user_id' => $owner->id,
            'status' => 'en_progreso',
            'priority' => 'alta',
            'court' => 'Juzgado 5 Civil del Circuito de Bogotá',
            'judge' => 'Dr. Roberto Gómez',
            'started_at' => now()->subMonths(3),
        ]);

        $laboralType = CaseType::where('name', 'Laboral')->first();
        $laboralFlow = CaseFlow::where('case_type_id', $laboralType->id)->first();

        $case2 = LegalCase::create([
            'firm_id' => $firm->id,
            'is_demo' => true,
            'case_number' => 'LW-0002-2026',
            'title' => 'Demanda laboral por despido sin justa causa',
            'case_type_id' => $laboralType->id,
            'case_flow_id' => $laboralFlow?->id,
            'client_id' => $clients[1]->id,
            'user_id' => $owner->id,
            'status' => 'abierto',
            'priority' => 'media',
            'started_at' => now()->subWeeks(2),
        ]);

        $familiaType = CaseType::where('name', 'Familia')->first();
        $familiaFlow = CaseFlow::where('case_type_id', $familiaType->id)->first();

        $case3 = LegalCase::create([
            'firm_id' => $firm->id,
            'is_demo' => true,
            'case_number' => 'LW-0003-2026',
            'title' => 'Divorcio contencioso por causal de abandono',
            'case_type_id' => $familiaType->id,
            'case_flow_id' => $familiaFlow?->id,
            'client_id' => $clients[0]->id,
            'user_id' => $owner->id,
            'status' => 'en_progreso',
            'priority' => 'media',
            'started_at' => now()->subMonths(1),
        ]);

        foreach ([$case1, $case2, $case3] as $case) {
            CaseEvent::create([
                'legal_case_id' => $case->id,
                'title' => 'Radicación de demanda',
                'event_date' => $case->started_at,
                'event_type' => 'actuacion',
                'user_id' => $owner->id,
            ]);

            CaseEvent::create([
                'legal_case_id' => $case->id,
                'title' => 'Auto admisorio de demanda',
                'event_date' => $case->started_at->addDays(8),
                'event_type' => 'auto',
                'user_id' => $owner->id,
            ]);

            if ($case->case_flow_id) {
                $steps = FlowStep::where('case_flow_id', $case->case_flow_id)->orderBy('order')->get();
                foreach ($steps as $i => $step) {
                    CaseFlowProgress::create([
                        'legal_case_id' => $case->id,
                        'flow_step_id' => $step->id,
                        'status' => $i < 2 ? 'completado' : ($i === 2 ? 'en_progreso' : 'pendiente'),
                        'completed_at' => $i < 2 ? now()->subDays((2 - $i) * 15) : null,
                        'completed_by' => $i < 2 ? $owner->id : null,
                    ]);
                }
            }
        }

        return [$case1, $case2, $case3];
    }

    private function createDemoReminders(Firm $firm, User $owner, array $cases): void
    {
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
