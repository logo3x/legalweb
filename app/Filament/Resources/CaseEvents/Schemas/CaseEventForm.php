<?php

namespace App\Filament\Resources\CaseEvents\Schemas;

use App\Models\LegalCase;
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
                    ->required()
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
