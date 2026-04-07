<?php

namespace App\Filament\Resources\CaseEvents;

use App\Filament\Resources\CaseEvents\Pages\CreateCaseEvent;
use App\Filament\Resources\CaseEvents\Pages\EditCaseEvent;
use App\Filament\Resources\CaseEvents\Pages\ListCaseEvents;
use App\Filament\Resources\CaseEvents\Schemas\CaseEventForm;
use App\Filament\Resources\CaseEvents\Tables\CaseEventsTable;
use App\Models\CaseEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CaseEventResource extends Resource
{
    protected static ?string $model = CaseEvent::class;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->whereHas('legalCase', fn (Builder $q) => $q->where('firm_id', auth()->user()->firm_id));

        return $query;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Actuación';

    protected static ?string $pluralModelLabel = 'Actuaciones';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CaseEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaseEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseEvents::route('/'),
            'create' => CreateCaseEvent::route('/create'),
            'edit' => EditCaseEvent::route('/{record}/edit'),
        ];
    }
}
