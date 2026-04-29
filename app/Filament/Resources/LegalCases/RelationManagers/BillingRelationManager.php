<?php

namespace App\Filament\Resources\LegalCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BillingRelationManager extends RelationManager
{
    protected static string $relationship = 'billingEntries';

    protected static ?string $title = 'Facturacion';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'hora' => 'Hora de trabajo',
                        'gasto' => 'Gasto / Expensa',
                        'concepto' => 'Concepto fijo',
                    ])
                    ->required()
                    ->default('hora')
                    ->live(),
                TextInput::make('description')
                    ->label('Descripcion')
                    ->required()
                    ->placeholder('Ej: Revision de expediente, Audiencia inicial...')
                    ->columnSpanFull(),
                DatePicker::make('entry_date')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                TextInput::make('hours')
                    ->label('Horas')
                    ->numeric()
                    ->step(0.25)
                    ->minValue(0.25)
                    ->placeholder('Ej: 1.5')
                    ->visible(fn ($get) => $get('type') === 'hora'),
                TextInput::make('rate_per_hour')
                    ->label('Tarifa por hora ($)')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('Ej: 150000')
                    ->visible(fn ($get) => $get('type') === 'hora'),
                TextInput::make('amount')
                    ->label('Monto ($)')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->placeholder('Ej: 250000')
                    ->helperText(fn ($get) => $get('type') === 'hora' ? 'Se calcula automaticamente si ingresa horas y tarifa' : null),
                Toggle::make('is_billable')
                    ->label('Facturable al cliente')
                    ->default(true),
                Select::make('user_id')
                    ->label('Abogado')
                    ->relationship('user', 'name', fn ($query) => $query->where('firm_id', auth()->user()->firm_id))
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Registre las horas trabajadas, gastos y conceptos de este caso. Use "Registrar" arriba a la derecha para anadir la primera entrada.')
            ->emptyStateHeading('Aun no ha registrado entradas de facturacion')
            ->emptyStateDescription('Use el boton "Registrar" para anotar horas trabajadas, gastos del proceso (notarias, copias, transporte) o conceptos a cobrar al cliente. Marque cada entrada como facturable o no facturable. Al final podra generar la cuenta de cobro con lo facturable.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->columns([
                TextColumn::make('entry_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hora' => 'info',
                        'gasto' => 'warning',
                        'concepto' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hora' => 'Hora',
                        'gasto' => 'Gasto',
                        'concepto' => 'Concepto',
                        default => $state,
                    }),
                TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
                TextColumn::make('hours')
                    ->label('Horas')
                    ->alignCenter()
                    ->placeholder('-')
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP', locale: 'es_CO')
                    ->sortable()
                    ->summarize(Sum::make()->money('COP', locale: 'es_CO')->label('Total')),
                IconColumn::make('is_billable')
                    ->label('Facturable')
                    ->boolean(),
                IconColumn::make('is_billed')
                    ->label('Cobrado')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Abogado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'hora' => 'Horas',
                        'gasto' => 'Gastos',
                        'concepto' => 'Conceptos',
                    ]),
                SelectFilter::make('is_billable')
                    ->label('Facturable')
                    ->options([
                        '1' => 'Si',
                        '0' => 'No',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar')
                    ->mutateFormDataUsing(function (array $data): array {
                        if ($data['type'] === 'hora' && ! empty($data['hours']) && ! empty($data['rate_per_hour'])) {
                            $data['amount'] = $data['hours'] * $data['rate_per_hour'];
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
