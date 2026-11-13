<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\MonthlyReportNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ClientWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $lawyer;

    private bool $twilioFails = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.enabled' => true,
            'services.twilio.sid' => 'AC123',
            'services.twilio.auth_token' => 'secret',
            'services.twilio.whatsapp_from' => '+15550001111',
            'services.twilio.templates' => [
                'document.requested.customer' => 'HXdoc',
                'portal.shared.customer' => 'HXportal',
                'monthly.report.customer' => 'HXmonthly',
            ],
        ]);

        Http::fake([
            'api.twilio.com/*' => fn () => $this->twilioFails
                ? Http::response(['code' => 63016, 'message' => 'error'], 400)
                : Http::response(['sid' => 'SM1'], 201),
            '*' => Http::response([], 200),
        ]);

        $this->firm = Firm::factory()->create(['name' => 'Bufete Ruiz']);
        $this->lawyer = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
    }

    public function test_consent_date_is_recorded_when_enabled_and_cleared_when_disabled(): void
    {
        $client = $this->makeClient(['whatsapp_enabled' => true]);

        $this->assertNotNull($client->whatsapp_consent_at);

        $client->update(['whatsapp_enabled' => false]);

        $this->assertNull($client->fresh()->whatsapp_consent_at);
    }

    public function test_client_is_only_routed_to_whatsapp_with_consent_phone_and_not_demo(): void
    {
        $this->assertSame('3001234567', $this->makeClient(['whatsapp_enabled' => true])->routeNotificationForWhatsapp());
        $this->assertNull($this->makeClient(['whatsapp_enabled' => false])->routeNotificationForWhatsapp());
        $this->assertNull($this->makeClient(['whatsapp_enabled' => true, 'phone' => null])->routeNotificationForWhatsapp());
        $this->assertNull($this->makeClient(['whatsapp_enabled' => true, 'is_demo' => true])->routeNotificationForWhatsapp());
    }

    public function test_requested_document_notifies_the_client_only_once(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]), portal: true);

        $document = Document::factory()->create([
            'legal_case_id' => $case->id,
            'name' => 'Poder especial',
            'responsible' => 'cliente',
            'status' => 'pendiente',
        ]);

        $document->update(['notes' => 'Autenticado en notaria']);
        $document->update(['status' => 'solicitado']);

        $this->assertNotNull($document->fresh()->client_notified_at);

        $portalUrl = route('portal.show', $case->portal_token);
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($portalUrl): bool {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['To'] === 'whatsapp:+573001234567'
                && $request['ContentSid'] === 'HXdoc'
                && $variables['2'] === 'Poder especial'
                && $variables['3'] === 'Bufete Ruiz'
                && $variables['4'] === $portalUrl;
        });
    }

    public function test_document_changed_to_requested_status_notifies_the_client(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]));

        $document = Document::factory()->create(['legal_case_id' => $case->id, 'responsible' => 'cliente', 'status' => 'en_tramite']);
        Http::assertNothingSent();

        $document->update(['status' => 'solicitado']);

        Http::assertSent(function (Request $request): bool {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['ContentSid'] === 'HXdoc' && $variables['4'] === 'comuniquese con su abogado';
        });
    }

    public function test_document_request_is_not_sent_without_consent(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => false]), portal: true);

        $document = Document::factory()->create(['legal_case_id' => $case->id, 'responsible' => 'cliente', 'status' => 'pendiente']);

        Http::assertNothingSent();
        $this->assertNull($document->fresh()->client_notified_at);
    }

    public function test_documents_for_other_responsibles_do_not_notify_the_client(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]));

        Document::factory()->create(['legal_case_id' => $case->id, 'responsible' => 'abogado', 'status' => 'pendiente']);

        Http::assertNothingSent();
    }

    public function test_demo_cases_and_clients_never_send_whatsapp(): void
    {
        $demoCase = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]), portal: true, attributes: ['is_demo' => true]);
        Document::factory()->create(['legal_case_id' => $demoCase->id, 'responsible' => 'cliente', 'status' => 'pendiente']);

        $demoClientCase = $this->makeCase($this->makeClient(['whatsapp_enabled' => true, 'is_demo' => true]), portal: true);
        Document::factory()->create(['legal_case_id' => $demoClientCase->id, 'responsible' => 'cliente', 'status' => 'pendiente']);

        $this->actingAs($this->lawyer);
        Filament::setCurrentPanel('admin');
        Livewire::test(ViewLegalCase::class, ['record' => $demoCase->getRouteKey()])
            ->assertActionHidden(['compartir', 'send_portal_whatsapp']);

        $this->assertNotContains(WhatsAppChannel::class, (new MonthlyReportNotification($demoCase, '/tmp/x.pdf', 'octubre 2026'))->via($demoCase->client));

        Http::assertNothingSent();
    }

    public function test_lawyer_sends_the_portal_link_by_whatsapp(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]));

        $this->actingAs($this->lawyer);
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewLegalCase::class, ['record' => $case->getRouteKey()])
            ->callAction(['compartir', 'send_portal_whatsapp'])
            ->assertNotified('Enlace enviado por WhatsApp');

        $portalUrl = route('portal.show', $case->fresh()->portal_token);
        Http::assertSent(function (Request $request) use ($case, $portalUrl): bool {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['ContentSid'] === 'HXportal'
                && $variables['1'] === 'Laura Gomez'
                && $variables['2'] === $case->case_number
                && $variables['4'] === $portalUrl;
        });
    }

    public function test_portal_whatsapp_button_reports_twilio_errors(): void
    {
        $this->twilioFails = true;
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]));

        $this->actingAs($this->lawyer);
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewLegalCase::class, ['record' => $case->getRouteKey()])
            ->callAction(['compartir', 'send_portal_whatsapp'])
            ->assertNotified('No se pudo enviar el WhatsApp');
    }

    public function test_portal_whatsapp_button_is_hidden_without_consent_or_twilio(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => false]));

        $this->actingAs($this->lawyer);
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewLegalCase::class, ['record' => $case->getRouteKey()])
            ->assertActionHidden(['compartir', 'send_portal_whatsapp']);

        $case->client->update(['whatsapp_enabled' => true]);
        config(['services.twilio.sid' => null]);

        Livewire::test(ViewLegalCase::class, ['record' => $case->getRouteKey()])
            ->assertActionHidden(['compartir', 'send_portal_whatsapp']);

        Http::assertNothingSent();
    }

    public function test_collaborator_without_share_permission_cannot_send_the_portal(): void
    {
        $case = $this->makeCase($this->makeClient(['whatsapp_enabled' => true]));
        $collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);
        CasePermission::create([
            'user_id' => $collaborator->id,
            'legal_case_id' => $case->id,
            'permissions' => ['case.view'],
            'assigned_by' => $this->lawyer->id,
        ]);

        $this->actingAs($collaborator);
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewLegalCase::class, ['record' => $case->getRouteKey()])
            ->assertActionHidden('compartir');

        Http::assertNothingSent();
    }

    public function test_monthly_report_adds_whatsapp_notice_for_consenting_clients(): void
    {
        $client = $this->makeClient(['whatsapp_enabled' => true]);
        $case = $this->makeCase($client);
        $notification = new MonthlyReportNotification($case, '/tmp/x.pdf', 'octubre 2026');

        $this->assertSame(['mail', WhatsAppChannel::class], $notification->via($client));
        $this->assertSame(['mail'], $notification->via($this->makeClient(['whatsapp_enabled' => false])));

        app(WhatsAppChannel::class)->send($client, $notification);

        Http::assertSent(function (Request $request) use ($case): bool {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['ContentSid'] === 'HXmonthly'
                && $variables['2'] === $case->case_number
                && $variables['3'] === 'octubre 2026';
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeClient(array $attributes = []): Client
    {
        return Client::factory()->create(array_merge([
            'firm_id' => $this->firm->id,
            'user_id' => $this->lawyer->id,
            'first_name' => 'Laura',
            'last_name' => 'Gomez',
            'phone' => '3001234567',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCase(Client $client, bool $portal = false, array $attributes = []): LegalCase
    {
        $case = LegalCase::factory()->create(array_merge([
            'firm_id' => $this->firm->id,
            'user_id' => $this->lawyer->id,
            'client_id' => $client->id,
        ], $attributes));

        if ($portal) {
            $case->generatePortalToken();
        }

        return $case;
    }
}
