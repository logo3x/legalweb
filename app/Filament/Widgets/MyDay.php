<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CaseEvents\CaseEventResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\CaseEvent;
use App\Models\Document;
use App\Models\Reminder;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Mi dia": lo que el abogado debe atender hoy, en un solo vistazo.
 *
 * Respeta los permisos: FirmScope limita casos, recordatorios y clientes, y los
 * colaboradores solo ven actuaciones y documentos de casos donde tienen ese permiso.
 */
class MyDay extends Widget
{
    protected string $view = 'filament.widgets.my-day';

    protected static ?int $sort = -3;

    /**
     * Nombre visible de cada tipo de recordatorio.
     */
    private const KIND_LABELS = [
        'audiencia' => 'Audiencia',
        'reunion' => 'Reunión',
        'tarea' => 'Tarea',
        'recordatorio' => 'Recordatorio',
    ];

    protected int|string|array $columnSpan = 'full';

    /**
     * Se muestra apenas abre el escritorio, sin esperar a que cargue el resto.
     */
    protected static bool $isLazy = false;

    /**
     * @return array{agenda: list<array<string, mixed>>, deadlines: list<array<string, mixed>>, news: list<array<string, mixed>>, documents: list<array<string, mixed>>}
     */
    public function getData(): array
    {
        return [
            'agenda' => $this->agenda(),
            'deadlines' => $this->deadlines(),
            'news' => $this->judicialNews(),
            'documents' => $this->clientDocuments(),
        ];
    }

    /**
     * Audiencias, reuniones y tareas de hoy y manana.
     *
     * @return list<array<string, mixed>>
     */
    private function agenda(): array
    {
        $from = now()->startOfDay();
        $to = now()->addDay()->endOfDay();

        $reminders = Reminder::with('legalCase')
            ->where('is_completed', false)
            ->where('type', '!=', 'vencimiento')
            ->whereBetween('due_date', [$from, $to])
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'when' => $reminder->due_date,
                'title' => $reminder->title,
                'case' => $reminder->legalCase?->case_number,
                'url' => $reminder->legalCase ? LegalCaseResource::getUrl('view', ['record' => $reminder->legalCase]) : null,
                'kind' => self::KIND_LABELS[$reminder->type] ?? ucfirst((string) $reminder->type),
            ]);

        $hearings = $this->visibleEvents()
            ->where('event_type', 'audiencia')
            ->whereBetween('event_date', [$from, $to])
            ->orderBy('event_date')
            ->limit(5)
            ->get()
            ->map(fn (CaseEvent $event): array => [
                'when' => $event->event_date,
                'title' => $event->title,
                'case' => $event->legalCase?->case_number,
                'url' => $event->legalCase ? LegalCaseResource::getUrl('view', ['record' => $event->legalCase]) : null,
                'kind' => 'Audiencia',
            ]);

        return $reminders->concat($hearings)->sortBy('when')->take(5)->values()->all();
    }

    /**
     * Terminos vencidos o que vencen en los proximos 3 dias.
     *
     * @return list<array<string, mixed>>
     */
    private function deadlines(): array
    {
        return Reminder::with('legalCase')
            ->where('is_completed', false)
            ->where('type', 'vencimiento')
            ->where('due_date', '<=', now()->addDays(3)->endOfDay())
            ->where('due_date', '>=', now()->subDays(7)->startOfDay())
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'when' => $reminder->due_date,
                'title' => $reminder->title,
                'case' => $reminder->legalCase?->case_number,
                'url' => $reminder->legalCase ? LegalCaseResource::getUrl('view', ['record' => $reminder->legalCase]) : null,
                'days' => (int) now()->startOfDay()->diffInDays($reminder->due_date->copy()->startOfDay(), false),
            ])
            ->all();
    }

    /**
     * Actuaciones nuevas que trajo la Rama Judicial o SAMAI en las ultimas 24 horas.
     *
     * @return list<array<string, mixed>>
     */
    private function judicialNews(): array
    {
        return $this->visibleEvents()
            ->where('event_type', 'actuacion')
            ->where('created_at', '>=', now()->subDay())
            ->latest('event_date')
            ->limit(5)
            ->get()
            ->map(fn (CaseEvent $event): array => [
                'when' => $event->event_date,
                'title' => $event->title,
                'case' => $event->legalCase?->case_number,
                'url' => $event->legalCase ? LegalCaseResource::getUrl('view', ['record' => $event->legalCase]) : null,
            ])
            ->all();
    }

    /**
     * Documentos que los clientes enviaron o confirmaron desde el portal (ultimos 3 dias).
     *
     * @return list<array<string, mixed>>
     */
    private function clientDocuments(): array
    {
        $allowed = DocumentResource::caseIdsWithPermission('documents.view');

        if ($allowed === []) {
            return [];
        }

        return Document::with('legalCase')
            ->whereHas('legalCase')
            ->when($allowed !== null, fn (Builder $query) => $query->whereIn('legal_case_id', $allowed))
            ->where('responsible', 'cliente')
            ->whereIn('status', ['en_tramite', 'recibido'])
            ->where('updated_at', '>=', now()->subDays(3))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Document $document): array => [
                'when' => $document->updated_at,
                'title' => $document->name,
                'case' => $document->legalCase?->case_number,
                'url' => $document->legalCase ? LegalCaseResource::getUrl('view', ['record' => $document->legalCase]) : null,
                'status' => $document->status === 'recibido' ? 'Envió el enlace' : 'Dice que lo tiene listo',
            ])
            ->all();
    }

    /**
     * Actuaciones de casos visibles para el usuario, con el permiso events.view.
     *
     * @return Builder<CaseEvent>
     */
    private function visibleEvents(): Builder
    {
        $allowed = CaseEventResource::caseIdsWithPermission('events.view');

        return CaseEvent::with('legalCase')
            ->whereHas('legalCase')
            ->when($allowed !== null, fn (Builder $query) => $query->whereIn('legal_case_id', $allowed ?: [0]));
    }
}
