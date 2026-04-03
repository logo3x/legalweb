<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseFlowProgress;
use App\Models\FlowStep;
use Filament\Resources\Pages\CreateRecord;

class CreateLegalCase extends CreateRecord
{
    protected static string $resource = LegalCaseResource::class;

    protected function afterCreate(): void
    {
        $this->generateFlowProgress();
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
}
