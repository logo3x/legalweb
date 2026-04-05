<?php

namespace Database\Seeders;

use App\Models\CaseEvent;
use App\Models\CaseFlow;
use App\Models\CaseFlowProgress;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\FlowStep;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Planes de suscripción
        $freePlan = Plan::create([
            'name' => 'Gratuito',
            'slug' => 'gratuito',
            'description' => 'Para explorar la plataforma',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_cases' => 3,
            'max_users' => 1,
            'max_storage_mb' => 50,
            'has_portal' => true,
            'has_notifications' => false,
            'sort_order' => 1,
        ]);

        Plan::create([
            'name' => 'Profesional',
            'slug' => 'profesional',
            'description' => 'Para abogados con práctica activa',
            'price_monthly' => 39900,
            'price_yearly' => 199000,
            'max_cases' => 20,
            'max_users' => 3,
            'max_storage_mb' => 1024,
            'has_portal' => true,
            'has_notifications' => true,
            'sort_order' => 2,
        ]);

        Plan::create([
            'name' => 'Firma',
            'slug' => 'firma',
            'description' => 'Para firmas con varios abogados',
            'price_monthly' => 69900,
            'price_yearly' => 349000,
            'max_cases' => 60,
            'max_users' => 10,
            'max_storage_mb' => 5120,
            'has_portal' => true,
            'has_notifications' => true,
            'sort_order' => 3,
        ]);

        // Firma demo
        $firm = Firm::create([
            'name' => 'Rodríguez & López Abogados',
            'nit' => '901234567-1',
            'legal_name' => 'Rodríguez & López Abogados S.A.S.',
            'email' => 'contacto@rodriguezlopez.co',
            'phone' => '6017654321',
            'address' => 'Calle 72 #10-07 Oficina 301',
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
            'description' => 'Firma especializada en derecho civil, laboral y de familia',
            'onboarding_completed' => true,
        ]);

        Subscription::create([
            'firm_id' => $firm->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(30),
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin LegalWeb',
            'email' => 'admin@legalweb.co',
            'firm_id' => $firm->id,
            'role' => 'admin',
        ]);

        $lawyer = User::factory()->create([
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos@legalweb.co',
            'firm_id' => $firm->id,
            'role' => 'abogado',
        ]);

        $lawyer2 = User::factory()->create([
            'name' => 'María López',
            'email' => 'maria@legalweb.co',
            'firm_id' => $firm->id,
            'role' => 'abogado',
        ]);

        $caseTypes = collect([
            ['name' => 'Civil', 'description' => 'Procesos civiles declarativos y ejecutivos', 'color' => '#3B82F6'],
            ['name' => 'Laboral', 'description' => 'Procesos laborales y de seguridad social', 'color' => '#10B981'],
            ['name' => 'Penal', 'description' => 'Procesos penales - Sistema Penal Acusatorio (Ley 906/2004)', 'color' => '#EF4444'],
            ['name' => 'Administrativo', 'description' => 'Procesos contencioso administrativos (CPACA)', 'color' => '#F59E0B'],
            ['name' => 'Comercial', 'description' => 'Procesos comerciales y societarios', 'color' => '#8B5CF6'],
            ['name' => 'Familia', 'description' => 'Procesos de familia, custodia, alimentos y divorcio', 'color' => '#EC4899'],
        ])->map(fn (array $data) => CaseType::create($data));

        $clients = Client::factory(15)->create(['user_id' => $lawyer->id, 'firm_id' => $firm->id, 'is_demo' => true]);

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

        // --- CIVIL: Proceso Verbal Sumario (CGP Art. 390-392) ---
        $this->createFlow($civilType, 'Proceso Verbal Sumario', 'Proceso verbal sumario de minima cuantia (CGP Art. 390-392)', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda verbal o escrita ante Juez Municipal de Pequeñas Causas'],
            ['name' => 'Admisión de demanda', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto admisorio con citación a audiencia'],
            ['name' => 'Notificación al demandado', 'order' => 3, 'days_limit' => 15, 'description' => 'Notificación personal o por aviso'],
            ['name' => 'Audiencia única', 'order' => 4, 'days_limit' => 30, 'description' => 'Contestación, conciliación, pruebas, alegatos y sentencia en audiencia única (Art. 392)'],
            ['name' => 'Sentencia', 'order' => 5, 'days_limit' => null, 'description' => 'Fallo en audiencia. Única instancia en mínima cuantía'],
        ]);

        // --- CIVIL: Proceso Monitorio (CGP Art. 419-421) ---
        $this->createFlow($civilType, 'Proceso Monitorio', 'Cobro de deudas de minima cuantia sin titulo ejecutivo (CGP Art. 419-421)', [
            ['name' => 'Presentación de demanda monitoria', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda con prueba siquiera sumaria de la obligación'],
            ['name' => 'Requerimiento de pago', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto que ordena al deudor pagar en 10 días (Art. 421)'],
            ['name' => 'Notificación del requerimiento', 'order' => 3, 'days_limit' => 15, 'description' => 'Notificación personal al deudor'],
            ['name' => 'Actitud del deudor', 'order' => 4, 'days_limit' => 10, 'description' => 'El deudor paga, se opone o guarda silencio'],
            ['name' => 'Sentencia o trámite verbal sumario', 'order' => 5, 'days_limit' => 20, 'description' => 'Si no paga ni se opone: sentencia condenatoria. Si se opone: trámite verbal sumario'],
        ]);

        // --- CIVIL: Proceso de Restitución de Inmueble (CGP Art. 384) ---
        $this->createFlow($civilType, 'Restitución de Inmueble Arrendado', 'Proceso de restitución de inmueble arrendado (CGP Art. 384)', [
            ['name' => 'Requerimiento previo al arrendatario', 'order' => 1, 'days_limit' => null, 'description' => 'Requerimiento de entrega mediante correo certificado o notificación escrita'],
            ['name' => 'Presentación de demanda', 'order' => 2, 'days_limit' => null, 'description' => 'Demanda de restitución con contrato y prueba del requerimiento'],
            ['name' => 'Admisión de demanda', 'order' => 3, 'days_limit' => 10, 'description' => 'Auto admisorio con medidas cautelares'],
            ['name' => 'Notificación al arrendatario', 'order' => 4, 'days_limit' => 20, 'description' => 'Notificación personal o por aviso'],
            ['name' => 'Contestación de demanda', 'order' => 5, 'days_limit' => 10, 'description' => 'Contestación y consignación de cánones adeudados'],
            ['name' => 'Audiencia', 'order' => 6, 'days_limit' => 30, 'description' => 'Audiencia de conciliación, pruebas y fallo'],
            ['name' => 'Sentencia y restitución', 'order' => 7, 'days_limit' => 15, 'description' => 'Orden de restitución y diligencia de entrega'],
        ]);

        // --- LABORAL: Proceso Ejecutivo Laboral (CPT Art. 100-111) ---
        $this->createFlow($laboralType, 'Proceso Ejecutivo Laboral', 'Cobro ejecutivo de obligaciones laborales (CPT Art. 100-111)', [
            ['name' => 'Presentación de demanda ejecutiva', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda con título ejecutivo laboral (sentencia, acta de conciliación, liquidación)'],
            ['name' => 'Mandamiento de pago', 'order' => 2, 'days_limit' => 5, 'description' => 'Auto que ordena el pago de obligaciones laborales'],
            ['name' => 'Notificación del mandamiento', 'order' => 3, 'days_limit' => 20, 'description' => 'Notificación personal al ejecutado'],
            ['name' => 'Excepciones', 'order' => 4, 'days_limit' => 5, 'description' => 'Término para proponer excepciones de mérito'],
            ['name' => 'Sentencia o auto de seguir adelante', 'order' => 5, 'days_limit' => 20, 'description' => 'Resolución de excepciones o auto de seguir adelante la ejecución'],
            ['name' => 'Liquidación y remate', 'order' => 6, 'days_limit' => 30, 'is_required' => false, 'description' => 'Liquidación del crédito y eventual remate de bienes'],
        ]);

        // --- LABORAL: Proceso Especial de Fuero Sindical (CPT Art. 113-118) ---
        $this->createFlow($laboralType, 'Fuero Sindical - Levantamiento', 'Solicitud de levantamiento de fuero sindical (CPT Art. 113-118)', [
            ['name' => 'Solicitud del empleador', 'order' => 1, 'days_limit' => null, 'description' => 'Solicitud de autorización para despedir trabajador aforado'],
            ['name' => 'Admisión', 'order' => 2, 'days_limit' => 5, 'description' => 'Auto admisorio con traslado al trabajador'],
            ['name' => 'Notificación al trabajador', 'order' => 3, 'days_limit' => 10, 'description' => 'Notificación personal al trabajador aforado'],
            ['name' => 'Contestación', 'order' => 4, 'days_limit' => 5, 'description' => 'Contestación del trabajador aforado'],
            ['name' => 'Audiencia de trámite', 'order' => 5, 'days_limit' => 15, 'description' => 'Audiencia de pruebas y alegatos'],
            ['name' => 'Sentencia', 'order' => 6, 'days_limit' => 10, 'description' => 'Autorización o negación del levantamiento del fuero'],
        ]);

        // --- PENAL: Preacuerdo / Negociación (Ley 906/2004 Art. 348-354) ---
        $this->createFlow($penalType, 'Preacuerdo y Negociación', 'Terminación anticipada por preacuerdo (Ley 906/2004 Art. 348-354)', [
            ['name' => 'Formulación de imputación', 'order' => 1, 'days_limit' => null, 'description' => 'Audiencia ante Juez de Control de Garantías'],
            ['name' => 'Negociación entre Fiscalía y defensa', 'order' => 2, 'days_limit' => 30, 'description' => 'Conversaciones para preacuerdo sobre cargos o pena'],
            ['name' => 'Aceptación de cargos', 'order' => 3, 'days_limit' => null, 'description' => 'El imputado acepta cargos con rebaja de pena acordada'],
            ['name' => 'Verificación judicial', 'order' => 4, 'days_limit' => 10, 'description' => 'Juez verifica voluntariedad y legalidad del preacuerdo (Art. 351)'],
            ['name' => 'Sentencia anticipada', 'order' => 5, 'days_limit' => 15, 'description' => 'Sentencia con la pena acordada en el preacuerdo'],
        ]);

        // --- PENAL: Incidente de Reparación Integral (Ley 906/2004 Art. 102-108) ---
        $this->createFlow($penalType, 'Incidente de Reparación Integral', 'Reparación de víctimas en proceso penal (Ley 906/2004 Art. 102-108)', [
            ['name' => 'Solicitud del incidente', 'order' => 1, 'days_limit' => 30, 'description' => 'La víctima solicita el incidente dentro de los 30 días siguientes a la sentencia condenatoria'],
            ['name' => 'Audiencia de reparación', 'order' => 2, 'days_limit' => 8, 'description' => 'Citación a audiencia dentro de los 8 días siguientes'],
            ['name' => 'Conciliación', 'order' => 3, 'days_limit' => null, 'description' => 'Intento de conciliación entre víctima y condenado'],
            ['name' => 'Pruebas de reparación', 'order' => 4, 'days_limit' => 15, 'is_required' => false, 'description' => 'Práctica de pruebas si no hubo conciliación'],
            ['name' => 'Sentencia de reparación', 'order' => 5, 'days_limit' => 10, 'description' => 'Decisión sobre monto y forma de reparación'],
        ]);

        // --- ADMINISTRATIVO: Acción de Tutela (Decreto 2591/1991) ---
        $this->createFlow($adminType, 'Acción de Tutela', 'Protección de derechos fundamentales (Art. 86 CP, Decreto 2591/1991)', [
            ['name' => 'Presentación de tutela', 'order' => 1, 'days_limit' => null, 'description' => 'Solicitud de amparo de derechos fundamentales ante cualquier juez'],
            ['name' => 'Admisión y auto', 'order' => 2, 'days_limit' => 1, 'description' => 'Admisión inmediata. No requiere apoderado ni formalidades'],
            ['name' => 'Traslado al accionado', 'order' => 3, 'days_limit' => 3, 'description' => 'Requerimiento para que rinda informe en máximo 3 días'],
            ['name' => 'Práctica de pruebas', 'order' => 4, 'days_limit' => 3, 'is_required' => false, 'description' => 'Pruebas de oficio si el juez las considera necesarias'],
            ['name' => 'Fallo de primera instancia', 'order' => 5, 'days_limit' => 10, 'description' => 'Sentencia en máximo 10 días desde la presentación'],
            ['name' => 'Impugnación', 'order' => 6, 'days_limit' => 3, 'is_required' => false, 'description' => 'Impugnación dentro de los 3 días siguientes a la notificación'],
            ['name' => 'Fallo de segunda instancia', 'order' => 7, 'days_limit' => 20, 'is_required' => false, 'description' => 'Decisión del superior jerárquico en 20 días'],
            ['name' => 'Revisión Corte Constitucional', 'order' => 8, 'days_limit' => null, 'is_required' => false, 'description' => 'Envío automático a la Corte para eventual selección y revisión'],
        ]);

        // --- ADMINISTRATIVO: Reparación Directa (CPACA Art. 140) ---
        $this->createFlow($adminType, 'Reparación Directa', 'Acción de reparación directa contra el Estado (CPACA Art. 140)', [
            ['name' => 'Solicitud de conciliación extrajudicial', 'order' => 1, 'days_limit' => 30, 'description' => 'Requisito de procedibilidad ante Procuraduría'],
            ['name' => 'Presentación de demanda', 'order' => 2, 'days_limit' => null, 'description' => 'Demanda dentro del término de caducidad: 2 años desde el hecho'],
            ['name' => 'Admisión de demanda', 'order' => 3, 'days_limit' => 15, 'description' => 'Auto admisorio con notificación a la entidad'],
            ['name' => 'Contestación', 'order' => 4, 'days_limit' => 30, 'description' => 'Contestación por la entidad demandada'],
            ['name' => 'Audiencia inicial', 'order' => 5, 'days_limit' => 30, 'description' => 'Saneamiento, fijación del litigio, conciliación y decreto de pruebas'],
            ['name' => 'Período probatorio', 'order' => 6, 'days_limit' => 40, 'description' => 'Práctica de pruebas decretadas'],
            ['name' => 'Alegatos', 'order' => 7, 'days_limit' => 10, 'description' => 'Traslado para alegatos de conclusión'],
            ['name' => 'Sentencia', 'order' => 8, 'days_limit' => 30, 'description' => 'Sentencia sobre responsabilidad del Estado'],
        ]);

        // --- COMERCIAL: Proceso de Liquidación Judicial (Ley 1116/2006) ---
        $this->createFlow($comercialType, 'Liquidación Judicial', 'Proceso de liquidación judicial de sociedad (Ley 1116/2006 Art. 47-68)', [
            ['name' => 'Apertura de liquidación', 'order' => 1, 'days_limit' => null, 'description' => 'Auto de apertura por la Superintendencia de Sociedades'],
            ['name' => 'Emplazamiento a acreedores', 'order' => 2, 'days_limit' => 10, 'description' => 'Aviso para que acreedores presenten sus créditos'],
            ['name' => 'Presentación de créditos', 'order' => 3, 'days_limit' => 20, 'description' => 'Los acreedores presentan sus acreencias'],
            ['name' => 'Calificación y graduación', 'order' => 4, 'days_limit' => 30, 'description' => 'Calificación y graduación de créditos por el liquidador'],
            ['name' => 'Inventario de bienes', 'order' => 5, 'days_limit' => 20, 'description' => 'Inventario valorado de activos de la sociedad'],
            ['name' => 'Proyecto de adjudicación', 'order' => 6, 'days_limit' => 30, 'description' => 'Proyecto de distribución de activos entre acreedores'],
            ['name' => 'Objeciones', 'order' => 7, 'days_limit' => 10, 'is_required' => false, 'description' => 'Objeciones al proyecto de adjudicación'],
            ['name' => 'Providencia de adjudicación', 'order' => 8, 'days_limit' => 15, 'description' => 'Aprobación del proyecto y adjudicación de bienes'],
            ['name' => 'Registro y cancelación', 'order' => 9, 'days_limit' => 30, 'description' => 'Registro de la providencia y cancelación de matrícula mercantil'],
        ]);

        // --- COMERCIAL: Proceso de Competencia Desleal (Ley 256/1996) ---
        $this->createFlow($comercialType, 'Competencia Desleal', 'Acción de competencia desleal ante Superintendencia de Industria y Comercio (Ley 256/1996)', [
            ['name' => 'Presentación de demanda', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda ante la SIC o juez civil del circuito'],
            ['name' => 'Medidas cautelares', 'order' => 2, 'days_limit' => 5, 'is_required' => false, 'description' => 'Solicitud de cese provisional de la conducta desleal'],
            ['name' => 'Admisión y traslado', 'order' => 3, 'days_limit' => 10, 'description' => 'Auto admisorio con traslado al demandado'],
            ['name' => 'Contestación', 'order' => 4, 'days_limit' => 10, 'description' => 'Contestación y proposición de excepciones'],
            ['name' => 'Audiencia de conciliación y pruebas', 'order' => 5, 'days_limit' => 30, 'description' => 'Intento de conciliación y práctica de pruebas'],
            ['name' => 'Alegatos', 'order' => 6, 'days_limit' => 5, 'description' => 'Alegatos de conclusión por las partes'],
            ['name' => 'Decisión', 'order' => 7, 'days_limit' => 30, 'description' => 'Resolución que declara o no la competencia desleal e indemnización'],
        ]);

        // --- FAMILIA: Proceso de Alimentos (Ley 1098/2006 y CGP) ---
        $this->createFlow($familiaType, 'Fijación de Cuota Alimentaria', 'Proceso de fijación de alimentos para menores (Ley 1098/2006, CGP)', [
            ['name' => 'Conciliación extrajudicial', 'order' => 1, 'days_limit' => null, 'is_required' => false, 'description' => 'Intento de conciliación ante ICBF, comisaría de familia o centro de conciliación'],
            ['name' => 'Presentación de demanda', 'order' => 2, 'days_limit' => null, 'description' => 'Demanda de alimentos con prueba de necesidad y capacidad'],
            ['name' => 'Admisión y fijación de alimentos provisionales', 'order' => 3, 'days_limit' => 10, 'description' => 'Auto admisorio con fijación de cuota provisional (Ley 1098/2006 Art. 129)'],
            ['name' => 'Notificación al demandado', 'order' => 4, 'days_limit' => 20, 'description' => 'Notificación personal al obligado alimentario'],
            ['name' => 'Contestación', 'order' => 5, 'days_limit' => 10, 'description' => 'Contestación de la demanda'],
            ['name' => 'Audiencia de conciliación y fallo', 'order' => 6, 'days_limit' => 30, 'description' => 'Audiencia de conciliación, pruebas y sentencia'],
            ['name' => 'Sentencia', 'order' => 7, 'days_limit' => null, 'description' => 'Fijación definitiva de cuota alimentaria'],
        ]);

        // --- FAMILIA: Proceso de Custodia y Cuidado Personal ---
        $this->createFlow($familiaType, 'Custodia y Cuidado Personal', 'Proceso de custodia y regulación de visitas (CGP, Ley 1098/2006)', [
            ['name' => 'Conciliación extrajudicial', 'order' => 1, 'days_limit' => null, 'is_required' => false, 'description' => 'Intento de conciliación ante ICBF o comisaría de familia'],
            ['name' => 'Presentación de demanda', 'order' => 2, 'days_limit' => null, 'description' => 'Demanda de custodia con prueba del interés superior del menor'],
            ['name' => 'Admisión y medidas provisionales', 'order' => 3, 'days_limit' => 10, 'description' => 'Auto admisorio con custodia provisional si es urgente'],
            ['name' => 'Informe del ICBF/Defensoría', 'order' => 4, 'days_limit' => 30, 'description' => 'Informe del equipo interdisciplinario sobre entorno del menor'],
            ['name' => 'Audiencia', 'order' => 5, 'days_limit' => 30, 'description' => 'Audiencia de pruebas y escucha del menor (si tiene edad)'],
            ['name' => 'Sentencia', 'order' => 6, 'days_limit' => 20, 'description' => 'Sentencia sobre custodia, régimen de visitas y alimentos'],
        ]);

        // --- FAMILIA: Proceso de Sucesión (CGP Art. 487-531) ---
        $this->createFlow($familiaType, 'Proceso de Sucesión', 'Sucesión por causa de muerte (CGP Art. 487-531)', [
            ['name' => 'Solicitud de apertura', 'order' => 1, 'days_limit' => null, 'description' => 'Demanda de apertura de sucesión con registro de defunción'],
            ['name' => 'Auto de apertura', 'order' => 2, 'days_limit' => 10, 'description' => 'Auto que abre la sucesión y nombra curador de bienes'],
            ['name' => 'Emplazamiento a herederos', 'order' => 3, 'days_limit' => 20, 'description' => 'Edicto emplazando a herederos conocidos y desconocidos'],
            ['name' => 'Reconocimiento de herederos', 'order' => 4, 'days_limit' => 30, 'description' => 'Auto que reconoce calidad de herederos'],
            ['name' => 'Inventario y avalúo', 'order' => 5, 'days_limit' => 20, 'description' => 'Inventario de bienes y avalúo de la masa sucesoral'],
            ['name' => 'Objeciones al inventario', 'order' => 6, 'days_limit' => 10, 'is_required' => false, 'description' => 'Objeciones al inventario por herederos o acreedores'],
            ['name' => 'Trabajo de partición', 'order' => 7, 'days_limit' => 40, 'description' => 'Elaboración del trabajo de partición por el partidor'],
            ['name' => 'Objeciones a la partición', 'order' => 8, 'days_limit' => 10, 'is_required' => false, 'description' => 'Objeciones al trabajo de partición'],
            ['name' => 'Sentencia aprobatoria', 'order' => 9, 'days_limit' => 20, 'description' => 'Auto que aprueba la partición'],
            ['name' => 'Registro de sentencia', 'order' => 10, 'days_limit' => 30, 'description' => 'Registro de la sentencia en oficina de instrumentos públicos y notaría'],
        ]);

        // Recoger todos los flujos primarios (uno por tipo) para crear casos demo
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
                'firm_id' => $firm->id,
                'is_demo' => true,
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

        // Recordatorios de ejemplo
        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $admin->id,
            'legal_case_id' => $allCases->first()?->id,
            'title' => 'Audiencia inicial - Juzgado 5 Civil',
            'description' => 'Preparar alegatos y revisar pruebas documentales antes de la audiencia.',
            'type' => 'audiencia',
            'priority' => 'alta',
            'due_date' => now()->addDays(5)->setHour(9)->setMinute(0),
            'remind_at' => now()->addDays(4)->setHour(8)->setMinute(0),
        ]);

        Reminder::create([
            'firm_id' => $firm->id,
            'user_id' => $admin->id,
            'legal_case_id' => $allCases->skip(1)->first()?->id,
            'title' => 'Vencimiento termino para contestar demanda',
            'description' => 'Revisar expediente y preparar contestacion con excepciones.',
            'type' => 'vencimiento',
            'priority' => 'urgente',
            'due_date' => now()->addDays(2)->setHour(17)->setMinute(0),
            'remind_at' => now()->addDay()->setHour(8)->setMinute(0),
        ]);
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
