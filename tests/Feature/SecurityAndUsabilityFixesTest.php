<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Filament\Resources\LegalCases\Pages\CreateLegalCase;
use App\Filament\Resources\LegalCases\Pages\EditLegalCase;
use App\Filament\Resources\LegalCases\Pages\ListLegalCases;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Models\CasePermission;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\UsageLimiter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Correcciones de la revision de seguridad del 12 de noviembre de 2026 y mejoras de uso.
 */
class SecurityAndUsabilityFixesTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    private User $collaborator;

    private LegalCase $writableCase;

    private LegalCase $readOnlyCase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Http::fake();

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin']);
        $this->collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);

        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id]);
        $this->writableCase = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $client->id, 'is_demo' => false]);
        $this->readOnlyCase = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'client_id' => $client->id, 'is_demo' => false]);

        CasePermission::create(['user_id' => $this->collaborator->id, 'legal_case_id' => $this->writableCase->id, 'permissions' => ['case.view', 'documents.view', 'documents.upload'], 'assigned_by' => $this->admin->id]);
        CasePermission::create(['user_id' => $this->collaborator->id, 'legal_case_id' => $this->readOnlyCase->id, 'permissions' => ['case.view', 'documents.view'], 'assigned_by' => $this->admin->id]);
    }

    private function makeFirmReadOnly(): void
    {
        $planId = Plan::create(['name' => 'Pro', 'slug' => 'pro-test'])->id;
        Subscription::create(['firm_id' => $this->firm->id, 'plan_id' => $planId, 'status' => 'past_due', 'starts_at' => now()->subMonths(2), 'ends_at' => now()->subDays(20)]);
    }

    public function test_collaborator_cannot_create_documents_in_cases_without_upload_permission(): void
    {
        $this->actingAs($this->collaborator);

        Livewire::test(CreateDocument::class)
            ->fillForm(['legal_case_id' => $this->readOnlyCase->id, 'name' => 'Documento colado'])
            ->call('create')
            ->assertHasFormErrors(['legal_case_id']);

        $this->assertDatabaseMissing('documents', ['name' => 'Documento colado']);

        Livewire::test(CreateDocument::class)
            ->fillForm(['legal_case_id' => $this->writableCase->id, 'name' => 'Documento permitido'])
            ->call('create')
            ->assertHasNoFormErrors(['legal_case_id']);
    }

    public function test_collaborator_cannot_move_a_document_to_another_case(): void
    {
        CasePermission::where('user_id', $this->collaborator->id)->where('legal_case_id', $this->writableCase->id)
            ->update(['permissions' => ['case.view', 'documents.view', 'documents.upload', 'documents.edit']]);
        $document = Document::factory()->create(['legal_case_id' => $this->writableCase->id]);

        $this->actingAs($this->collaborator);

        Livewire::test(EditDocument::class, ['record' => $document->getRouteKey()])
            ->fillForm(['legal_case_id' => $this->readOnlyCase->id])
            ->call('save')
            ->assertHasFormErrors(['legal_case_id']);

        $this->assertSame($this->writableCase->id, $document->fresh()->legal_case_id);
    }

    public function test_read_only_subscription_blocks_writes_made_from_the_panel(): void
    {
        $this->makeFirmReadOnly();
        $this->actingAs($this->admin);

        Livewire::test(EditLegalCase::class, ['record' => $this->writableCase->getRouteKey()])
            ->fillForm(['title' => 'Titulo cambiado sin pagar'])
            ->call('save')
            ->assertNotified('Cuenta en modo solo lectura');

        $this->assertNotSame('Titulo cambiado sin pagar', $this->writableCase->fresh()->title);

        // Consultar sigue funcionando.
        $this->get(LegalCaseResource::getUrl('view', ['record' => $this->writableCase]))->assertOk();
    }

    public function test_active_subscription_can_still_write(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditLegalCase::class, ['record' => $this->writableCase->getRouteKey()])
            ->fillForm(['title' => 'Titulo actualizado'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Titulo actualizado', $this->writableCase->fresh()->title);
    }

    public function test_same_client_document_can_exist_in_two_firms_without_revealing_it(): void
    {
        $otherFirm = Firm::factory()->create();
        Client::factory()->create(['firm_id' => $otherFirm->id, 'document_number' => '79123456']);

        $this->actingAs($this->admin);

        Livewire::test(CreateClient::class)
            ->fillForm(['document_type' => 'CC', 'document_number' => '79123456', 'first_name' => 'Ana', 'last_name' => 'Rojas'])
            ->call('create')
            ->assertHasNoFormErrors();

        // Dentro de la misma firma si es duplicado.
        Livewire::test(CreateClient::class)
            ->fillForm(['document_type' => 'CC', 'document_number' => '79123456', 'first_name' => 'Otra', 'last_name' => 'Persona'])
            ->call('create')
            ->assertHasFormErrors(['document_number' => 'unique']);

        $this->assertSame(2, Client::withoutGlobalScopes()->where('document_number', '79123456')->count());
    }

    public function test_ai_actions_are_rate_limited_per_user(): void
    {
        $this->actingAs($this->admin);
        $key = 'ai:'.$this->admin->id;

        for ($i = 0; $i < UsageLimiter::LIMITS['ai'][0]; $i++) {
            RateLimiter::hit($key, 3600);
        }

        $this->assertFalse(UsageLimiter::attempt('ai'));
        $this->assertTrue(UsageLimiter::attempt('ai', 'otro-usuario'));
    }

    public function test_case_report_pdf_requires_events_permission(): void
    {
        $this->actingAs($this->collaborator);

        Livewire::test(ViewLegalCase::class, ['record' => $this->readOnlyCase->getRouteKey()])
            ->assertActionHidden('download_report');
    }

    public function test_global_search_finds_cases_and_clients_only_within_permissions(): void
    {
        $client = Client::factory()->create(['firm_id' => $this->firm->id, 'first_name' => 'Gloria', 'last_name' => 'Busqueda', 'document_number' => '5550001']);
        $case = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'external_case_number' => '11001310300120250012300', 'title' => 'Proceso buscable']);
        $foreignCase = LegalCase::factory()->create(['firm_id' => Firm::factory()->create()->id, 'external_case_number' => '11001310300120250012399', 'title' => 'Proceso ajeno']);

        $this->actingAs($this->admin);

        $byRadicado = LegalCaseResource::getGlobalSearchResults('110013103001202500123');
        $titles = $byRadicado->pluck('title')->map(fn ($title) => (string) $title)->implode('|');

        $this->assertStringContainsString($case->case_number, $titles);
        $this->assertStringNotContainsString($foreignCase->case_number, $titles);
        $this->assertStringContainsString('Gloria', LegalCaseResource::getGlobalSearchResults('Gloria')->first()->details['Cliente']);

        // Un colaborador no encuentra casos que no tiene asignados.
        $this->actingAs($this->collaborator);
        $this->assertCount(0, LegalCaseResource::getGlobalSearchResults('Proceso buscable'));
    }

    public function test_client_can_be_created_from_the_case_form(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CreateLegalCase::class)
            ->mountFormComponentAction('client_id', 'createOption')
            ->setFormComponentActionData(['document_type' => 'CC', 'document_number' => '1022333444', 'first_name' => 'Nuevo', 'last_name' => 'Cliente'])
            ->callMountedFormComponentAction()
            ->assertHasNoFormComponentActionErrors();

        $client = Client::where('document_number', '1022333444')->first();

        $this->assertNotNull($client);
        $this->assertSame($this->firm->id, $client->firm_id);
        $this->assertSame($this->admin->id, $client->user_id);
        $component->assertFormSet(['client_id' => $client->id]);
    }

    public function test_case_list_tabs_filter_cases(): void
    {
        $closed = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'status' => 'cerrado', 'case_type_id' => CaseType::factory()]);
        $this->writableCase->update(['status' => 'abierto']);

        $this->actingAs($this->admin);

        Livewire::test(ListLegalCases::class)
            ->set('activeTab', 'activos')
            ->assertCanSeeTableRecords([$this->writableCase])
            ->assertCanNotSeeTableRecords([$closed])
            ->set('activeTab', 'cerrados')
            ->assertCanSeeTableRecords([$closed])
            ->assertCanNotSeeTableRecords([$this->writableCase]);
    }

    public function test_menu_shows_most_used_sections_first_and_deadlines_page(): void
    {
        $this->actingAs($this->admin);

        $html = $this->get('/admin/legal-cases')->assertOk()->getContent();

        $positions = array_map(fn (string $label) => strpos($html, $label), ['Casos', 'Agenda', 'Vencimientos', 'Clientes', 'Seguimiento', 'Herramientas', 'Configuración']);
        $this->assertNotContains(false, $positions);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
        $this->assertStringNotContainsString('>Configuracion<', $html);

        $this->get('/admin/vencimientos')->assertOk();
    }
}
