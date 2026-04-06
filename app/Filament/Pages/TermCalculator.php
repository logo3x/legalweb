<?php

namespace App\Filament\Pages;

use App\Services\JudicialCalendarService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TermCalculator extends Page
{
    protected string $view = 'filament.pages.term-calculator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Calculadora';

    protected static ?string $title = 'Calculadora de Terminos Judiciales';

    protected static ?int $navigationSort = 7;

    public ?string $startDate = null;

    public ?int $term = null;

    public ?string $termType = 'business';

    public ?array $result = null;

    public function calculate(): void
    {
        if (! $this->startDate || ! $this->term) {
            Notification::make()->title('Complete todos los campos.')->warning()->send();

            return;
        }

        $service = app(JudicialCalendarService::class);
        $start = Carbon::parse($this->startDate);

        $this->result = $service->calculateDeadline($start, $this->term, $this->termType);
    }

    public function clear(): void
    {
        $this->startDate = null;
        $this->term = null;
        $this->termType = 'business';
        $this->result = null;
    }
}
