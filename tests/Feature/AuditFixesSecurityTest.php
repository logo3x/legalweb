<?php

namespace Tests\Feature;

use App\Filament\Pages\FirmSettings;
use App\Filament\Resources\CaseEvents\CaseEventResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Models\CaseEvent;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\CaseUpdatedNotification;
use App\Notifications\ClientDocumentReadyNotification;
use App\Notifications\PortalAccessNotification;
use App\Services\TybaService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Pruebas de las correcciones de la auditoria OWASP del 11 de noviembre de 2026.
 */
class AuditFixesSecurityTest extends TestCase
{
    use RefreshDatabase;

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

        $this->firm = Firm::factory()->create(['name' => 'Firma Original']);
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $this->collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);

        $assignedClient = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'first_name' => 'ClienteAsignado']);
        $otherClient = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'first_name' => 'ClienteAjeno']);

        $this->assignedCase = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $assignedClient->id, 'is_demo' => false]);
        $this->otherCase = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $otherClient->id, 'is_demo' => false]);

        CasePermission::create([
            'user_id' => $this->collaborator->id,
            'legal_case_id' => $this->assignedCase->id,
            'permissions' => ['case.view'],
            'assigned_by' => $this->admin->id,
        ]);
    }

    /** A1 */
    public function test_case_events_and_documents_resources_respect_case_permissions(): void
    {
        $event = CaseEvent::factory()->create(['legal_case_id' => $this->assignedCase->id, 'title' => 'EVENTO-SOLO-LECTURA']);
        $otherEvent = CaseEvent::factory()->create(['legal_case_id' => $this->otherCase->id, 'title' => 'EVENTO-AJENO']);
        Document::factory()->create(['legal_case_id' => $this->assignedCase->id, 'name' => 'DOC-SIN-PERMISO']);

        $this->actingAs($this->collaborator);

        // Solo tiene case.view: no ve actuaciones ni documentos, ni puede crear o editar.
        $this->get('/admin/case-events')->assertOk()->assertDontSee('EVENTO-SOLO-LECTURA')->assertDontSee('EVENTO-AJENO');
        $this->get('/admin/documents')->assertOk()->assertDontSee('DOC-SIN-PERMISO');
        $this->get("/admin/case-events/{$event->id}/edit")->assertNotFound();
        $this->assertFalse(CaseEventResource::canCreate());
        $this->assertFalse(CaseEventResource::canEdit($event));
        $this->assertFalse(CaseEventResource::canDeleteAny());
        $this->assertFalse(DocumentResource::canCreate());

        CasePermission::where('user_id', $this->collaborator->id)->update(['permissions' => ['case.view', 'events.view', 'events.create']]);

        $this->get('/admin/case-events')->assertSee('EVENTO-SOLO-LECTURA')->assertDontSee('EVENTO-AJENO');
        $this->assertTrue(CaseEventResource::canCreate());
        $this->assertFalse(CaseEventResource::canEdit($event));
        $this->assertFalse(CaseEventResource::canEdit($otherEvent));

        $this->actingAs($this->admin);
        $this->assertTrue(CaseEventResource::canEdit($otherEvent));
        $this->get('/admin/case-events')->assertSee('EVENTO-AJENO');
    }

    /** A1: descarga de archivos antiguos */
    public function test_document_download_requires_documents_view_permission(): void
    {
        $document = Document::factory()->create(['legal_case_id' => $this->assignedCase->id, 'file_path' => 'documents/x.pdf']);

        $this->actingAs($this->collaborator)->get(route('documents.file', $document))->assertForbidden();
    }

    /** A2 */
    public function test_collaborator_only_sees_clients_and_reminders_of_assigned_cases(): void
    {
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'legal_case_id' => $this->otherCase->id, 'title' => 'RECORDATORIO-AJENO']);
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'legal_case_id' => $this->assignedCase->id, 'title' => 'RECORDATORIO-DEL-CASO']);
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->collaborator->id, 'legal_case_id' => null, 'title' => 'RECORDATORIO-PROPIO']);
        $ownClient = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->collaborator->id, 'first_name' => 'ClientePropio']);

        $this->actingAs($this->collaborator);

        $this->assertEqualsCanonicalizing(['ClienteAsignado', 'ClientePropio'], Client::pluck('first_name')->all());
        $this->assertEqualsCanonicalizing(['RECORDATORIO-DEL-CASO', 'RECORDATORIO-PROPIO'], Reminder::pluck('title')->all());
        $this->get('/admin/clients')->assertOk()->assertDontSee('ClienteAjeno');

        $this->actingAs($this->admin);
        $this->assertSame(3, Client::count());
        $this->assertNotNull($ownClient->fresh());
    }

    /** M1 */
    public function test_collaborator_cannot_change_firm_data_but_keeps_personal_preferences(): void
    {
        $this->actingAs($this->collaborator);

        $this->get('/admin/firm-settings')->assertOk()->assertDontSee('Datos principales');

        Livewire::test(FirmSettings::class)
            ->set('data.name', 'Firma Hackeada')
            ->set('data.email', 'atacante@example.com')
            ->set('data.security_email_enabled', false)
            ->call('save');

        $this->assertSame('Firma Original', $this->firm->fresh()->name);
        $this->assertFalse((bool) $this->collaborator->fresh()->security_email_enabled);
    }

    /** M2 */
    public function test_notification_emails_do_not_render_html_or_links_from_data(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'first_name' => '<b>Juan</b>']);
        $event = new CaseEvent([
            'title' => '<a href="https://evil.test">Pague aqui</a> [Pague](https://evil.test/md) <script>alert(1)</script>',
            'description' => '**importante**',
            'event_type' => 'auto',
            'event_date' => now(),
        ]);

        $html = (string) (new CaseUpdatedNotification($this->assignedCase, $event))->toMail($client)->render();

        // Ningun enlace real hacia el dominio del atacante (como texto escapado si puede aparecer).
        $this->assertDoesNotMatchRegularExpression('/<a\s[^>]*href=["\']?https:\/\/evil\.test/i', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<b>Juan</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** M3 */
    public function test_portal_actions_do_not_repeat_notifications(): void
    {
        Notification::fake();
        $this->assignedCase->update(['portal_token' => str_repeat('a', 64), 'portal_enabled' => true]);
        $document = Document::factory()->create(['legal_case_id' => $this->assignedCase->id, 'responsible' => 'cliente', 'status' => 'pendiente']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.accept', $this->assignedCase->portal_token));
            $this->post(route('portal.document.ready', ['token' => $this->assignedCase->portal_token, 'document' => $document->id]));
            $this->post(route('portal.document.link', ['token' => $this->assignedCase->portal_token, 'document' => $document->id]), ['external_url' => 'https://drive.google.com/x']);
        }

        Notification::assertSentToTimes($this->admin, PortalAccessNotification::class, 1);
        Notification::assertSentToTimes($this->admin, ClientDocumentReadyNotification::class, 2);
    }

    /** M4 */
    public function test_portal_of_trashed_case_or_client_is_not_available(): void
    {
        $this->assignedCase->update(['portal_token' => str_repeat('b', 64), 'portal_enabled' => true]);
        $this->get(route('portal.show', $this->assignedCase->portal_token))->assertOk();

        $this->assignedCase->client->delete();
        $this->get(route('portal.show', $this->assignedCase->portal_token))->assertNotFound();

        $this->assignedCase->client()->withTrashed()->first()->restore();
        $this->assignedCase->delete();
        $this->get(route('portal.show', $this->assignedCase->portal_token))->assertNotFound();
        $this->post(route('portal.accept', $this->assignedCase->portal_token))->assertNotFound();
    }

    /** M5 */
    public function test_radicado_only_accepts_digits_dashes_and_spaces(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditLegalCase::class, ['record' => $this->assignedCase->getRouteKey()])
            ->fillForm(['external_case_number' => '<script src=//ev.il/x></script>'])
            ->call('save')
            ->assertHasFormErrors(['external_case_number' => 'regex']);
    }

    /** B1 */
    public function test_google_login_with_unverified_email_cannot_take_over_an_account(): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => 'google-999',
            'email' => $this->admin->email,
            'name' => 'Impostor',
            'avatar' => null,
        ])->setRaw(['email_verified' => false]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $this->get('/auth/google/callback')->assertRedirect('/admin/login');

        $this->assertGuest();
        $this->assertNull($this->admin->fresh()->google_id);
    }

    /** B2 */
    public function test_monitoring_skips_trashed_clients(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'document_number' => '123456', 'process_monitoring_enabled' => true, 'is_demo' => false]);
        $client->delete();

        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('searchByDocument'));

        $this->artisan('app:monitor-client-processes')->assertSuccessful();
    }
}
