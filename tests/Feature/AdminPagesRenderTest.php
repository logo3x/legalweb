<?php

namespace Tests\Feature;

use App\Models\CaseEvent;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\TybaSyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->user = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
    }

    public function test_firm_settings_page_renders_with_whatsapp_section(): void
    {
        config(['services.twilio.enabled' => true]);

        $this->actingAs($this->user)
            ->get('/admin/firm-settings')
            ->assertOk()
            ->assertSee('Notificaciones por WhatsApp');
    }

    public function test_whatsapp_is_hidden_while_disabled(): void
    {
        config(['services.twilio.enabled' => false]);

        $this->actingAs($this->user)
            ->get('/admin/firm-settings')
            ->assertOk()
            ->assertDontSee('Notificaciones por WhatsApp');
    }

    public function test_court_analytics_page_renders_with_data(): void
    {
        $case = LegalCase::factory()->create([
            'firm_id' => $this->user->firm_id,
            'user_id' => $this->user->id,
            'court' => 'Juzgado 03 Civil Municipal',
            'status' => 'abierto',
        ]);
        CaseEvent::factory()->count(2)->create(['legal_case_id' => $case->id]);

        $this->actingAs($this->user)
            ->get('/admin/analitica-despachos')
            ->assertOk()
            ->assertSee('Juzgado 03 Civil Municipal');
    }

    public function test_court_analytics_page_renders_empty_state(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/analitica-despachos')
            ->assertOk()
            ->assertSee('Aun no hay casos con despacho asignado');
    }

    public function test_case_view_renders_with_sync_changes(): void
    {
        $case = LegalCase::factory()->create([
            'firm_id' => $this->user->firm_id,
            'user_id' => $this->user->id,
            'external_case_number' => '11001400300120260012300',
        ]);
        TybaSyncLog::create([
            'legal_case_id' => $case->id,
            'status' => 'ok',
            'nuevas_actuaciones' => 1,
            'mensaje' => '1 nueva(s) actuacion(es)',
            'cambios' => ['actuaciones' => [['fecha' => '05/11/2026', 'tipo' => 'Auto admite', 'anotacion' => '']], 'campos' => []],
            'origen' => 'manual',
        ]);

        $this->actingAs($this->user)
            ->get("/admin/legal-cases/{$case->id}")
            ->assertOk();
    }

    public function test_portal_highlights_documents_the_client_must_provide(): void
    {
        $case = LegalCase::factory()->create([
            'firm_id' => $this->user->firm_id,
            'user_id' => $this->user->id,
            'portal_token' => str_repeat('a', 64),
            'portal_enabled' => true,
        ]);
        Document::factory()->create(['legal_case_id' => $case->id, 'name' => 'Cedula ampliada', 'responsible' => 'cliente', 'status' => 'pendiente']);
        Document::factory()->create(['legal_case_id' => $case->id, 'name' => 'Registro civil', 'responsible' => 'cliente', 'status' => 'en_tramite']);

        $this->withSession(["portal_accepted_{$case->id}" => true])
            ->get(route('portal.show', $case->portal_token))
            ->assertOk()
            ->assertSeeInOrder(['Documentos que debe aportar', 'Cedula ampliada', 'Registro civil', 'Ya aviso que lo tiene listo', 'Sus Datos']);
    }
}
