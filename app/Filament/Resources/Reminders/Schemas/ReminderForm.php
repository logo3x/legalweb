<?php

namespace App\Filament\Resources\Reminders\Schemas;

use App\Models\LegalCase;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReminderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titulo')
                    ->required()
                    ->placeholder('Ej: Preparar memorial para audiencia'),
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'audiencia' => 'Audiencia',
                        'vencimiento' => 'Vencimiento de termino',
                        'reunion' => 'Reunion',
                        'tarea' => 'Tarea',
                        'recordatorio' => 'Recordatorio general',
                    ])
                    ->required()
                    ->default('recordatorio'),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ])
                    ->required()
                    ->default('media'),
                DateTimePicker::make('due_date')
                    ->label('Fecha limite')
                    ->required(),
                DateTimePicker::make('remind_at')
                    ->label('Recordar el')
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Fecha en que recibira una alerta por email. Dejelo vacio para no recibir alerta.'),
                Select::make('legal_case_id')
                    ->label('Caso relacionado (opcional)')
                    ->placeholder('Sin caso asociado')
                    ->searchable()
                    ->preload()
                    ->options(fn () => LegalCase::where('firm_id', auth()->user()->firm_id)
                        ->orderByDesc('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => $c->case_number.' - '.str($c->title)->limit(60)])
                        ->toArray())
                    ->getSearchResultsUsing(fn (string $search) => LegalCase::where('firm_id', auth()->user()->firm_id)
                        ->where(fn ($q) => $q->where('case_number', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => $c->case_number.' - '.str($c->title)->limit(60)])
                        ->toArray())
                    ->getOptionLabelUsing(function ($value) {
                        $case = LegalCase::find($value);

                        return $case ? $case->case_number.' - '.str($case->title)->limit(60) : null;
                    }),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->placeholder('Detalles adicionales del recordatorio')
                    ->columnSpanFull(),
            ]);
    }
}
