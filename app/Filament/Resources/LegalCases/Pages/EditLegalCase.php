<?php

namespace App\Filament\Resources\LegalCases\Pages;

use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseFlowProgress;
use App\Models\FlowStep;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLegalCase extends EditRecord
{
    protected static string $resource = LegalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->record->is_demo) {
            Notification::make()
                ->title('Caso de ejemplo')
                ->body('Los casos de ejemplo no se pueden editar. Cree un nuevo caso para empezar.')
                ->warning()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if (! $record->wasChanged('case_flow_id') || ! $record->case_flow_id) {
            return;
        }

        $record->flowProgress()->delete();

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

    protected function getSaveFormAction(): Action
    {
        $action = parent::getSaveFormAction();

        if ($this->record->is_demo) {
            $action->label('Caso de ejemplo (no editable)')->disabled();
        }

        return $action;
    }
}
