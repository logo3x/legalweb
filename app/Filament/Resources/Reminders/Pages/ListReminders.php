<?php

namespace App\Filament\Resources\Reminders\Pages;

use App\Filament\Resources\Reminders\ReminderResource;
use App\Models\CaseEvent;
use App\Models\LegalCase;
use App\Models\Reminder;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListReminders extends ListRecords
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Recordatorio'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('agenda')
                    ->tabs([
                        Tab::make('Recordatorios')
                            ->icon('heroicon-o-bell')
                            ->badge(fn () => Reminder::where('user_id', auth()->id())
                                ->where('is_completed', false)
                                ->where('due_date', '<=', now()->addDays(3))
                                ->count() ?: null)
                            ->badgeColor('danger')
                            ->schema([
                                Section::make('Acerca de los recordatorios')
                                    ->schema([
                                        Text::make('Organice su practica legal con recordatorios de audiencias, vencimientos de terminos, reuniones y tareas. Los recordatorios se crean automaticamente al sincronizar con la Rama Judicial, o puede crearlos manualmente. Recibira alertas por email antes de cada evento.')
                                            ->color('neutral'),
                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->compact(),
                                EmbeddedTable::make(),
                            ]),
                        Tab::make('Vencimientos')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                View::make('filament.partials.deadlines-content')
                                    ->viewData(['data' => $this->getDeadlinesData()]),
                            ]),
                    ])
                    ->persistTabInQueryString('tab'),
            ]);
    }

    public function getDeadlinesData(): array
    {
        $userId = auth()->id();
        $firmId = auth()->user()->firm_id;

        $reminders = Reminder::with('legalCase')
            ->where('user_id', $userId)
            ->where('is_completed', false)
            ->where('due_date', '>=', now()->subDays(7))
            ->where('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->get()
            ->map(function ($r) {
                $days = (int) now()->startOfDay()->diffInDays($r->due_date->startOfDay(), false);

                return [
                    'id' => $r->id,
                    'title' => $r->title,
                    'description' => $r->description,
                    'due_date' => $r->due_date->format('d/m/Y'),
                    'due_date_raw' => $r->due_date->format('Y-m-d'),
                    'days' => $days,
                    'type' => $r->type,
                    'priority' => $r->priority,
                    'case_number' => $r->legalCase?->case_number,
                    'case_id' => $r->legal_case_id,
                    'status' => match (true) {
                        $days < 0 => 'vencido',
                        $days === 0 => 'hoy',
                        $days <= 3 => 'urgente',
                        $days <= 7 => 'proximo',
                        default => 'normal',
                    },
                ];
            });

        $recentActuaciones = CaseEvent::with('legalCase')
            ->whereHas('legalCase', fn ($q) => $q->where('firm_id', $firmId))
            ->where('event_type', 'actuacion')
            ->where('event_date', '>=', now()->subDays(15))
            ->orderByDesc('event_date')
            ->limit(15)
            ->get()
            ->map(fn ($e) => [
                'title' => $e->title,
                'date' => $e->event_date->format('d/m/Y'),
                'case_number' => $e->legalCase?->case_number,
                'case_id' => $e->legalCase?->id,
            ]);

        $casesStale = LegalCase::where('firm_id', $firmId)
            ->whereNotNull('external_case_number')
            ->where('external_case_number', '!=', '')
            ->whereIn('status', ['abierto', 'en_progreso', 'en_espera'])
            ->where(function ($q) {
                $q->whereNull('last_tyba_sync')
                    ->orWhere('last_tyba_sync', '<', now()->subHours(48));
            })
            ->count();

        $vencidos = $reminders->where('status', 'vencido')->count();
        $hoy = $reminders->where('status', 'hoy')->count();
        $urgentes = $reminders->where('status', 'urgente')->count();
        $proximos = $reminders->where('status', 'proximo')->count();

        return [
            'reminders' => $reminders,
            'recentActuaciones' => $recentActuaciones,
            'casesStale' => $casesStale,
            'summary' => [
                'vencidos' => $vencidos,
                'hoy' => $hoy,
                'urgentes' => $urgentes,
                'proximos' => $proximos,
                'total' => $reminders->count(),
            ],
        ];
    }
}
