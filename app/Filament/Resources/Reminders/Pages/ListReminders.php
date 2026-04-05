<?php

namespace App\Filament\Resources\Reminders\Pages;

use App\Filament\Resources\Reminders\ReminderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ListReminders extends ListRecords
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Recordatorio'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Agenda de Recordatorios')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Text::make('Organice su practica legal con recordatorios de audiencias, vencimientos de terminos, reuniones y tareas. Reciba alertas por email antes de cada evento para no perder ningun plazo importante. Puede asociar cada recordatorio a un caso especifico para tener todo centralizado.')
                            ->color('neutral'),
                    ])
                    ->collapsible()
                    ->compact(),
                $this->getTabsContentComponent(),
                EmbeddedTable::make(),
            ]);
    }
}
