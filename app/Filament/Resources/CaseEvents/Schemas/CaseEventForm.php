<?php

namespace App\Filament\Resources\CaseEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CaseEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('legal_case_id')
                    ->label('Caso')
                    ->relationship('legalCase', 'case_number', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->case_number.' - '.str($record->title)->limit(60))
                    ->required()
                    ->searchable(['case_number', 'title'])
                    ->preload(),
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                DateTimePicker::make('event_date')
                    ->label('Fecha del Evento')
                    ->required(),
                Select::make('event_type')
                    ->label('Tipo de Evento')
                    ->options([
                        'actuacion' => 'Actuación',
                        'audiencia' => 'Audiencia',
                        'notificacion' => 'Notificación',
                        'memorial' => 'Memorial',
                        'auto' => 'Auto',
                        'sentencia' => 'Sentencia',
                    ])
                    ->required()
                    ->default('actuacion'),
                Toggle::make('is_milestone')
                    ->label('Hito importante'),
                Select::make('user_id')
                    ->label('Registrado por')
                    ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->searchable()
                    ->preload(),
            ]);
    }
}
