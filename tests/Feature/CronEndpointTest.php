<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CronEndpointTest extends TestCase
{
    private const TOKEN = 'token-de-prueba-suficientemente-largo';

    public function test_cron_is_blocked_when_token_is_not_configured(): void
    {
        config(['app.cron_token' => null]);

        $this->get('/cron/cualquier-cosa/unknown')->assertForbidden();
        $this->get('/cron//unknown')->assertNotFound();
    }

    public function test_cron_rejects_old_default_token(): void
    {
        config(['app.cron_token' => self::TOKEN]);

        $this->get('/cron/legalweb-cron-2026/unknown')->assertForbidden();
    }

    public function test_cron_rejects_short_configured_token(): void
    {
        config(['app.cron_token' => 'corto']);

        $this->get('/cron/corto/unknown')->assertForbidden();
    }

    public function test_cron_accepts_configured_token(): void
    {
        config(['app.cron_token' => self::TOKEN]);

        $this->get('/cron/'.self::TOKEN.'/unknown')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'results' => []]);
    }

    public function test_cron_runs_ai_model_verification_task(): void
    {
        config([
            'app.cron_token' => self::TOKEN,
            'services.gemini.api_key' => null,
            'services.openrouter.api_key' => 'openrouter-test-key',
        ]);

        Http::fake([
            '*/models' => Http::response(['data' => [
                ['id' => 'acme/good-model:free', 'name' => 'Good Model', 'context_length' => 128000],
            ]]),
            '*/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'OK']]]]),
        ]);

        $this->get('/cron/'.self::TOKEN.'/verify-ai-models')
            ->assertOk()
            ->assertJson(['results' => ['verify-ai-models: 1 modelo(s) disponibles']]);
    }
}
