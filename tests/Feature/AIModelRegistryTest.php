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

                return Http::response($status === 200 ? ['candidates' => [['content' => ['parts' => [['text' => 'OK']]]]]] : [], $status);
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

        $this->assertSame(
            ['gemini:gemini-flash-latest', 'openrouter:acme/good-model:free'],
            array_column($verified, 'key'),
        );
        $this->assertSame('Good Model (OpenRouter)', app(AIModelRegistry::class)->options()['openrouter:acme/good-model:free']);

        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'stealth/secret-alpha'));
        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'acme/paid-model'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'gemini-3.8-flash-tts'));
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

    public function test_summary_does_not_send_client_contact_data_but_draft_does(): void
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

        Http::assertSent(fn (Request $request) => str_contains((string) $request->body(), $client->email));
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
