<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseFlowProgress;
use App\Models\Document;
use App\Models\FlowStep;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateLegalCase extends CreateRecord
{
    protected static string $resource = LegalCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['firm_id'] = auth()->user()->firm_id;
        unset($data['initial_documents']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->generateFlowProgress();
        $this->saveInitialDocuments();
    }

    private function generateFlowProgress(): void
    {
        $record = $this->record;

        if (! $record->case_flow_id) {
            return;
        }

        $steps = FlowStep::where('case_flow_id', $record->case_flow_id)
            ->orderBy('order')
            ->get();

        foreach ($steps as $step) {
            CaseFlowProgress::create([
                'legal_case_id' => $record->id,
                'flow_step_id' => $step->id,
                'status' => 'pendiente',
            ]);
        }
    }

    private function saveInitialDocuments(): void
    {
        $files = $this->data['initial_documents'] ?? [];

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $size = Storage::disk('public')->exists($filePath) ? Storage::disk('public')->size($filePath) : null;

            Document::create([
                'legal_case_id' => $this->record->id,
                'name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $extension,
                'file_size' => $size,
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
