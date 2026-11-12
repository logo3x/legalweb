<?php

namespace Tests\Feature;

use App\Filament\Pages\UsoIa;
use App\Models\AiUsageLog;
use App\Models\Firm;
use App\Models\User;
use App\Services\AIModelHealth;
use App\Services\AIModelRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AIModelHealthTest extends TestCase
{
    use RefreshDatabase;

    private const GOOD = 'openrouter:acme/good-model:free';

    private const BAD = 'openrouter:acme/bad-model:free';

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        config([
            'services.gemini.api_key' => null,
            'services.openrouter.api_key' => 'openrouter-test-key',
            'services.openrouter.data_collection' => 'allow',
            'services.ai.health_interval_hours' => 6,
        ]);

        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => self::GOOD, 'label' => 'Good Model (OpenRouter)', 'verified_at' => now()->toDateTimeString()],
            ['key' => self::BAD, 'label' => 'Bad Model (OpenRouter)', 'verified_at' => now()->toDateTimeString()],
        ]);

        $this->superadmin = User::factory()->create(['role' => 'superadmin', 'firm_id' => null]);
    }

    /**
     * @param  array<string, int>  $statuses  modelo => estado HTTP
     */
    private function fakeChat(array $statuses): void
    {
        Http::fake(function (Request $request) use ($statuses) {
            if (str_ends_with($request->url(), '/chat/completions')) {
                $status = $statuses[$request['model']] ?? 404;

                return Http::response($status === 200 ? ['choices' => [['message' => ['content' => 'La tutela protege derechos fundamentales.']]]] : ['error' => ['message' => 'Modelo saturado']], $status);
            }

            return Http::response(['data' => []]);
        });
    }

    public function test_usage_page_renders_without_any_ai_calls(): void
    {
        $this->actingAs($this->superadmin)
            ->get('/admin/uso-ia')
            ->assertOk()
            ->assertSee('Estado de los modelos')
            ->assertSee('Good Model (OpenRouter)')
            ->assertSee('Sin comprobar');
    }

    public function test_usage_page_renders_with_usage_data(): void
    {
        $firm = Firm::factory()->create(['name' => 'Firma Con Uso']);
        AiUsageLog::create(['firm_id' => $firm->id, 'action' => 'resumen', 'provider' => 'OpenRouter', 'model' => 'acme/good-model:free', 'total_tokens' => 500, 'success' => true, 'created_at' => now()]);

        $this->actingAs($this->superadmin)->get('/admin/uso-ia')->assertOk()->assertSee('Firma Con Uso');
    }

    public function test_usage_page_is_only_for_superadmin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'firm_id' => Firm::factory()->create()->id]);

        $this->actingAs($admin)->get('/admin/uso-ia')->assertForbidden();
    }

    public function test_probe_marks_models_as_active_or_failing(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200, 'acme/bad-model:free' => 503]);
        $health = app(AIModelHealth::class);

        $this->assertTrue($health->probe(self::GOOD)['ok']);
        $failed = $health->probe(self::BAD);

        $this->assertFalse($failed['ok']);
        $this->assertSame(503, $failed['status']);
        $this->assertStringContainsString('Modelo saturado', $failed['error']);
        $this->assertSame(1, $health->healthyCount());

        // 503 es transitorio: el modelo sigue en la lista para reintentarlo.
        $this->assertArrayHasKey(self::BAD, app(AIModelRegistry::class)->options());
    }

    public function test_real_ai_calls_update_health_without_extra_requests(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200]);
        $this->actingAs($this->superadmin);

        app(AIModelHealth::class)->record(self::GOOD, true, 200, null, 800, 'uso');

        $row = collect(app(AIModelHealth::class)->report())->firstWhere('key', self::GOOD);
        $this->assertSame('activo', $row['state']);
        $this->assertSame('uso', $row['source']);
        Http::assertNothingSent();
    }

    public function test_scheduled_check_only_probes_models_without_recent_news(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200, 'acme/bad-model:free' => 200]);
        $health = app(AIModelHealth::class);
        $health->record(self::GOOD, true, 200, null, 500, 'uso');

        $probed = $health->runScheduledCheck(2);

        $this->assertSame([self::BAD], $probed);
        Http::assertSentCount(1);

        // Pasado el intervalo, se vuelve a probar.
        $this->travel(7)->hours();
        $this->assertCount(2, $health->runScheduledCheck(2));
    }

    public function test_failing_model_is_retried_after_one_hour(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200, 'acme/bad-model:free' => 503]);
        $health = app(AIModelHealth::class);
        $health->runScheduledCheck(2);

        $this->travel(61)->minutes();

        $this->assertSame([self::BAD], $health->runScheduledCheck(2));
    }

    public function test_total_outage_searches_new_models_and_alerts_superadmin_once(): void
    {
        $this->fakeChat([]);
        $health = app(AIModelHealth::class);

        $health->runScheduledCheck(2);
        $this->travel(2)->hours();
        $health->runScheduledCheck(2);

        $this->assertSame(0, $health->healthyCount());
        $this->assertSame(1, $this->superadmin->notifications()->count());
        $this->assertStringContainsString('La IA no esta respondiendo', $this->superadmin->notifications()->first()->data['title']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/models'));
    }

    public function test_cron_queue_task_runs_the_health_check_every_ten_minutes(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200, 'acme/bad-model:free' => 200]);
        $token = str_repeat('t', 32);
        config(['app.cron_token' => $token]);

        $this->get("/cron/{$token}/queue")->assertOk()->assertJsonFragment(['ai-health: 2 modelo(s) probados']);
        $this->get("/cron/{$token}/queue")->assertOk()->assertJsonMissing(['ai-health: 2 modelo(s) probados']);
    }

    public function test_superadmin_can_probe_a_model_from_the_page(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200]);
        $this->actingAs($this->superadmin);

        Livewire::test(UsoIa::class)->call('probeModel', self::GOOD)->assertNotified();
        Livewire::test(UsoIa::class)->call('probeModel', 'openrouter:no-esta-en-la-lista')->assertNotNotified();

        $this->assertTrue(app(AIModelHealth::class)->all()[self::GOOD]['ok']);
        Http::assertSentCount(1);
    }

    public function test_manual_probes_are_rate_limited(): void
    {
        $this->fakeChat(['acme/good-model:free' => 200]);
        $this->actingAs($this->superadmin);

        for ($i = 0; $i < 12; $i++) {
            Livewire::test(UsoIa::class)->call('probeModel', self::GOOD);
        }

        Http::assertSentCount(10);
    }
}
