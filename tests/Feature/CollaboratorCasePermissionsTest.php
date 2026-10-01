<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Filament\Resources\LegalCases\RelationManagers\BillingRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\EventsRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\FlowProgressRelationManager;
use App\Filament\Resources\LegalCases\RelationManagers\PortalAccessLogsRelationManager;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CollaboratorCasePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $collaborator;

    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $this->collaborator = User::factory()->create(['firm_id' => $firm->id, 'role' => 'abogado']);
        $client = Client::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->admin->id]);
        $this->case = LegalCase::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->admin->id, 'client_id' => $client->id]);

        // Ninguna prueba debe llamar a un proveedor de IA real.
        Http::fake();

        // Colaborador con permisos de solo lectura sobre caso y actuaciones.
        CasePermission::create([
            'user_id' => $this->collaborator->id,
            'legal_case_id' => $this->case->id,
            'permissions' => ['case.view', 'events.view'],
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_read_only_collaborator_cannot_edit_the_case(): void
    {
        $this->actingAs($this->collaborator);
        $this->assertFalse(LegalCaseResource::canEdit($this->case));

        $this->actingAs($this->admin);
        $this->assertTrue(LegalCaseResource::canEdit($this->case));
    }

    public function test_tabs_are_only_visible_with_the_matching_view_permission(): void
    {
        $this->actingAs($this->collaborator);

        $this->assertTrue(EventsRelationManager::canViewForRecord($this->case, ViewLegalCase::class));
        $this->assertTrue(BillingRelationManager::canViewForRecord($this->case, ViewLegalCase::class));
        $this->assertFalse(DocumentsRelationManager::canViewForRecord($this->case, ViewLegalCase::class));
        $this->assertFalse(FlowProgressRelationManager::canViewForRecord($this->case, ViewLegalCase::class));
        $this->assertFalse(PortalAccessLogsRelationManager::canViewForRecord($this->case, ViewLegalCase::class));
    }

    public function test_admin_sees_every_tab(): void
    {
        $this->actingAs($this->admin);

        foreach ([EventsRelationManager::class, DocumentsRelationManager::class, FlowProgressRelationManager::class, PortalAccessLogsRelationManager::class] as $manager) {
            $this->assertTrue($manager::canViewForRecord($this->case, ViewLegalCase::class), $manager);
        }
    }

    public function test_read_only_collaborator_cannot_create_or_modify_events(): void
    {
        $this->actingAs($this->collaborator);

        $manager = Livewire::test(EventsRelationManager::class, [
            'ownerRecord' => $this->case,
            'pageClass' => ViewLegalCase::class,
        ])->instance();

        $this->assertFalse($manager->getAuthorizationResponse('create')->allowed());
        $this->assertFalse($manager->getAuthorizationResponse('update')->allowed());
        $this->assertFalse($manager->getAuthorizationResponse('deleteAny')->allowed());
    }

    public function test_collaborator_with_create_permission_can_create_but_not_edit_events(): void
    {
        CasePermission::where('user_id', $this->collaborator->id)->update(['permissions' => json_encode(['case.view', 'events.view', 'events.create'])]);
        $this->actingAs($this->collaborator->fresh());

        $manager = Livewire::test(EventsRelationManager::class, [
            'ownerRecord' => $this->case,
            'pageClass' => ViewLegalCase::class,
        ])->instance();

        $this->assertTrue($manager->getAuthorizationResponse('create')->allowed());
        $this->assertFalse($manager->getAuthorizationResponse('update')->allowed());
    }

    public function test_header_actions_require_their_case_permission(): void
    {
        $this->actingAs($this->collaborator);

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertActionHidden('ai_summary')
            ->assertActionHidden('ai_draft')
            ->assertActionHidden('compartir')
            ->assertActionHidden('toggle_auto_report')
            ->assertActionHidden('edit');

        Http::assertNothingSent();
    }

    public function test_assigning_cases_ignores_other_firms_cases_and_unknown_permissions(): void
    {
        $otherFirm = Firm::factory()->create();
        $otherAdmin = User::factory()->create(['firm_id' => $otherFirm->id, 'role' => 'admin']);
        $otherCase = LegalCase::factory()->create([
            'firm_id' => $otherFirm->id,
            'user_id' => $otherAdmin->id,
            'client_id' => Client::factory()->create(['firm_id' => $otherFirm->id, 'user_id' => $otherAdmin->id])->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('team.assign-cases', $this->collaborator), [
                'cases' => [
                    $this->case->id => ['enabled' => '1', 'permissions' => ['case.view', 'superpoderes']],
                    $otherCase->id => ['enabled' => '1', 'permissions' => ['case.view']],
                ],
            ])
            ->assertRedirect('/admin/team-members');

        $permissions = CasePermission::where('user_id', $this->collaborator->id)->get();

        $this->assertSame([$this->case->id], $permissions->pluck('legal_case_id')->all());
        $this->assertSame(['case.view'], $permissions->first()->permissions);
    }

    public function test_admin_sees_header_actions(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ViewLegalCase::class, ['record' => $this->case->getRouteKey()])
            ->assertActionVisible('ai_draft')
            ->assertActionVisible('compartir')
            ->assertActionVisible('edit');
    }
}
