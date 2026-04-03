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

        $lawyer2 = User::factory()->create([
            'name' => 'María López',
            'email' => 'maria@legalweb.co',
        ]);

        $caseTypes = collect([
            ['name' => 'Civil', 'description' => 'Procesos civiles declarativos y ejecutivos', 'color' => '#3B82F6'],
            ['name' => 'Laboral', 'description' => 'Procesos laborales y de seguridad social', 'color' => '#10B981'],
            ['name' => 'Penal', 'description' => 'Procesos penales - Sistema Penal Acusatorio (Ley 906/2004)', 'color' => '#EF4444'],
            ['name' => 'Administrativo', 'description' => 'Procesos contencioso administrativos (CPACA)', 'color' => '#F59E0B'],
            ['name' => 'Comercial', 'description' => 'Procesos comerciales y societarios', 'color' => '#8B5CF6'],
            ['name' => 'Familia', 'description' => 'Procesos de familia, custodia, alimentos y divorcio', 'color' => '#EC4899'],
        ])->map(fn (array $data) => CaseType::create($data));

        $clients = Client::factory(15)->create(['user_id' => $lawyer->id]);

        $civilType = $caseTypes->firstWhere('name', 'Civil');
        $laboralType = $caseTypes->firstWhere('name', 'Laboral');
        $penalType = $caseTypes->firstWhere('name', 'Penal');
        $adminType = $caseTypes->firstWhere('name', 'Administrativo');
        $comercialType = $caseTypes->firstWhere('name', 'Comercial');
        $familiaType = $caseTypes->firstWhere('name', 'Familia');

        // =====================================================
        // FLUJOS BASADOS EN LEGISLACIÓN COLOMBIANA
        // =====================================================

        // --- CIVIL: Proceso Verbal (CGP Art. 368-373) ---
        $civilFlow = $this->createFlow($civilType, 'Proceso Verbal Civil', 'Proceso verbal de mayor y menor cuantía (CGP Art. 368-373)', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null, 'description' => 'Radicación de la demanda con anexos (CGP Art. 82)'],
            ['name' => 'Admisión de demanda', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto admisorio o inadmisorio (CGP Art. 90)'],
            ['name' => 'Notificación al demandado', 'order' => 3, 'days_limit' => 25, 'description' => 'Notificación personal o por aviso (CGP Art. 291-295)'],
            ['name' => 'Traslado de la demanda', 'order' => 4, 'days_limit' => 20, 'description' => 'Término para contestar la demanda (CGP Art. 369)'],
            ['name' => 'Contestación de demanda', 'order' => 5, 'days_limit' => 20, 'description' => 'Contestación, excepciones y demanda de reconvención'],
            ['name' => 'Audiencia inicial (Art. 372)', 'order' => 6, 'days_limit' => 30, 'description' => 'Saneamiento, fijación de hechos, conciliación, decreto de pruebas'],
            ['name' => 'Audiencia de instrucción y juzgamiento (Art. 373)', 'order' => 7, 'days_limit' => 40, 'description' => 'Práctica de pruebas, alegatos y sentencia en audiencia'],
            ['name' => 'Sentencia', 'order' => 8, 'days_limit' => 40, 'description' => 'Sentencia oral o escrita dentro del término legal'],
            ['name' => 'Recurso de apelación', 'order' => 9, 'days_limit' => 3, 'is_required' => false, 'description' => 'Apelación dentro de los 3 días siguientes a la notificación'],
        ]);

        // --- CIVIL: Proceso Ejecutivo (CGP Art. 422-445) ---
        $ejecutivoFlow = $this->createFlow($civilType, 'Proceso Ejecutivo', 'Cobro de obligaciones con título ejecutivo (CGP Art. 422-445)', [
            ['name' => 'Presentación de demanda ejecutiva', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda con título ejecutivo que contenga obligación clara, expresa y exigible'],
            ['name' => 'Mandamiento de pago', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto que ordena el pago (CGP Art. 430)'],
            ['name' => 'Notificación del mandamiento', 'order' => 3, 'days_limit' => 25, 'description' => 'Notificación personal al ejecutado'],
            ['name' => 'Excepciones de mérito', 'order' => 4, 'days_limit' => 10, 'description' => 'Término para proponer excepciones (CGP Art. 442)'],
            ['name' => 'Medidas cautelares', 'order' => 5, 'days_limit' => 15, 'description' => 'Embargo y secuestro de bienes'],
            ['name' => 'Sentencia / Orden de seguir adelante', 'order' => 6, 'days_limit' => 30, 'description' => 'Resolución de excepciones o auto de seguir adelante'],
            ['name' => 'Liquidación del crédito', 'order' => 7, 'days_limit' => 15, 'description' => 'Liquidación de capital, intereses y costas'],
            ['name' => 'Remate de bienes', 'order' => 8, 'days_limit' => 30, 'is_required' => false, 'description' => 'Subasta de bienes embargados si no hay pago'],
        ]);

        // --- LABORAL: Proceso Ordinario Laboral (CPT Art. 70-84) ---
        $laboralFlow = $this->createFlow($laboralType, 'Proceso Ordinario Laboral', 'Proceso ordinario de primera instancia (CPT Art. 70-84)', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null, 'description' => 'Radicación ante Juzgado Laboral del Circuito'],
            ['name' => 'Admisión y auto de traslado', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto admisorio con traslado al demandado'],
            ['name' => 'Notificación al demandado', 'order' => 3, 'days_limit' => 25, 'description' => 'Notificación personal o por aviso al empleador'],
            ['name' => 'Contestación de demanda', 'order' => 4, 'days_limit' => 10, 'description' => 'Contestación dentro del término de traslado (CPT Art. 21)'],
            ['name' => 'Audiencia de conciliación, decisión de excepciones y fijación del litigio', 'order' => 5, 'days_limit' => 30, 'description' => 'Primera audiencia obligatoria (CPT Art. 77)'],
            ['name' => 'Audiencia de trámite y juzgamiento', 'order' => 6, 'days_limit' => 45, 'description' => 'Práctica de pruebas, alegatos y fallo (CPT Art. 80)'],
            ['name' => 'Sentencia', 'order' => 7, 'days_limit' => 30, 'description' => 'Sentencia oral en audiencia o escrita'],
            ['name' => 'Recurso de apelación', 'order' => 8, 'days_limit' => 5, 'is_required' => false, 'description' => 'Apelación ante Tribunal Superior - Sala Laboral'],
        ]);

        // --- PENAL: Sistema Penal Acusatorio (Ley 906 de 2004) ---
        $penalFlow = $this->createFlow($penalType, 'Proceso Penal Acusatorio', 'Sistema Penal Acusatorio - Ley 906 de 2004', [
            ['name' => 'Noticia criminal / Denuncia', 'order' => 1, 'days_limit' => null, 'description' => 'Recepción de denuncia, querella o informe de policía'],
            ['name' => 'Indagación preliminar', 'order' => 2, 'days_limit' => 60, 'description' => 'Investigación inicial por la Fiscalía (Art. 175)'],
            ['name' => 'Formulación de imputación', 'order' => 3, 'days_limit' => null, 'description' => 'Audiencia ante Juez de Control de Garantías (Art. 286-294)'],
            ['name' => 'Medida de aseguramiento', 'order' => 4, 'days_limit' => 3, 'is_required' => false, 'description' => 'Solicitud de medida cautelar personal (Art. 306-320)'],
            ['name' => 'Investigación', 'order' => 5, 'days_limit' => 90, 'description' => 'Investigación formal - 90 días con detenido, 120 sin detenido (Art. 175)'],
            ['name' => 'Escrito de acusación', 'order' => 6, 'days_limit' => 30, 'description' => 'Presentación del escrito de acusación por la Fiscalía (Art. 336)'],
            ['name' => 'Audiencia de formulación de acusación', 'order' => 7, 'days_limit' => 30, 'description' => 'Audiencia ante Juez de Conocimiento (Art. 338-342)'],
            ['name' => 'Audiencia preparatoria', 'order' => 8, 'days_limit' => 30, 'description' => 'Descubrimiento probatorio, estipulaciones, solicitud de pruebas (Art. 355-365)'],
            ['name' => 'Juicio oral', 'order' => 9, 'days_limit' => 45, 'description' => 'Práctica de pruebas y alegatos de las partes (Art. 366-397)'],
            ['name' => 'Sentido del fallo', 'order' => 10, 'days_limit' => null, 'description' => 'Anuncio de sentido del fallo: absolutorio o condenatorio (Art. 446)'],
            ['name' => 'Sentencia', 'order' => 11, 'days_limit' => 15, 'description' => 'Lectura de sentencia con motivación (Art. 447)'],
            ['name' => 'Recurso de apelación', 'order' => 12, 'days_limit' => 5, 'is_required' => false, 'description' => 'Apelación ante Tribunal Superior - Sala Penal'],
        ]);

        // --- ADMINISTRATIVO: Nulidad y Restablecimiento del Derecho (CPACA Art. 138) ---
        $adminFlow = $this->createFlow($adminType, 'Nulidad y Restablecimiento del Derecho', 'Medio de control de nulidad y restablecimiento (CPACA Ley 1437/2011)', [
            ['name' => 'Agotamiento de vía gubernativa', 'order' => 1, 'days_limit' => null, 'description' => 'Recurso de reposición y/o apelación ante la entidad'],
            ['name' => 'Solicitud de conciliación extrajudicial', 'order' => 2, 'days_limit' => 30, 'description' => 'Conciliación ante Procuraduría como requisito de procedibilidad (Art. 161)'],
            ['name' => 'Presentación de demanda', 'order' => 3, 'days_limit' => null, 'description' => 'Demanda dentro del término de caducidad: 4 meses (Art. 164)'],
            ['name' => 'Admisión de demanda', 'order' => 4, 'days_limit' => 15, 'description' => 'Auto admisorio y notificación a la entidad demandada'],
            ['name' => 'Contestación de demanda', 'order' => 5, 'days_limit' => 30, 'description' => 'Contestación por la entidad (Art. 172)'],
            ['name' => 'Audiencia inicial', 'order' => 6, 'days_limit' => 30, 'description' => 'Saneamiento, fijación del litigio, conciliación, decreto de pruebas (Art. 180)'],
            ['name' => 'Período probatorio', 'order' => 7, 'days_limit' => 40, 'description' => 'Práctica de pruebas decretadas'],
            ['name' => 'Alegatos de conclusión', 'order' => 8, 'days_limit' => 10, 'description' => 'Traslado para alegatos (Art. 181)'],
            ['name' => 'Sentencia', 'order' => 9, 'days_limit' => 30, 'description' => 'Sentencia de primera instancia'],
            ['name' => 'Recurso de apelación', 'order' => 10, 'days_limit' => 10, 'is_required' => false, 'description' => 'Apelación ante Tribunal o Consejo de Estado'],
        ]);

        // --- COMERCIAL: Proceso de Insolvencia Empresarial (Ley 1116/2006) ---
        $comercialFlow = $this->createFlow($comercialType, 'Reorganización Empresarial', 'Proceso de reorganización (Ley 1116 de 2006)', [
            ['name' => 'Solicitud de admisión', 'order' => 1, 'days_limit' => null, 'description' => 'Solicitud ante Superintendencia de Sociedades con estados financieros'],
            ['name' => 'Auto de admisión', 'order' => 2, 'days_limit' => 15, 'description' => 'Admisión del proceso y designación de promotor (Art. 14)'],
            ['name' => 'Fijación y presentación de créditos', 'order' => 3, 'days_limit' => 20, 'description' => 'Aviso y presentación de acreencias ante promotor'],
            ['name' => 'Calificación y graduación de créditos', 'order' => 4, 'days_limit' => 30, 'description' => 'Proyecto de calificación y graduación por el promotor (Art. 29)'],
            ['name' => 'Objeciones', 'order' => 5, 'days_limit' => 5, 'is_required' => false, 'description' => 'Traslado de objeciones a la calificación'],
            ['name' => 'Negociación del acuerdo', 'order' => 6, 'days_limit' => 120, 'description' => 'Negociación del acuerdo de reorganización con acreedores (Art. 33)'],
            ['name' => 'Celebración del acuerdo', 'order' => 7, 'days_limit' => null, 'description' => 'Votación y aprobación por mayorías legales (Art. 31)'],
            ['name' => 'Confirmación del acuerdo', 'order' => 8, 'days_limit' => 10, 'description' => 'Confirmación judicial del acuerdo'],
            ['name' => 'Ejecución y seguimiento', 'order' => 9, 'days_limit' => null, 'description' => 'Cumplimiento del acuerdo y seguimiento por promotor'],
        ]);

        // --- FAMILIA: Proceso de Divorcio Contencioso (CGP) ---
        $familiaFlow = $this->createFlow($familiaType, 'Divorcio Contencioso', 'Proceso de divorcio contencioso ante Juez de Familia', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda con causal de divorcio (Ley 25/1992, Art. 154 C.C.)'],
            ['name' => 'Admisión de demanda', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto admisorio con medidas cautelares provisionales'],
            ['name' => 'Notificación al cónyuge demandado', 'order' => 3, 'days_limit' => 25, 'description' => 'Notificación personal al cónyuge'],
            ['name' => 'Contestación de demanda', 'order' => 4, 'days_limit' => 20, 'description' => 'Contestación, excepciones o demanda de reconvención'],
            ['name' => 'Audiencia de conciliación', 'order' => 5, 'days_limit' => 30, 'description' => 'Intento de conciliación sobre custodia, alimentos y bienes'],
            ['name' => 'Decreto de pruebas', 'order' => 6, 'days_limit' => 10, 'description' => 'Auto que decreta pruebas solicitadas por las partes'],
            ['name' => 'Práctica de pruebas', 'order' => 7, 'days_limit' => 40, 'description' => 'Testimonios, peritajes, documentos'],
            ['name' => 'Alegatos de conclusión', 'order' => 8, 'days_limit' => 10, 'description' => 'Traslado para alegatos finales'],
            ['name' => 'Sentencia', 'order' => 9, 'days_limit' => 30, 'description' => 'Sentencia que decreta o niega el divorcio, custodia y alimentos'],
            ['name' => 'Registro de sentencia', 'order' => 10, 'days_limit' => 15, 'description' => 'Inscripción en Registro Civil y Notaría'],
        ]);

        $flows = [
            $civilType->id => $civilFlow,
            $laboralType->id => $laboralFlow,
            $penalType->id => $penalFlow,
            $adminType->id => $adminFlow,
            $comercialType->id => $comercialFlow,
            $familiaType->id => $familiaFlow,
        ];

        // Crear casos para cada tipo con su flujo asignado
        $allCases = collect();

        foreach ($caseTypes as $type) {
            $flow = $flows[$type->id];
            $caseLawyer = $type->name === 'Penal' || $type->name === 'Familia' ? $lawyer2 : $lawyer;

            $cases = LegalCase::factory(3)->create([
                'case_type_id' => $type->id,
                'case_flow_id' => $flow->id,
                'client_id' => fn () => $clients->random()->id,
                'user_id' => $caseLawyer->id,
            ]);

            $allCases = $allCases->concat($cases);
        }

        // Agregar actuaciones y documentos a todos los casos
        foreach ($allCases as $case) {
            CaseEvent::factory(rand(2, 5))->create([
                'legal_case_id' => $case->id,
                'user_id' => $case->user_id,
            ]);

            Document::factory(rand(1, 3))->create([
                'legal_case_id' => $case->id,
                'uploaded_by' => $case->user_id,
            ]);
        }

        // Generar progreso de flujo con distintos niveles de avance
        foreach ($allCases as $index => $case) {
            $flowSteps = FlowStep::where('case_flow_id', $case->case_flow_id)
                ->orderBy('order')
                ->get();

            $totalSteps = $flowSteps->count();
            $completedCount = min(($index % 5) + 1, $totalSteps);

            foreach ($flowSteps as $i => $step) {
                if ($i > $completedCount) {
                    CaseFlowProgress::create([
                        'legal_case_id' => $case->id,
                        'flow_step_id' => $step->id,
                        'status' => 'pendiente',
                    ]);
                } elseif ($i === $completedCount) {
                    CaseFlowProgress::create([
                        'legal_case_id' => $case->id,
                        'flow_step_id' => $step->id,
                        'status' => 'en_progreso',
                    ]);
                } else {
                    CaseFlowProgress::create([
                        'legal_case_id' => $case->id,
                        'flow_step_id' => $step->id,
                        'status' => 'completado',
                        'completed_at' => now()->subDays(($completedCount - $i) * 12),
                        'completed_by' => $case->user_id,
                        'notes' => 'Paso completado',
                    ]);
                }
            }
        }
    }

    private function createFlow(CaseType $caseType, string $name, string $description, array $steps): CaseFlow
    {
        $flow = CaseFlow::create([
            'case_type_id' => $caseType->id,
            'name' => $name,
            'description' => $description,
        ]);

        foreach ($steps as $step) {
            FlowStep::create(array_merge(
                ['case_flow_id' => $flow->id, 'is_required' => true],
                $step,
            ));
        }

        return $flow;
    }
}
