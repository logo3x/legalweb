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
        $isAdmin = auth()->user()->isAdmin();

        return [
            Action::make('save_top')
                ->label('Guardar cambios')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(function () {
                    $this->save();
                })
                ->disabled(fn () => $this->record->is_demo),
            RestoreAction::make()
                ->visible($isAdmin)
                ->modalHeading('Restaurar caso eliminado')
                ->modalDescription('Este caso fue enviado a la papelera previamente. Al restaurarlo, volvera a aparecer en su lista de casos activos con todos sus documentos, actuaciones y registros intactos.')
                ->modalSubmitActionLabel('Si, restaurar caso'),
            DeleteAction::make()
                ->visible($isAdmin)
                ->modalHeading('Eliminar caso')
                ->modalDescription('Esta accion movera el caso a la papelera. Podra restaurarlo despues si lo necesita. Se conservaran los documentos, actuaciones y registros asociados.')
                ->modalSubmitActionLabel('Si, eliminar caso'),
            ForceDeleteAction::make()
                ->visible($isAdmin)
                ->modalHeading('Eliminar caso permanentemente')
                ->modalDescription('ADVERTENCIA: Esta accion es IRREVERSIBLE. Se eliminaran permanentemente el caso, todos sus documentos, actuaciones, flujos de proceso y registros asociados. Esta informacion NO podra recuperarse.')
                ->modalSubmitActionLabel('Entiendo, eliminar permanentemente'),
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
