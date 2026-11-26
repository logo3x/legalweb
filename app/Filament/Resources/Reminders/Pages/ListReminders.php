<?php

namespace App\Filament\Resources\Reminders\Pages;

use App\Filament\Resources\Reminders\ReminderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReminders extends ListRecords
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo recordatorio'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Audiencias, reuniones y tareas. Le avisamos por correo antes de cada una. Los términos de sus procesos están en Vencimientos.';
    }
}
