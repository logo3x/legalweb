<?php

namespace Tests\Feature;

use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\AIModelRegistry;
use App\Services\AIPseudonymizer;
use App\Services\AIService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIPrivacyTest extends TestCase
{
    private function makeCase(): LegalCase
    {
        $firm = Firm::factory()->make(['name' => 'Firma Ejemplo', 'nit' => '900123456-1']);
        $user = User::factory()->make(['name' => 'Laura Abogada Torres', 'email' => 'laura@firma.test'])->setRelation('firm', $firm);
        $client = Client::factory()->make([
            'first_name' => 'Juan Carlos',
            'last_name' => 'Perez Garcia',
            'document_type' => 'CC',
            'document_number' => '79123456',
            'phone' => '3001234567',
            'email' => 'juan.perez@correo.test',
            'address' => 'Calle 10 # 20-30',
            'city' => 'Bogota',
            'user_id' => null,
        ]);

        return LegalCase::factory()->make([
            'title' => 'Ejecutivo - PEREZ GARCIA JUAN CARLOS vs BANCO DEL EJEMPLO S.A.',
            'external_case_number' => '11001310300120250012300',
            'court' => 'Juzgado 1 Civil del Circuito de Bogota',
            'judge' => 'Maria Jueza',
            'opposing_party' => 'BANCO DEL EJEMPLO S.A.',
            'description' => "Importado desde Rama Judicial\nDepartamento: BOGOTA\nDespacho: Juzgado 1\n\nSujetos procesales:\n- Demandante: PEREZ GARCIA JUAN CARLOS\n- Demandado: BANCO DEL EJEMPLO S.A.\n- Apoderado: ROJAS LOPEZ ANDREA\nContacto adicional: otra.persona@mail.test o 310 555 1234, cedula 1.020.304.050",
            'case_type_id' => null,
            'client_id' => null,
            'user_id' => null,
        ])
            ->setRelation('client', $client)
            ->setRelation('caseType', CaseType::factory()->make(['name' => 'Ejecutivo']))
            ->setRelation('user', $user)
            ->setRelation('events', new Collection)
            ->setRelation('flowProgress', new Collection);
    }

    public function test_personal_data_is_masked_and_restored(): void
    {
        $case = $this->makeCase();
        $pseudonymizer = AIPseudonymizer::forCase($case);
        $original = "Cliente: Juan Carlos Perez Garcia (CC 79123456), tel 3001234567, juan.perez@correo.test.\n"
            ."Rama: PEREZ GARCIA JUAN CARLOS contra BANCO DEL EJEMPLO S.A., apoderado ROJAS LOPEZ ANDREA.\n"
            .'Abogada Laura Abogada Torres (laura@firma.test). Otro: otra.persona@mail.test, 310 555 1234, cedula 1.020.304.050. '
            .'Radicado 11001310300120250012300 en Departamento: BOGOTA.';

        $masked = $pseudonymizer->mask($original);

        foreach (['Juan Carlos', 'Perez Garcia', 'PEREZ GARCIA', '79123456', '3001234567', 'juan.perez@correo.test', 'BANCO DEL EJEMPLO', 'ROJAS LOPEZ', 'Laura Abogada', 'laura@firma.test', 'otra.persona@mail.test', '310 555 1234', '1.020.304.050'] as $secret) {
            $this->assertStringNotContainsStringIgnoringCase($secret, $masked);
        }

        // Los datos publicos del proceso se conservan.
        $this->assertStringContainsString('11001310300120250012300', $masked);
        $this->assertStringContainsString('Departamento: BOGOTA', $masked);
        $this->assertStringContainsString('[CLIENTE]', $masked);

        $restored = $pseudonymizer->restore($masked);
        $this->assertStringContainsString('Juan Carlos Perez Garcia', $restored);
        $this->assertStringContainsString('BANCO DEL EJEMPLO S.A.', $restored);
        $this->assertStringContainsString('otra.persona@mail.test', $restored);
        $this->assertStringContainsString('1.020.304.050', $restored);
    }

    public function test_ai_provider_never_receives_personal_data_and_draft_comes_back_complete(): void
    {
        config([
            'services.gemini.api_key' => null,
            'services.nvidia.api_key' => null,
            'services.openrouter.api_key' => 'or-test',
            'services.openrouter.data_collection' => 'deny',
        ]);
        Cache::forever(AIModelRegistry::CACHE_KEY, [['key' => 'openrouter:acme/model:free', 'label' => 'Acme', 'verified_at' => now()->toDateTimeString()]]);

        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Señor juez, [CLIENTE], identificado con [CLIENTE_DOCUMENTO], demanda a [PARTE_1]. Firma: [ABOGADO].']]]])]);

        $draft = app(AIService::class)->draftDocument($this->makeCase(), 'Memorial');

        Http::assertSent(function (Request $request): bool {
            $body = (string) $request->body();

            return str_contains($body, '[CLIENTE]')
                && ! str_contains($body, 'Juan Carlos')
                && ! str_contains($body, '79123456')
                && ! str_contains($body, 'juan.perez@correo.test')
                && ! str_contains($body, 'BANCO DEL EJEMPLO')
                && ! str_contains($body, 'Laura Abogada')
                && str_contains($body, 'PRIVACIDAD');
        });

        $this->assertStringContainsString('Juan Carlos Perez Garcia, identificado con 79123456', $draft);
        $this->assertStringContainsString('BANCO DEL EJEMPLO S.A.', $draft);
        $this->assertStringContainsString('Laura Abogada Torres', $draft);
    }

    public function test_models_that_do_not_train_are_tried_first(): void
    {
        config([
            'services.gemini.api_key' => 'g-test',
            'services.nvidia.api_key' => 'n-test',
            'services.openrouter.api_key' => 'or-test',
            'services.openrouter.data_collection' => 'deny',
        ]);
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'gemini:gemini-flash-latest', 'label' => 'Gemini', 'verified_at' => ''],
            ['key' => 'nvidia:nvidia/nemotron', 'label' => 'Nemotron', 'verified_at' => ''],
            ['key' => 'openrouter:acme/model:free', 'label' => 'Acme', 'verified_at' => ''],
        ]);

        $registry = app(AIModelRegistry::class);

        $this->assertSame('openrouter:acme/model:free', $registry->attemptOrder()[0]);
        $this->assertStringContainsString('no entrena', $registry->labeledOptions()['openrouter:acme/model:free']);
        $this->assertStringNotContainsString('no entrena', $registry->labeledOptions()['gemini:gemini-flash-latest']);

        // Con "allow", OpenRouter deja de contar como privado.
        config(['services.openrouter.data_collection' => 'allow']);
        $this->assertSame('gemini:gemini-flash-latest', $registry->attemptOrder()[0]);
    }
}
