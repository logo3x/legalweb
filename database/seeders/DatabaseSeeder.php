<?php

namespace Database\Seeders;

use App\Models\CaseEvent;
use App\Models\CaseFlow;
use App\Models\CaseFlowProgress;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Document;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin LegalWeb',
            'email' => 'admin@legalweb.co',
        ]);

        $lawyer = User::factory()->create([
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos@legalweb.co',
        ]);

        $caseTypes = collect([
            ['name' => 'Civil', 'description' => 'Procesos civiles y de familia', 'color' => '#3B82F6'],
            ['name' => 'Laboral', 'description' => 'Procesos laborales y de seguridad social', 'color' => '#10B981'],
            ['name' => 'Penal', 'description' => 'Procesos penales y disciplinarios', 'color' => '#EF4444'],
            ['name' => 'Administrativo', 'description' => 'Procesos contencioso administrativos', 'color' => '#F59E0B'],
            ['name' => 'Comercial', 'description' => 'Procesos comerciales y societarios', 'color' => '#8B5CF6'],
            ['name' => 'Familia', 'description' => 'Procesos de familia y menores', 'color' => '#EC4899'],
        ])->map(fn (array $data) => CaseType::create($data));

        $clients = Client::factory(10)->create(['user_id' => $lawyer->id]);

        $civilType = $caseTypes->firstWhere('name', 'Civil');
        $laboralType = $caseTypes->firstWhere('name', 'Laboral');

        $civilFlow = CaseFlow::create([
            'case_type_id' => $civilType->id,
            'name' => 'Proceso Ordinario Civil',
            'description' => 'Flujo estándar para procesos ordinarios civiles',
        ]);

        $civilSteps = [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null],
            ['name' => 'Admisión de demanda', 'order' => 2, 'days_limit' => 10],
            ['name' => 'Notificación al demandado', 'order' => 3, 'days_limit' => 25],
            ['name' => 'Contestación de demanda', 'order' => 4, 'days_limit' => 20],
            ['name' => 'Audiencia inicial', 'order' => 5, 'days_limit' => 30],
            ['name' => 'Período probatorio', 'order' => 6, 'days_limit' => 60],
            ['name' => 'Alegatos de conclusión', 'order' => 7, 'days_limit' => 10],
            ['name' => 'Sentencia', 'order' => 8, 'days_limit' => 40],
        ];

        foreach ($civilSteps as $step) {
            FlowStep::create(array_merge($step, ['case_flow_id' => $civilFlow->id]));
        }

        $cases = LegalCase::factory(15)->recycle([$lawyer, $civilType, $laboralType])->recycle($clients)->create();

        foreach ($cases as $case) {
            CaseEvent::factory(rand(2, 6))->create([
                'legal_case_id' => $case->id,
                'user_id' => $lawyer->id,
            ]);

            Document::factory(rand(1, 3))->create([
                'legal_case_id' => $case->id,
                'uploaded_by' => $lawyer->id,
            ]);
        }

        $civilCases = $cases->filter(fn (LegalCase $c) => $c->case_type_id === $civilType->id)->take(3);
        $flowSteps = $civilFlow->steps;

        foreach ($civilCases as $case) {
            $completedCount = rand(1, $flowSteps->count());
            foreach ($flowSteps->take($completedCount) as $i => $step) {
                $isLast = $i === $completedCount - 1;
                CaseFlowProgress::create([
                    'legal_case_id' => $case->id,
                    'flow_step_id' => $step->id,
                    'status' => $isLast ? 'en_progreso' : 'completado',
                    'completed_at' => $isLast ? null : now()->subDays(rand(1, 180)),
                    'completed_by' => $isLast ? null : $lawyer->id,
                ]);
            }
        }
    }
}
