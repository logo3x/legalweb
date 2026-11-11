<?php

namespace Tests\Feature;

use App\Jobs\SendWebhookDelivery;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\N8nWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class N8nIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-n8n-de-prueba-suficientemente-largo';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.n8n.webhook_url' => 'https://n8n.example.com/webhook/legalweb',
            'services.n8n.webhook_secret' => 'secreto',
            'services.n8n.events' => '*',
            'services.n8n.api_token' => self::TOKEN,
        ]);
    }

    public function test_nothing_is_recorded_when_n8n_is_not_configured(): void
    {
        config(['services.n8n.webhook_url' => null]);
        Queue::fake();

        $this->assertNull(app(N8nWebhook::class)->dispatch('case.created', ['x' => 1]));
        $this->assertSame(0, WebhookDelivery::count());
        Queue::assertNothingPushed();
    }

    public function test_only_enabled_events_are_dispatched(): void
    {
        config(['services.n8n.events' => 'firm.registered, subscription.activated']);
        Queue::fake();

        $this->assertNotNull(app(N8nWebhook::class)->dispatch('firm.registered', []));
        $this->assertNull(app(N8nWebhook::class)->dispatch('case.created', []));
        Queue::assertPushed(SendWebhookDelivery::class, 1);
    }

    public function test_creating_a_real_case_dispatches_event_but_demo_cases_do_not(): void
    {
        Queue::fake();
        $firm = Firm::factory()->create();

        LegalCase::factory()->create(['firm_id' => $firm->id, 'is_demo' => true]);
        $this->assertSame(0, WebhookDelivery::where('event', 'case.created')->count());

        $case = LegalCase::factory()->create(['firm_id' => $firm->id, 'is_demo' => false, 'status' => 'abierto']);
        $delivery = WebhookDelivery::where('event', 'case.created')->sole();
        $this->assertSame($case->case_number, $delivery->payload['data']['case']['case_number']);
        $this->assertSame($firm->id, $delivery->firm_id);

        $case->update(['status' => 'cerrado']);
        $statusChange = WebhookDelivery::where('event', 'case.status_changed')->sole();
        $this->assertSame('abierto', $statusChange->payload['data']['previous_status']);
    }

    public function test_delivery_is_signed_and_marked_as_sent(): void
    {
        Queue::fake();
        Http::fake(['n8n.example.com/*' => Http::response(['ok' => true], 200)]);

        $delivery = app(N8nWebhook::class)->dispatch('test.ping', ['hola' => 'mundo']);
        (new SendWebhookDelivery($delivery->id))->handle();

        Http::assertSent(function (Request $request) use ($delivery): bool {
            return $request->url() === 'https://n8n.example.com/webhook/legalweb'
                && $request->header('X-LegalWeb-Event')[0] === 'test.ping'
                && $request->header('X-LegalWeb-Delivery')[0] === $delivery->uuid
                && $request->header('X-LegalWeb-Signature')[0] === N8nWebhook::sign($request->body(), 'secreto')
                && $request['data']['hola'] === 'mundo';
        });

        $delivery->refresh();
        $this->assertSame(WebhookDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->sent_at);
    }

    public function test_server_errors_are_marked_failed_and_rethrown_for_retry(): void
    {
        Queue::fake();
        Http::fake(['n8n.example.com/*' => Http::response('caido', 503)]);

        $delivery = app(N8nWebhook::class)->dispatch('test.ping', []);

        try {
            (new SendWebhookDelivery($delivery->id))->handle();
            $this->fail('Se esperaba una excepcion para reintentar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('503', $e->getMessage());
        }

        $delivery->refresh();
        $this->assertSame(WebhookDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame(503, $delivery->response_code);
    }

    public function test_api_rejects_missing_or_wrong_token(): void
    {
        $this->getJson('/n8n/estado')->assertUnauthorized();
        $this->getJson('/n8n/estado', ['Authorization' => 'Bearer otro'])->assertUnauthorized();

        config(['services.n8n.api_token' => 'corto']);
        $this->getJson('/n8n/estado', ['Authorization' => 'Bearer corto'])->assertUnauthorized();
    }

    public function test_api_lists_firms_with_filters(): void
    {
        Queue::fake();

        $inactive = Firm::factory()->create(['name' => 'Firma Dormida']);
        User::factory()->create(['firm_id' => $inactive->id, 'role' => 'admin', 'last_login_at' => now()->subDays(20)]);
        LegalCase::factory()->create(['firm_id' => $inactive->id, 'is_demo' => true]);

        $active = Firm::factory()->create(['name' => 'Firma Activa']);
        User::factory()->create(['firm_id' => $active->id, 'role' => 'admin', 'last_login_at' => now()->subDay()]);
        LegalCase::factory()->create(['firm_id' => $active->id, 'is_demo' => false]);

        $optedOut = Firm::factory()->create(['name' => 'Firma Sin Correos']);
        $optedOutAdmin = User::factory()->create(['firm_id' => $optedOut->id, 'role' => 'admin', 'last_login_at' => now()->subDays(30)]);
        $optedOutAdmin->forceFill(['mass_email_opt_out_at' => now()])->save();

        $headers = ['Authorization' => 'Bearer '.self::TOKEN];

        $this->getJson('/n8n/estado', $headers)->assertOk()->assertJson(['status' => 'ok']);

        $this->getJson('/n8n/firmas?inactive_days=10', $headers)
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.name', 'Firma Dormida')
            ->assertJsonPath('data.0.real_cases', 0);

        $this->getJson('/n8n/firmas?without_cases=1&include_opted_out=1', $headers)
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->getJson('/n8n/firmas', $headers)
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonMissing(['name' => 'Firma Sin Correos']);
    }

    public function test_api_filters_trials_ending_soon(): void
    {
        $ending = Firm::factory()->create(['name' => 'Prueba por vencer']);
        Subscription::create(['firm_id' => $ending->id, 'plan_id' => $this->planId(), 'status' => 'active', 'starts_at' => now()->subMonths(3), 'trial_ends_at' => now()->addDays(3)]);

        $later = Firm::factory()->create(['name' => 'Prueba lejana']);
        Subscription::create(['firm_id' => $later->id, 'plan_id' => $this->planId(), 'status' => 'active', 'starts_at' => now(), 'trial_ends_at' => now()->addDays(60)]);

        $this->getJson('/n8n/firmas?trial_ending_days=5', ['Authorization' => 'Bearer '.self::TOKEN])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.name', 'Prueba por vencer');
    }

    public function test_superadmin_page_renders_deliveries(): void
    {
        Queue::fake();
        $firm = Firm::factory()->create();
        $superadmin = User::factory()->create(['firm_id' => $firm->id, 'role' => 'superadmin']);
        app(N8nWebhook::class)->dispatch('test.ping', [], $firm->id);

        $this->actingAs($superadmin)
            ->get('/admin/integracion-n8n')
            ->assertOk()
            ->assertSee('test.ping')
            ->assertSee('N8N_WEBHOOK_URL');

        $admin = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $this->actingAs($admin)->get('/admin/integracion-n8n')->assertForbidden();
    }

    public function test_cron_queue_releases_failed_job_for_retry_instead_of_failing_it(): void
    {
        config(['app.cron_token' => 'token-cron-de-prueba-suficientemente-largo']);
        Http::fake(['n8n.example.com/*' => Http::sequence()->push('caido', 503)->push(['ok' => true], 200)]);

        $delivery = WebhookDelivery::create([
            'uuid' => (string) Str::uuid(),
            'event' => 'test.ping',
            'payload' => ['event' => 'test.ping', 'data' => []],
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);
        Queue::connection('database')->push(new SendWebhookDelivery($delivery->id));

        $this->get('/cron/token-cron-de-prueba-suficientemente-largo/queue')->assertOk();

        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertSame(WebhookDelivery::STATUS_FAILED, $delivery->fresh()->status);

        // Simula que ya paso el tiempo de espera del reintento.
        DB::table('jobs')->update(['available_at' => now()->subMinute()->timestamp]);

        $this->get('/cron/token-cron-de-prueba-suficientemente-largo/queue')->assertOk();

        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(WebhookDelivery::STATUS_SENT, $delivery->fresh()->status);
        $this->assertSame(2, $delivery->fresh()->attempts);
    }

    private function planId(): int
    {
        return (int) Plan::query()->value('id') ?: Plan::create([
            'name' => 'Gratuito', 'slug' => 'gratuito-test', 'price' => 0,
        ])->id;
    }
}
