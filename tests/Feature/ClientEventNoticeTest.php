<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\EventsRelationManager;
use App\Models\CaseEvent;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\CaseUpdatedNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ClientEventNoticeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    private LegalCase $case;

    private CaseEvent $event;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $this->client = Client::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->admin->id, 'email' => 'cliente@correo.co', 'is_demo' => false]);
        $this->case = LegalCase::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->admin->id, 'client_id' => $this->client->id, 'is_demo' => false, 'portal_enabled' => false]);
        $this->event = CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'title' => 'Auto Fija Fecha', 'event_date' => now()]);
    }

    private function eventsTable()
    {
        return Livewire::test(EventsRelationManager::class, ['ownerRecord' => $this->case, 'pageClass' => ViewLegalCase::class]);
    }

    public function test_lawyer_notifies_the_client_immediately_and_sees_where_it_went(): void
    {
        Notification::fake();
        $this->actingAs($this->admin);

        $this->eventsTable()
            ->callTableAction('notify_client', $this->event)
            ->assertNotified('Correo enviado');

        Notification::assertSentTo($this->client, CaseUpdatedNotification::class, fn (CaseUpdatedNotification $n): bool => $n->event->is($this->event));
    }

    public function test_button_is_disabled_when_the_client_has_no_email_or_is_a_demo(): void
    {
        $this->actingAs($this->admin);

        $this->client->forceFill(['email' => null])->save();
        $this->eventsTable()->assertTableActionDisabled('notify_client', $this->event);

        $this->client->forceFill(['email' => 'demo@ejemplo.com', 'is_demo' => true])->save();
        $this->eventsTable()->assertTableActionDisabled('notify_client', $this->event);
    }

    public function test_collaborator_without_share_permission_cannot_notify_the_client(): void
    {
        $collaborator = User::factory()->create(['firm_id' => $this->case->firm_id, 'role' => 'abogado']);
        CasePermission::create(['user_id' => $collaborator->id, 'legal_case_id' => $this->case->id, 'permissions' => ['case.view', 'events.view'], 'assigned_by' => $this->admin->id]);
        $this->actingAs($collaborator);

        $this->eventsTable()->assertTableActionHidden('notify_client', $this->event);
    }

    public function test_event_form_no_longer_has_the_toggle_that_never_sent_anything(): void
    {
        $this->actingAs($this->admin);

        $this->eventsTable()
            ->mountTableAction('edit', $this->event)
            ->assertDontSee('Notificar al cliente por email');
    }

    public function test_case_page_shows_the_client_email(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertSee('Correo del cliente')
            ->assertSee('cliente@correo.co');
    }

    public function test_client_email_only_links_the_portal_when_it_is_enabled(): void
    {
        $this->case->forceFill(['portal_token' => 'tok-123'])->save();

        $html = (string) (new CaseUpdatedNotification($this->case, $this->event))->toMail($this->client)->render();
        $this->assertStringNotContainsString('Ver estado de mi caso', $html);

        $this->case->forceFill(['portal_enabled' => true])->save();
        $html = (string) (new CaseUpdatedNotification($this->case->fresh(), $this->event))->toMail($this->client)->render();
        $this->assertStringContainsString('Ver estado de mi caso', $html);
    }

    public function test_client_email_leaves_out_notes_meant_for_the_lawyer(): void
    {
        $this->event->forceFill([
            'title' => 'Auto Decide',
            'event_type' => 'actuacion',
            'event_date' => '2026-10-15 00:00:00',
            'rama_documents' => true,
            'description' => 'resuelve aclaracion

Tiene documentos anexos en la Rama Judicial (autos, memoriales u oficios): están abajo, en esta misma actuación, y en el botón «Documentos» de la lista.',
        ])->save();

        $html = (string) (new CaseUpdatedNotification($this->case, $this->event->fresh()))->toMail($this->client)->render();

        $this->assertStringContainsString('resuelve aclaracion', $html);
        $this->assertStringContainsString('Tipo: Actuación | Fecha: 15/10/2026', $html);
        $this->assertStringNotContainsString('15/10/2026 00:00', $html);
        $this->assertStringContainsString('El despacho publicó documentos en esta actuación', $html);
        $this->assertStringNotContainsString('botón «Documentos»', $html);
    }

    public function test_lawyer_can_preview_the_email_before_sending_it(): void
    {
        $this->actingAs($this->admin);

        $table = $this->eventsTable();
        $preview = (fn (CaseEvent $record) => $this->clientEmailPreview($record))->call($table->instance(), $this->event)->render();
        $this->assertStringContainsString('Ver el correo', $preview);

        $mail = (new CaseUpdatedNotification($this->case, $this->event))->toMail($this->client);
        $html = view('filament.modals.client-email-preview', ['subject' => $mail->subject, 'html' => (string) $mail->render()])->render();

        $this->assertStringContainsString('Ver el correo', $html);
        $this->assertStringContainsString('srcdoc=', $html);
        $this->assertStringContainsString('Asunto: <strong style="color: #0F172A;">Actualizacion en su caso', $html);
    }
}
