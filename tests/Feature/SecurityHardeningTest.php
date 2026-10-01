<?php

namespace Tests\Feature;

use App\Filament\Resources\CaseFlows\CaseFlowResource;
use App\Filament\Resources\CaseTypes\CaseTypeResource;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Services\GeneratedFileStore;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/private/generated/990001'));
        File::deleteDirectory(storage_path('app/private/generated/990002'));

        parent::tearDown();
    }

    private function makeUser(?int $firmId, string $role = 'admin'): User
    {
        $user = User::factory()->make(['firm_id' => $firmId, 'role' => $role]);
        $user->id = 424242;

        return $user;
    }

    public function test_password_registration_page_is_disabled(): void
    {
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_user_without_firm_sees_no_firm_data(): void
    {
        $this->actingAs($this->makeUser(null));

        foreach ([LegalCase::class, Client::class, Reminder::class] as $model) {
            $this->assertStringContainsString('1 = 0', $model::query()->toSql(), $model);
        }
    }

    public function test_user_with_firm_only_sees_own_firm_data(): void
    {
        $this->actingAs($this->makeUser(990001));

        foreach ([LegalCase::class, Client::class, Reminder::class] as $model) {
            $query = $model::query();

            $this->assertStringContainsString('"firm_id" = ?', $query->toSql(), $model);
            $this->assertContains(990001, $query->getBindings(), $model);
        }
    }

    public function test_panel_requires_a_firm_unless_superadmin(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertFalse($this->makeUser(null, 'abogado')->canAccessPanel($panel));
        $this->assertFalse($this->makeUser(null, 'admin')->canAccessPanel($panel));
        $this->assertTrue($this->makeUser(990001, 'abogado')->canAccessPanel($panel));
        $this->assertTrue($this->makeUser(null, 'superadmin')->canAccessPanel($panel));
    }

    public function test_only_superadmin_can_modify_shared_case_types_and_flows(): void
    {
        $record = new CaseType;

        $this->actingAs($this->makeUser(990001, 'admin'));

        foreach ([CaseTypeResource::class, CaseFlowResource::class] as $resource) {
            $this->assertFalse($resource::canCreate(), $resource);
            $this->assertFalse($resource::canEdit($record), $resource);
            $this->assertFalse($resource::canDelete($record), $resource);
            $this->assertFalse($resource::canDeleteAny(), $resource);
        }

        $this->actingAs($this->makeUser(990001, 'superadmin'));

        foreach ([CaseTypeResource::class, CaseFlowResource::class] as $resource) {
            $this->assertTrue($resource::canCreate(), $resource);
            $this->assertTrue($resource::canDeleteAny(), $resource);
        }
    }

    public function test_generated_files_are_stored_privately_per_firm(): void
    {
        $store = app(GeneratedFileStore::class);

        $path = $store->pathFor(990001, 'reporte_LW-0001-2026.pdf');

        $this->assertStringStartsWith(storage_path('app'.DIRECTORY_SEPARATOR.'private'), str_replace('/', DIRECTORY_SEPARATOR, $path));
        $this->assertStringNotContainsString('public', $path);

        file_put_contents($path, '%PDF-test');

        $this->assertSame($path, $store->find(990001, 'reporte_LW-0001-2026.pdf'));
        $this->assertNull($store->find(990002, 'reporte_LW-0001-2026.pdf'));
        $this->assertNull($store->find(990002, '../990001/reporte_LW-0001-2026.pdf'));
    }

    public function test_download_only_serves_files_of_the_users_firm_and_deletes_them(): void
    {
        $path = app(GeneratedFileStore::class)->pathFor(990001, 'borrador_poder_LW-0001-2026.docx');
        file_put_contents($path, 'contenido');

        $this->actingAs($this->makeUser(990002))
            ->get(route('download.file', 'borrador_poder_LW-0001-2026.docx'))
            ->assertNotFound();

        $this->actingAs($this->makeUser(null))
            ->get(route('download.file', 'borrador_poder_LW-0001-2026.docx'))
            ->assertNotFound();

        $response = $this->actingAs($this->makeUser(990001))
            ->get(route('download.file', 'borrador_poder_LW-0001-2026.docx'))
            ->assertOk()
            ->assertDownload('borrador_poder_LW-0001-2026.docx');

        // El archivo se borra al terminar de enviarse (el cliente de pruebas no lo envia por si solo).
        ob_start();
        $response->baseResponse->sendContent();
        ob_end_clean();

        $this->assertFileDoesNotExist($path);
    }

    public function test_download_requires_authentication(): void
    {
        $this->get(route('download.file', 'reporte.pdf'))->assertRedirect('/admin/login');
    }
}
