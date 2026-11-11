<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\CaseEvent;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Notifications\NewProcessesFoundNotification;
use App\Notifications\TybaSyncNotification;
use App\Services\CourtAnalyticsService;
use App\Services\SamaiService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pruebas de seguridad de las funciones agregadas en noviembre de 2026:
 * analitica, portal, comparador, notificaciones, n8n y SAMAI.
 */
class NewFeaturesSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const XSS = '<script>alert("xss")</script>';

    private Firm $firm;

    private User $admin;

    private User $collaborator;

    private LegalCase $assignedCase;

    private LegalCase $otherCase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Http::fake();

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $this->collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);

        $this->assignedCase = $this->makeCase(['court' => 'Juzgado Asignado', 'status' => 'abierto']);
        $this->otherCase = $this->makeCase(['court' => 'Juzgado Reservado', 'status' => 'abierto']);

        CasePermission::create([
            'user_id' => $this->collaborator->id,
            'legal_case_id' => $this->assignedCase->id,
            'permissions' => ['case.view'],
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_collaborator_analytics_only_include_assigned_cases(): void
    {
        $this->actingAs($this->collaborator)
            ->get('/admin/analitica-despachos')
            ->assertOk()
            ->assertSee('Juzgado Asignado')
            ->assertDontSee('Juzgado Reservado');

        $this->actingAs($this->admin)
            ->get('/admin/analitica-despachos')
            ->assertOk()
            ->assertSee('Juzgado Reservado');

        $limited = app(CourtAnalyticsService::class)->forFirm($this->firm->id, []);
        $this->assertSame(0, $limited['totals']['cases']);
    }

    public function test_firm_reports_are_only_for_admins(): void
    {
        $this->actingAs($this->collaborator)->get('/admin/reports')->assertForbidden();

        // El reporte usa SQL de MySQL (DATEDIFF); en SQLite solo se valida el permiso.
        $this->actingAs($this->admin);
        $this->assertTrue(Reports::canAccess());
    }

    public function test_portal_document_actions_cannot_touch_documents_of_other_cases(): void
    {
        $portalCase = $this->makeCase(['portal_token' => str_repeat('p', 64), 'portal_enabled' => true]);
        $foreignDocument = Document::factory()->create(['legal_case_id' => $this->otherCase->id, 'responsible' => 'cliente', 'status' => 'pendiente']);
        $lawyerDocument = Document::factory()->create(['legal_case_id' => $portalCase->id, 'responsible' => 'abogado', 'status' => 'pendiente']);

        $this->post(route('portal.document.ready', ['token' => $portalCase->portal_token, 'document' => $foreignDocument->id]))->assertNotFound();
        $this->post(route('portal.document.link', ['token' => $portalCase->portal_token, 'document' => $foreignDocument->id]), ['external_url' => 'https://drive.google.com/x'])->assertNotFound();
        $this->post(route('portal.document.ready', ['token' => $portalCase->portal_token, 'document' => $lawyerDocument->id]))->assertNotFound();

        $this->assertSame('pendiente', $foreignDocument->fresh()->status);
        $this->assertNull($foreignDocument->fresh()->external_url);
    }

    public function test_portal_rejects_disabled_portal_and_unsafe_links(): void
    {
        $portalCase = $this->makeCase(['portal_token' => str_repeat('q', 64), 'portal_enabled' => true]);
        $document = Document::factory()->create(['legal_case_id' => $portalCase->id, 'responsible' => 'cliente', 'status' => 'pendiente']);
        $url = route('portal.document.link', ['token' => $portalCase->portal_token, 'document' => $document->id]);

        foreach (['javascript:alert(1)', 'data:text/html;base64,PHNjcmlwdD4=', 'ftp://example.com/archivo'] as $unsafe) {
            $this->post($url, ['external_url' => $unsafe])->assertSessionHasErrors('external_url');
        }
        $this->assertNull($document->fresh()->external_url);

        $portalCase->update(['portal_enabled' => false]);
        $this->post($url, ['external_url' => 'https://drive.google.com/x'])->assertNotFound();
        $this->post(route('portal.document.ready', ['token' => $portalCase->portal_token, 'document' => $document->id]))->assertNotFound();
    }

    public function test_sync_changes_modal_escapes_data_from_external_sources(): void
    {
        $html = view('filament.modals.sync-changes', ['cambios' => [
            'actuaciones' => [['fecha' => '01/11/2026', 'tipo' => self::XSS, 'anotacion' => self::XSS]],
            'campos' => [['campo' => 'Despacho', 'antes' => self::XSS, 'despues' => self::XSS]],
        ]])->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_judicial_update_email_escapes_field_changes(): void
    {
        $case = $this->makeCase(['court' => self::XSS]);
        $mail = (new TybaSyncNotification($case, 0, ['campos' => [['campo' => 'Despacho', 'antes' => 'A', 'despues' => self::XSS]]]))->toMail($this->admin);

        $html = (string) $mail->render();

        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_new_processes_email_escapes_court_names(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        $mail = (new NewProcessesFoundNotification($client, [['radicado' => '11001', 'despacho' => self::XSS, 'fecha' => '']]))->toMail($this->admin);

        $html = (string) $mail->render();

        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_n8n_case_events_do_not_include_client_personal_data(): void
    {
        config(['services.n8n.webhook_url' => 'https://n8n.example.com/webhook/x', 'services.n8n.events' => '*']);
        Queue::fake();

        $client = Client::factory()->create([
            'firm_id' => $this->firm->id,
            'first_name' => 'Nombre Secreto',
            'email' => 'cliente.privado@example.com',
            'document_number' => '99887766',
            'phone' => '3009998877',
        ]);
        $case = $this->makeCase(['client_id' => $client->id, 'is_demo' => false]);

        $payload = json_encode(WebhookDelivery::where('event', 'case.created')->latest('id')->firstOrFail()->payload);

        foreach (['Nombre Secreto', 'cliente.privado@example.com', '99887766', '3009998877'] as $personalData) {
            $this->assertStringNotContainsString($personalData, $payload);
        }
        $this->assertStringContainsString($case->case_number, $payload);
    }

    public function test_n8n_firms_api_requires_token_and_never_exposes_clients(): void
    {
        config(['services.n8n.api_token' => 'token-n8n-seguridad-suficientemente-largo']);
        Client::factory()->create(['firm_id' => $this->firm->id, 'document_number' => '55443322']);

        $this->getJson('/n8n/firmas')->assertUnauthorized();
        $this->getJson('/n8n/firmas', ['Authorization' => 'Bearer token-n8n-seguridad-suficientemente-largX'])->assertUnauthorized();

        $response = $this->getJson('/n8n/firmas', ['Authorization' => 'Bearer token-n8n-seguridad-suficientemente-largo'])->assertOk();
        $this->assertStringNotContainsString('55443322', $response->getContent());
    }

    public function test_n8n_api_is_disabled_when_token_is_not_configured(): void
    {
        config(['services.n8n.api_token' => null]);

        $this->getJson('/n8n/estado', ['Authorization' => 'Bearer '])->assertUnauthorized();
    }

    public function test_samai_links_always_point_to_samai_even_with_malicious_data(): void
    {
        Http::swap(new HttpFactory);
        Http::fake([
            '*/corporaciones' => Http::response(['ok' => true, 'data' => []]),
            '*/buscar' => Http::response(['ok' => true, 'data' => ['items' => [[
                'radicado' => '11001333501820260048800',
                'guid' => '"><script>alert(1)</script>',
                'demandante' => self::XSS,
            ]]]]),
        ]);

        $item = app(SamaiService::class)->findByRadicado('11001333501820260048800');

        $this->assertStringStartsWith(SamaiService::BASE_URL.'/Vistas/Casos/list_procesos.aspx?guid=', $item['url']);
        $this->assertStringNotContainsString('<', $item['url']);
        $this->assertStringNotContainsString('"', $item['url']);
    }

    public function test_collaborator_cannot_open_superadmin_integrations(): void
    {
        $this->actingAs($this->collaborator)->get('/admin/integracion-n8n')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/integracion-n8n')->assertForbidden();
    }

    public function test_case_view_escapes_client_name_in_share_buttons(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'first_name' => self::XSS]);
        $case = $this->makeCase(['client_id' => $client->id]);
        CaseEvent::factory()->create(['legal_case_id' => $case->id, 'title' => self::XSS]);

        $html = $this->actingAs($this->admin)->get("/admin/legal-cases/{$case->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCase(array $attributes = []): LegalCase
    {
        return LegalCase::factory()->create(array_merge([
            'firm_id' => $this->firm->id,
            'user_id' => $this->admin->id,
            'is_demo' => false,
        ], $attributes));
    }
}
