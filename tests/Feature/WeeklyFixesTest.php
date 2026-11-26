<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\AIModelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Arreglos de la semana del analisis de factibilidad (26 de noviembre de 2026).
 */
class WeeklyFixesTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-cron-de-prueba-2026-largo';

    public function test_client_portal_does_not_load_google_analytics(): void
    {
        $firm = Firm::factory()->create();
        $user = User::factory()->create(['firm_id' => $firm->id]);
        $client = Client::factory()->create(['firm_id' => $firm->id, 'user_id' => $user->id]);
        $case = LegalCase::factory()->create([
            'firm_id' => $firm->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'portal_token' => str_repeat('g', 64),
            'portal_enabled' => true,
        ]);

        $this->get(route('portal.show', $case->portal_token))
            ->assertOk()
            ->assertDontSee('googletagmanager', false)
            ->assertDontSee("gtag('config'", false);
    }

    public function test_landing_no_longer_makes_unbacked_promises(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Sin errores de conteo')
            ->assertDontSee('Secreto profesional garantizado')
            ->assertDontSee('te recuerda dos días antes')
            ->assertDontSee('vigila la Rama Judicial y SAMAI cada noche', false);
    }

    public function test_deploy_task_keeps_the_verified_ai_models(): void
    {
        config(['app.cron_token' => self::TOKEN]);
        Cache::forever(AIModelRegistry::CACHE_KEY, [['key' => 'openrouter:acme/good:free', 'label' => 'Good', 'verified_at' => now()->toDateTimeString()]]);

        $this->get('/cron/'.self::TOKEN.'/setup')->assertOk();

        $this->assertCount(1, Cache::get(AIModelRegistry::CACHE_KEY, []), 'Desplegar no debe borrar los modelos de IA verificados.');
    }

    public function test_setup_panel_is_disabled_unless_explicitly_enabled(): void
    {
        $this->assertFalse(config('app.setup_enabled'));
        $this->assertStringContainsString("config('app.setup_enabled')", (string) file_get_contents(public_path('setup.php')));
    }
}
