<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\CaseEvent;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('legal_case_id')
                    ->label('Caso')
                    ->relationship('legalCase', 'title', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->case_number} - {$record->title}")
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('case_event_id')
                    ->label('Actuación (opcional)')
                    ->options(function ($get) {
                        $caseId = $get('legal_case_id');
                        if (! $caseId) {
                            return [];
                        }

                        return CaseEvent::where('legal_case_id', $caseId)
                            ->orderByDesc('event_date')
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->event_date->format('d/m/Y').' - '.$e->title]);
                    })
                    ->searchable()
                    ->preload()
                    ->helperText('Seleccione primero un caso para ver sus actuaciones.'),
                TextInput::make('name')
                    ->label('Nombre del Documento')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label('Archivo')
                    ->disk('public')
                    ->directory('documents')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->downloadable()
                    ->openable()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
