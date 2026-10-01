<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentStorageSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $firm = Firm::factory()->create();
        $this->owner = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $case = LegalCase::factory()->create(['firm_id' => $firm->id, 'user_id' => $this->owner->id]);

        Storage::disk('local')->put("documents/{$firm->id}/01J9ABC.pdf", '%PDF-contenido');

        $this->document = Document::create([
            'legal_case_id' => $case->id,
            'name' => 'poder firmado.pdf',
            'file_path' => "documents/{$firm->id}/01J9ABC.pdf",
            'file_type' => 'pdf',
        ]);
    }

    public function test_owner_firm_downloads_document_as_attachment_with_original_name(): void
    {
        $this->actingAs($this->owner)
            ->get(route('documents.file', $this->document))
            ->assertOk()
            ->assertDownload('poder firmado.pdf');
    }

    public function test_user_of_another_firm_cannot_download_document(): void
    {
        $intruder = User::factory()->create(['firm_id' => Firm::factory()->create()->id, 'role' => 'admin']);

        $this->actingAs($intruder)
            ->get(route('documents.file', $this->document))
            ->assertNotFound();
    }

    public function test_collaborator_without_case_assignment_cannot_download_document(): void
    {
        $collaborator = User::factory()->create(['firm_id' => $this->owner->firm_id, 'role' => 'abogado']);

        $this->actingAs($collaborator)
            ->get(route('documents.file', $this->document))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('documents.file', $this->document))->assertRedirect('/admin/login');
    }

    public function test_legacy_document_on_public_disk_is_still_downloadable_by_owner(): void
    {
        Storage::disk('public')->put('documents/antiguo.pdf', '%PDF-antiguo');
        $this->document->update(['file_path' => 'documents/antiguo.pdf']);

        $this->actingAs($this->owner)
            ->get(route('documents.file', $this->document))
            ->assertOk()
            ->assertDownload('poder firmado.pdf');
    }

    public function test_missing_file_returns_not_found(): void
    {
        $this->document->update(['file_path' => 'documents/no-existe.pdf']);

        $this->actingAs($this->owner)
            ->get(route('documents.file', $this->document))
            ->assertNotFound();
    }
}
