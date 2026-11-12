<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Pages\UpgradePlan;
use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseFlowProgress;
use App\Models\FlowStep;
use App\Services\FirmPlan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLegalCase extends CreateRecord
{
    protected static string $resource = LegalCaseResource::class;

    public function mount(): void
    {
        $firm = auth()->user()->firm;

        if ($firm && ! $firm->planLimits()->canAddActiveCase()) {
            Notification::make()
                ->title('Alcanzo el limite de casos activos')
                ->body(FirmPlan::upgradeMessage('El plan Gratuito permite '.$firm->planLimits()->maxActiveCases().' casos activos. Puede cerrar o archivar un caso, o'))
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(UpgradePlan::getUrl());

            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['firm_id'] = auth()->user()->firm_id;

        return $data;
    }

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
