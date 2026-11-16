<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\Reminders\ReminderResource;
use App\Models\CasePermission;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Services\AIProviderClient;
use App\Services\CaseSpreadsheetImporter;
use App\Services\GeneratedFileStore;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pruebas de las correcciones de la revision de seguridad del 16 de noviembre de 2026.
 */
class SecurityReviewNovemberTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    private User $collaborator;

    private Client $client;

    private LegalCase $assignedCase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Http::fake();

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $this->collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'asistente']);
        $this->client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        $this->assignedCase = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $this->client->id, 'is_demo' => false]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function assign(LegalCase $case, array $permissions): void
    {
        CasePermission::create(['user_id' => $this->collaborator->id, 'legal_case_id' => $case->id, 'permissions' => $permissions, 'assigned_by' => $this->admin->id]);
    }

    public function test_collaborator_cannot_edit_or_delete_clients_created_by_others(): void
    {
        $this->assign($this->assignedCase, ['case.view']);
        $own = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->collaborator->id]);
        $this->actingAs($this->collaborator);

        $this->assertFalse(ClientResource::canEdit($this->client));
        $this->assertFalse(ClientResource::canDelete($this->client));
        $this->assertFalse(ClientResource::canDeleteAny());
        $this->assertTrue(ClientResource::canEdit($own));

        $this->get(ClientResource::getUrl('edit', ['record' => $this->client]))->assertForbidden();

        $this->actingAs($this->admin);
        $this->assertTrue(ClientResource::canEdit($this->client));
        Livewire::test(EditClient::class, ['record' => $this->client->getRouteKey()])->assertOk();
    }

    public function test_collaborator_cannot_change_reminders_created_by_others(): void
    {
        $this->assign($this->assignedCase, ['case.view']);
        $deadline = Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'legal_case_id' => $this->assignedCase->id]);
        $own = Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->collaborator->id, 'legal_case_id' => null]);
        $this->actingAs($this->collaborator);

        $this->assertFalse(ReminderResource::canEdit($deadline));
        $this->assertFalse(ReminderResource::canDelete($deadline));
        $this->assertTrue(ReminderResource::canEdit($own));
        $this->get(ReminderResource::getUrl('edit', ['record' => $deadline]))->assertForbidden();
    }

    public function test_case_without_view_permission_is_hidden(): void
    {
        $this->assign($this->assignedCase, ['documents.upload', 'ai.use']);
        $this->actingAs($this->collaborator);

        $this->assertNull(LegalCase::find($this->assignedCase->id));
        $this->assertNull(Client::find($this->client->id));
    }

    public function test_ai_needs_permission_to_view_case_events(): void
    {
        $this->assign($this->assignedCase, ['case.view', 'ai.use']);
        $this->actingAs($this->collaborator);

        Livewire::test(ViewLegalCase::class, ['record' => $this->assignedCase->getRouteKey()])
            ->assertActionHidden('ai_summary');

        CasePermission::where('user_id', $this->collaborator->id)->update(['permissions' => json_encode(['case.view', 'ai.use', 'events.view'])]);

        Livewire::test(ViewLegalCase::class, ['record' => $this->assignedCase->getRouteKey()])
            ->assertActionVisible('ai_summary');
    }

    public function test_monthly_reports_cannot_be_downloaded_by_name(): void
    {
        $this->assign($this->assignedCase, ['case.view']);
        $legacy = app(GeneratedFileStore::class)->pathFor($this->firm->id, 'reporte_LW-0007-2026_2026_10.pdf');
        file_put_contents($legacy, '%PDF-1.4');

        $this->actingAs($this->collaborator)->get('/download/reporte_LW-0007-2026_2026_10.pdf')->assertNotFound();
        $this->assertFileExists($legacy, 'El intento de descarga no debe borrar el archivo.');

        $internal = app(GeneratedFileStore::class)->internalPathFor($this->firm->id, 'reporte_x.pdf');
        $this->assertStringContainsString('generated-internal', $internal);
        $this->assertNull(app(GeneratedFileStore::class)->find($this->firm->id, 'reporte_x.pdf'));

        @unlink($legacy);
    }

    public function test_disabling_the_portal_revokes_the_shared_link(): void
    {
        $this->actingAs($this->admin);
        $oldToken = $this->assignedCase->generatePortalToken();
        $this->get(route('portal.show', $oldToken))->assertOk();

        Livewire::test(ViewLegalCase::class, ['record' => $this->assignedCase->getRouteKey()])->callAction('toggle_portal');
        $this->assertFalse($this->assignedCase->fresh()->portal_enabled);
        $this->assertNull($this->assignedCase->fresh()->portal_token);

        Livewire::test(ViewLegalCase::class, ['record' => $this->assignedCase->getRouteKey()])->callAction('toggle_portal');
        $newToken = $this->assignedCase->fresh()->portal_token;

        $this->assertNotSame($oldToken, $newToken);
        $this->get(route('portal.show', $oldToken))->assertNotFound();
        $this->get(route('portal.show', $newToken))->assertOk();
    }

    public function test_collaborator_import_cannot_reach_hidden_or_trashed_clients(): void
    {
        CaseType::create(['name' => 'Civil']);
        $caseTypes = CaseType::count();
        $hidden = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'document_number' => '111222']);
        $trashed = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->collaborator->id, 'document_number' => '333444']);
        $trashed->delete();
        $this->actingAs($this->collaborator);

        $path = tempnam(sys_get_temp_dir(), 'lwtest').'.csv';
        file_put_contents($path, "Documento,Nombres,Titulo\n111222,Ana,Caso uno\n333444,Luis,Caso dos\n");
        $summary = (new CaseSpreadsheetImporter($this->collaborator))->import($path, 'csv');

        $this->assertSame(0, $summary['created']);
        $this->assertSame(2, $summary['errors']);
        $this->assertTrue(Client::withoutGlobalScopes()->onlyTrashed()->whereKey($trashed->id)->exists(), 'Un colaborador no restaura clientes.');
        $this->assertSame($caseTypes, CaseType::count(), 'No se crean tipos de proceso globales si ya existen.');
    }

    public function test_gemini_key_travels_in_a_header_and_is_redacted_from_errors(): void
    {
        config(['services.gemini.api_key' => 'clave-secreta-gemini', 'services.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta']);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]])]);

        app(AIProviderClient::class)->complete('gemini:gemini-flash-latest', 'sistema', 'mensaje', 100);

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->url(), 'clave-secreta-gemini')
            && $request->header('x-goog-api-key') === ['clave-secreta-gemini']);

        $this->assertStringNotContainsString('clave-secreta', AIProviderClient::redactSecrets('error para https://x/y?key=clave-secreta-gemini&a=1'));
    }

    public function test_responses_send_a_content_security_policy(): void
    {
        $this->get('/')->assertHeader('Content-Security-Policy', "object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
    }
}
