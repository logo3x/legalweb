<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\LegalCase;
use Filament\Forms\Components\DatePicker;
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
                TextInput::make('name')
                    ->label('Nombre del documento')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('responsible')
                    ->label('Quien debe conseguirlo')
                    ->options([
                        'cliente' => 'Cliente',
                        'abogado' => 'Abogado',
                        'firma' => 'Firma',
                        'contraparte' => 'Contraparte',
                        'juzgado' => 'Juzgado',
                        'otro' => 'Otro',
                    ])
                    ->default('cliente')
                    ->required(),
                TextInput::make('entity')
                    ->label('Entidad donde se consigue')
                    ->placeholder('Ej: Notaria, Registraduria'),
                TextInput::make('estimated_cost')
                    ->label('Valor aproximado ($)')
                    ->numeric()
                    ->prefix('$'),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'solicitado' => 'Solicitado',
                        'en_tramite' => 'En tramite',
                        'recibido' => 'Recibido',
                        'no_aplica' => 'No aplica',
                    ])
                    ->default('pendiente')
                    ->required(),
                Select::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ])
                    ->default('media'),
                DatePicker::make('due_date')
                    ->label('Fecha limite'),
                TextInput::make('external_url')
                    ->label('Enlace al archivo')
                    ->url()
                    ->placeholder('https://drive.google.com/...')
                    ->columnSpanFull()
                    ->helperText('Guarde el archivo en Drive, OneDrive o Dropbox y pegue el enlace aqui.'),
            ]);
    }
}
