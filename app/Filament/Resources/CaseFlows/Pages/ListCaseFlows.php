<?php

namespace App\Filament\Resources\CaseFlows\Pages;

use App\Filament\Resources\CaseFlows\CaseFlowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ListCaseFlows extends ListRecords
{
    protected static string $resource = CaseFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Flujo'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Acerca de los Flujos de Proceso')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Text::make('Los flujos precargados estan basados en las etapas procesales de la legislacion colombiana vigente, incluyendo el Codigo General del Proceso (CGP), el Codigo Procesal del Trabajo (CPT), la Ley 906 de 2004 (Sistema Penal Acusatorio), el CPACA (Ley 1437 de 2011), la Ley 1116 de 2006 (Insolvencia) y la Ley 25 de 1992 (Familia).')
                            ->color('neutral'),
                        Text::make('Estos flujos son completamente editables. Puede modificar los pasos existentes, agregar nuevos, eliminar los que no apliquen o reordenarlos. Tambien puede crear flujos adicionales para otros tipos de proceso o variantes especificas de su practica juridica usando el boton "Nuevo Flujo".')
                            ->color('neutral'),
                    ])
                    ->collapsible()
                    ->compact(),
                $this->getTabsContentComponent(),
                EmbeddedTable::make(),
            ]);
    }
}
