<?php

namespace App\Filament\Resources\Documents\Schemas;

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
                    ->relationship('legalCase', 'title')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->case_number} - {$record->title}")
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('case_event_id')
                    ->label('Actuación (opcional)')
                    ->relationship('caseEvent', 'title')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Nombre del Documento')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label('Archivo')
                    ->directory('documents')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
