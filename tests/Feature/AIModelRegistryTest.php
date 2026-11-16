<?php

namespace Tests\Feature;

use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\AIModelRegistry;
use App\Services\AIService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIModelRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => 'gemini-test-key',
            'services.gemini.model' => 'gemini-flash-latest',
            'services.openrouter.api_key' => 'openrouter-test-key',
            'services.openrouter.model' => null,
            'services.openrouter.data_collection' => 'allow',
        ]);
    }

    /**
     * Caso en memoria con sus relaciones, sin tocar la base de datos.
     */
    private function makeCase(): LegalCase
    {
        $user = User::factory()->make()->setRelation('firm', Firm::factory()->make());

        return LegalCase::factory()->make(['case_type_id' => null, 'client_id' => null, 'user_id' => null])
            ->setRelation('client', Client::factory()->make(['user_id' => null]))
            ->setRelation('caseType', CaseType::factory()->make())
            ->setRelation('user', $user)
            ->setRelation('events', new Collection)
            ->setRelation('flowProgress', new Collection);
    }

    /**
     * @param  array<string, int>  $chatStatuses  modelo => estado HTTP de la respuesta de chat
     */
    private function fakeProviders(array $chatStatuses): void
    {
        Http::fake(function (Request $request) use ($chatStatuses) {
            $url = $request->url();

            if (str_contains($url, 'generativelanguage.googleapis.com') && str_contains($url, '/models?')) {
                return Http::response(['models' => [
                    ['name' => 'models/gemini-flash-latest', 'displayName' => 'Gemini Flash Latest', 'supportedGenerationMethods' => ['generateContent']],
                    ['name' => 'models/gemini-3.8-flash-tts', 'displayName' => 'TTS', 'supportedGenerationMethods' => ['generateContent']],
                    ['name' => 'models/text-embedding-004', 'displayName' => 'Embedding', 'supportedGenerationMethods' => ['embedContent']],
                ]]);
            }

            if (str_contains($url, ':generateContent')) {
                preg_match('#/models/([^:]+):generateContent#', $url, $matches);
                $status = $chatStatuses['gemini:'.$matches[1]] ?? 404;

                return Http::response($status === 200 ? ['candidates' => [['content' => ['parts' => [['text' => 'La tutela protege derechos fundamentales de forma inmediata.']]]]]] : [], $status);
            }

            if (str_ends_with($url, '/models')) {
                return Http::response(['data' => [
                    ['id' => 'acme/good-model:free', 'name' => 'Good Model', 'context_length' => 128000, 'architecture' => ['output_modalities' => ['text']]],
                    ['id' => 'acme/other-model:free', 'name' => 'Other Model', 'context_length' => 128000, 'architecture' => ['output_modalities' => ['text']]],
                    ['id' => 'stealth/secret-alpha', 'name' => 'Stealth', 'context_length' => 128000, 'pricing' => ['prompt' => '0', 'completion' => '0']],
                    ['id' => 'acme/paid-model', 'name' => 'Paid', 'context_length' => 128000],
                    ['id' => 'acme/tiny:free', 'name' => 'Tiny', 'context_length' => 4096],
                    ['id' => 'acme/content-safety:free', 'name' => 'Safety', 'context_length' => 128000],
                ]]);
            }

            if (str_ends_with($url, '/chat/completions')) {
                $status = $chatStatuses['openrouter:'.$request['model']] ?? 404;

                return Http::response($status === 200 ? ['choices' => [['message' => ['content' => 'Respuesta de '.$request['model']]]]] : [], $status);
            }

            return Http::response([], 500);
        });
    }

    public function test_refresh_only_keeps_free_text_models_that_respond(): void
    {
        $this->fakeProviders([
            'gemini:gemini-flash-latest' => 200,
            'openrouter:acme/good-model:free' => 200,
            'openrouter:acme/other-model:free' => 404,
        ]);

        $verified = app(AIModelRegistry::class)->refresh();

        // OpenRouter va primero (con deny no entrena con los datos); los proveedores se alternan.
        $this->assertSame(
            ['openrouter:acme/good-model:free', 'gemini:gemini-flash-latest'],
            array_column($verified, 'key'),
        );
        $this->assertSame('Good Model (OpenRouter)', app(AIModelRegistry::class)->options()['openrouter:acme/good-model:free']);

        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'stealth/secret-alpha'));
        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'acme/paid-model'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'gemini-3.8-flash-tts'));
    }

    public function test_refresh_runs_in_short_steps_that_resume_where_they_stopped(): void
    {
        $this->fakeProviders([
            'gemini:gemini-flash-latest' => 200,
            'openrouter:acme/good-model:free' => 200,
            'openrouter:acme/other-model:free' => 404,
        ]);
        $registry = app(AIModelRegistry::class);

        // Con un tiempo minimo, cada tramo prueba un solo candidato y guarda el avance.
        $first = $registry->refreshStep(1);

        $this->assertFalse($first['done']);
        $this->assertSame(1, $first['probed']);
        $this->assertTrue($registry->refreshInProgress());
        $this->assertNull($registry->lastVerifiedAt());

        $steps = 1;
        do {
            $step = $registry->refreshStep(1);
            $steps++;
        } while (! $step['done'] && $steps < 10);

        $this->assertTrue($step['done']);
        $this->assertGreaterThan(2, $steps);
        $this->assertFalse($registry->refreshInProgress());
        $this->assertNotNull($registry->lastVerifiedAt());
        $this->assertSame(['openrouter:acme/good-model:free', 'gemini:gemini-flash-latest'], array_column($registry->verifiedModels(), 'key'));
    }

    public function test_verify_command_with_budget_reports_progress_instead_of_blocking(): void
    {
        $this->fakeProviders(['openrouter:acme/good-model:free' => 200, 'gemini:gemini-flash-latest' => 200]);

        $this->artisan('app:verify-ai-models', ['--budget' => 1])
            ->expectsOutputToContain('Busqueda en curso')
            ->assertSuccessful();
    }

    public function test_refresh_keeps_probing_until_enough_models_respond(): void
    {
        config(['services.gemini.api_key' => null, 'services.openrouter.max_candidates' => 1]);

        // El primero no tiene proveedor que cumpla la politica de datos (404), el segundo si.
        $this->fakeProviders([
            'openrouter:acme/good-model:free' => 404,
            'openrouter:acme/other-model:free' => 200,
        ]);

        $verified = app(AIModelRegistry::class)->refresh();

        $this->assertSame(['openrouter:acme/other-model:free'], array_column($verified, 'key'));
    }

    public function test_refresh_stops_probing_once_enough_models_respond(): void
    {
        config(['services.gemini.api_key' => null, 'services.openrouter.max_candidates' => 1]);
        $this->fakeProviders([
            'openrouter:acme/good-model:free' => 200,
            'openrouter:acme/other-model:free' => 200,
        ]);

        app(AIModelRegistry::class)->refresh();

        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'acme/other-model:free'));
    }

    public function test_gemini_goes_first_when_it_is_the_preferred_provider(): void
    {
        config(['services.ai.preferred_provider' => 'gemini']);
        $this->fakeProviders([
            'gemini:gemini-flash-latest' => 200,
            'openrouter:acme/good-model:free' => 200,
        ]);

        $verified = app(AIModelRegistry::class)->refresh();

        $this->assertSame('gemini:gemini-flash-latest', $verified[0]['key']);
        $this->assertSame('gemini:gemini-flash-latest', app(AIModelRegistry::class)->attemptOrder()[0]);
    }

    public function test_models_that_reason_without_answering_are_discarded(): void
    {
        config(['services.gemini.api_key' => null]);
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/good-model:free', 'label' => 'Good Model', 'verified_at' => '2026-09-30 06:00:00'],
        ]);

        // Responde 200 pero sin contenido: agoto los tokens "pensando" (finish_reason = length).
        Http::fake([
            '*/models' => Http::response(['data' => [['id' => 'acme/good-model:free', 'name' => 'Good Model', 'context_length' => 128000]]]),
            '*/chat/completions' => Http::response(['choices' => [['message' => ['content' => null], 'finish_reason' => 'length']]]),
        ]);

        $registry = app(AIModelRegistry::class);

        $this->assertSame([], $registry->refresh(), 'No pasa la verificacion');

        $registry->recordResult('openrouter:acme/good-model:free', false, 200);
        $this->assertSame([], $registry->verifiedModels(), 'Una llamada real sin respuesta lo retira de la lista');
    }

    public function test_invalid_api_key_does_not_empty_the_model_list(): void
    {
        $registry = app(AIModelRegistry::class);
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:a:free', 'label' => 'A', 'verified_at' => ''],
        ]);

        $registry->recordResult('openrouter:a:free', false, 401);
        $registry->recordResult('openrouter:a:free', false, 503);

        $this->assertCount(1, $registry->verifiedModels());
    }

    public function test_refresh_saves_progress_after_each_probe(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/other-model:free', 'label' => 'Other Model', 'verified_at' => '2026-09-30 06:00:00'],
        ]);

        // Simula que el hosting corta la ejecucion en la segunda prueba.
        $calls = 0;
        Http::fake(function (Request $request) use (&$calls) {
            if (str_contains($request->url(), '/models')) {
                return str_contains($request->url(), 'generativelanguage')
                    ? Http::response(['models' => [['name' => 'models/gemini-flash-latest', 'displayName' => 'Gemini Flash Latest', 'supportedGenerationMethods' => ['generateContent']]]])
                    : Http::response(['data' => [
                        ['id' => 'acme/good-model:free', 'name' => 'Good Model', 'context_length' => 128000],
                        ['id' => 'acme/other-model:free', 'name' => 'Other Model', 'context_length' => 128000],
                    ]]);
            }

            if (++$calls === 2) {
                throw new \Error('Maximum execution time exceeded');
            }

            return Http::response(['choices' => [['message' => ['content' => 'La tutela protege derechos fundamentales de forma inmediata.']]]]);
        });

        try {
            app(AIModelRegistry::class)->refresh();
        } catch (\Error) {
            // La ejecucion se corto a mitad de la verificacion.
        }

        $keys = array_column(app(AIModelRegistry::class)->verifiedModels(), 'key');

        $this->assertContains('openrouter:acme/good-model:free', $keys);
        $this->assertContains('openrouter:acme/other-model:free', $keys, 'Los modelos anteriores aun no probados se conservan');
    }

    public function test_refresh_keeps_previous_list_when_nothing_responds(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/good-model:free', 'label' => 'Good Model', 'verified_at' => '2026-09-30 06:00:00'],
        ]);
        $this->fakeProviders([]);

        $this->assertSame([], app(AIModelRegistry::class)->refresh());
        $this->assertCount(1, app(AIModelRegistry::class)->verifiedModels());
    }

    public function test_refresh_keeps_previously_verified_model_on_transient_failure(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/other-model:free', 'label' => 'Other Model', 'verified_at' => '2026-09-30 06:00:00'],
        ]);
        $this->fakeProviders([
            'openrouter:acme/good-model:free' => 200,
            'openrouter:acme/other-model:free' => 429,
        ]);

        $keys = array_column(app(AIModelRegistry::class)->refresh(), 'key');

        $this->assertContains('openrouter:acme/other-model:free', $keys);
        $this->assertContains('openrouter:acme/good-model:free', $keys);
    }

    public function test_failed_call_removes_model_only_when_it_no_longer_exists(): void
    {
        $registry = app(AIModelRegistry::class);
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:a:free', 'label' => 'A', 'verified_at' => ''],
            ['key' => 'openrouter:b:free', 'label' => 'B', 'verified_at' => ''],
        ]);

        $registry->recordResult('openrouter:a:free', false, 429);
        $registry->recordResult('openrouter:b:free', false, 404);

        $this->assertSame(['openrouter:a:free'], array_column($registry->verifiedModels(), 'key'));
    }

    public function test_service_uses_preferred_model_and_falls_back_when_it_fails(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/good-model:free', 'label' => 'Good Model (OpenRouter)', 'verified_at' => ''],
            ['key' => 'openrouter:acme/other-model:free', 'label' => 'Other Model (OpenRouter)', 'verified_at' => ''],
        ]);
        $this->fakeProviders(['openrouter:acme/good-model:free' => 200]);
        $case = $this->makeCase();

        $ai = app(AIService::class)->usingModel('openrouter:acme/other-model:free');
        $result = $ai->summarizeCase($case);

        $this->assertSame('Respuesta de acme/good-model:free', $result);
        $this->assertSame('openrouter:acme/good-model:free', $ai->getLastModelKey());
        $this->assertSame('Good Model (OpenRouter)', $ai->getLastProvider());
        $this->assertNotContains('openrouter:acme/other-model:free', array_column(app(AIModelRegistry::class)->verifiedModels(), 'key'));
    }

    public function test_preferred_model_outside_the_verified_list_is_ignored(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/good-model:free', 'label' => 'Good Model (OpenRouter)', 'verified_at' => ''],
        ]);
        $this->fakeProviders(['openrouter:acme/good-model:free' => 200, 'openrouter:anthropic/modelo-de-pago' => 200]);
        $case = $this->makeCase();

        // La clave llega del navegador: un modelo de pago no verificado nunca se usa ni se agrega a la lista.
        $ai = app(AIService::class)->usingModel('openrouter:anthropic/modelo-de-pago');
        $ai->summarizeCase($case);

        $this->assertSame('openrouter:acme/good-model:free', $ai->getLastModelKey());
        $this->assertSame(['openrouter:acme/good-model:free'], array_column(app(AIModelRegistry::class)->verifiedModels(), 'key'));
    }

    public function test_service_discovers_a_new_model_when_all_known_models_are_gone(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:retired/model:free', 'label' => 'Retired', 'verified_at' => ''],
        ]);
        $this->fakeProviders(['openrouter:acme/other-model:free' => 200]);
        $case = $this->makeCase();

        $ai = app(AIService::class);

        $this->assertSame('Respuesta de acme/other-model:free', $ai->suggestNextStep($case));
        $this->assertSame(
            ['openrouter:acme/other-model:free'],
            array_column(app(AIModelRegistry::class)->verifiedModels(), 'key'),
        );
    }

    public function test_service_returns_null_when_no_model_responds(): void
    {
        $this->fakeProviders([]);

        $this->assertNull(app(AIService::class)->summarizeCase($this->makeCase()));
    }

    public function test_client_contact_data_never_reaches_the_provider(): void
    {
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/good-model:free', 'label' => 'Good Model', 'verified_at' => ''],
        ]);
        $this->fakeProviders(['openrouter:acme/good-model:free' => 200]);
        $case = $this->makeCase();
        $client = $case->client;

        app(AIService::class)->summarizeCase($case);

        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), $client->email)
            || str_contains((string) $request->body(), $client->phone));

        app(AIService::class)->draftDocument($case, 'Memorial');

        // El borrador necesita los datos de contacto: viajan como marcadores y se restauran localmente.
        Http::assertSent(fn (Request $request) => str_contains((string) $request->body(), '[CLIENTE_CORREO]'));
        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), $client->email));
    }

    public function test_data_collection_deny_is_sent_to_openrouter_when_configured(): void
    {
        config(['services.openrouter.data_collection' => 'deny']);
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => 'openrouter:acme/good-model:free', 'label' => 'Good Model', 'verified_at' => ''],
        ]);
        $this->fakeProviders(['openrouter:acme/good-model:free' => 200]);

        app(AIService::class)->summarizeCase($this->makeCase());

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/chat/completions')
            && $request['provider'] === ['data_collection' => 'deny']);
    }

    public function test_verify_command_reports_available_models(): void
    {
        $this->fakeProviders(['openrouter:acme/good-model:free' => 200]);

        $this->artisan('app:verify-ai-models')
            ->expectsOutputToContain('Good Model (OpenRouter)')
            ->assertSuccessful();
    }

    public function test_verify_command_fails_when_no_model_responds(): void
    {
        $this->fakeProviders([]);

        $this->artisan('app:verify-ai-models')->assertFailed();
    }
}
