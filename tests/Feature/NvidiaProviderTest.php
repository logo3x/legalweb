<?php

namespace Tests\Feature;

use App\Services\AIModelRegistry;
use App\Services\AIProviderClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NvidiaProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => null,
            'services.openrouter.api_key' => null,
            'services.nvidia.api_key' => 'nvapi-test',
            'services.nvidia.model' => 'nvidia/nemotron-3-ultra-550b-a55b',
            'services.nvidia.max_candidates' => 2,
        ]);

        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/v1/models')) {
                return Http::response(['data' => [
                    ['id' => 'nvidia/nemotron-3-ultra-550b-a55b'],
                    ['id' => 'nvidia/nemotron-3.5-embed-8b'],
                    ['id' => 'nvidia/nemotron-4-340b-reward'],
                    ['id' => 'google/gemma-4-31b-it'],
                    ['id' => 'stepfun-ai/step-5-preview'],
                ]]);
            }

            return match ($request['model']) {
                'nvidia/nemotron-3-ultra-550b-a55b' => Http::response(['choices' => [['message' => ['content' => 'El plazo es de cinco dias segun el articulo 90 del CGP.']]], 'usage' => ['total_tokens' => 80]]),
                'stepfun-ai/step-5-preview' => Http::response(['choices' => [['message' => ['content' => 'Respuesta valida del segundo modelo disponible.']]]]),
                default => Http::response(['status' => 404, 'detail' => "Function 'x': Not found for account 'y'"], 404),
            };
        });
    }

    public function test_client_calls_nvidia_with_bearer_token(): void
    {
        $result = app(AIProviderClient::class)->complete('nvidia:nvidia/nemotron-3-ultra-550b-a55b', 'sistema', 'pregunta', 200);

        $this->assertStringContainsString('articulo 90', $result['content']);
        $this->assertSame(80, $result['usage']['total_tokens']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://integrate.api.nvidia.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer nvapi-test'));
    }

    public function test_unavailable_models_report_the_nvidia_error(): void
    {
        $result = app(AIProviderClient::class)->complete('nvidia:google/gemma-4-31b-it', 'sistema', 'pregunta', 200);

        $this->assertNull($result['content']);
        $this->assertSame(404, $result['status']);
        $this->assertStringContainsString('Not found for account', $result['error']);
    }

    public function test_refresh_keeps_only_nvidia_chat_models_that_respond(): void
    {
        $verified = app(AIModelRegistry::class)->refresh();

        $this->assertSame(
            ['nvidia:nvidia/nemotron-3-ultra-550b-a55b', 'nvidia:stepfun-ai/step-5-preview'],
            array_column($verified, 'key'),
        );
        $this->assertSame('Nemotron 3 Ultra 550B A55B (NVIDIA)', $verified[0]['label']);

        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'embed'));
        Http::assertNotSent(fn (Request $request) => str_contains((string) $request->body(), 'reward'));
    }
}
