@php
    $kindLabels = ['termino' => 'Término de etapa', 'vencimiento' => 'Recordatorio'];
    $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
@endphp

<style>
    .lw-dl { --lw-ink: #0f172a; --lw-muted: #475569; --lw-line: #e2e8f0; --lw-surface: #ffffff; --lw-danger: #b91c1c; --lw-warn: #92400e; --lw-link: #1d4ed8; }
    .dark .lw-dl { --lw-ink: #f1f5f9; --lw-muted: #cbd5e1; --lw-line: rgba(255,255,255,.1); --lw-surface: rgba(255,255,255,.04); --lw-danger: #fca5a5; --lw-warn: #fcd34d; --lw-link: #93c5fd; }
    .lw-dl { display: flex; flex-direction: column; gap: 28px; color: var(--lw-ink); }
    .lw-dl-notice { display: flex; gap: 12px; align-items: flex-start; padding: 12px 16px; border: 1px solid var(--lw-line); border-radius: 10px; background: var(--lw-surface); font-size: 14px; line-height: 1.5; }
    .lw-dl-notice svg { flex: none; width: 20px; height: 20px; color: #d97706; margin-top: 1px; }
    .lw-dl-notice a, .lw-dl-row a { color: var(--lw-link); font-weight: 500; text-decoration: none; }
    .lw-dl-notice a:hover, .lw-dl-row a:hover { text-decoration: underline; }
    .lw-dl-group h3 { display: flex; align-items: baseline; gap: 8px; margin: 0 0 10px; font-size: 15px; font-weight: 600; }
    .lw-dl-group h3 span { font-size: 13px; font-weight: 500; color: var(--lw-muted); }
    .lw-dl-group.is-urgent h3 { color: var(--lw-danger); }
    .lw-dl-list { border: 1px solid var(--lw-line); border-radius: 12px; background: var(--lw-surface); overflow: hidden; }
    .lw-dl-row { display: grid; grid-template-columns: 52px minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 14px 18px; }
    .lw-dl-row + .lw-dl-row { border-top: 1px solid var(--lw-line); }
    .lw-dl-date { text-align: center; line-height: 1.1; }
    .lw-dl-date strong { display: block; font-size: 20px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .lw-dl-date small { font-size: 12px; color: var(--lw-muted); text-transform: uppercase; letter-spacing: .02em; }
    .lw-dl-title { font-size: 14px; font-weight: 600; }
    .lw-dl-meta { margin-top: 2px; font-size: 13px; color: var(--lw-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lw-dl-when { font-size: 13px; font-weight: 500; color: var(--lw-muted); text-align: right; white-space: nowrap; }
    .lw-dl-when.is-urgent { color: var(--lw-danger); font-weight: 600; }
    .lw-dl-when.is-soon { color: var(--lw-warn); }
    .lw-dl-empty { padding: 28px 24px; border: 1px dashed var(--lw-line); border-radius: 12px; font-size: 14px; line-height: 1.6; color: var(--lw-muted); }
    .lw-dl-empty strong { display: block; margin-bottom: 4px; font-size: 15px; color: var(--lw-ink); }
    .lw-dl details { font-size: 13px; color: var(--lw-muted); line-height: 1.6; }
    .lw-dl summary { cursor: pointer; font-weight: 500; color: var(--lw-ink); }
    @media (max-width: 640px) {
        .lw-dl-row { grid-template-columns: 44px minmax(0, 1fr); padding: 12px 14px; }
        .lw-dl-when { grid-column: 2; text-align: left; }
        .lw-dl-meta { white-space: normal; }
    }
</style>

<div class="lw-dl">
    @if($data['unmonitored'] > 0)
        <div class="lw-dl-notice" role="status">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <div>
                <strong>{{ $data['unmonitored'] }} {{ $data['unmonitored'] === 1 ? 'proceso no se ha podido consultar' : 'procesos no se han podido consultar' }} recientemente.</strong>
                Sus actuaciones y términos pueden no estar al día: revíselos directamente en la Rama Judicial o SAMAI.
                <a href="{{ $data['unmonitoredUrl'] }}">Ver casos con error de consulta</a>
            </div>
        </div>
    @endif

    @if($data['total'] === 0)
        <div class="lw-dl-empty">
            <strong>No hay términos corriendo en los próximos 30 días.</strong>
            Los términos aparecen aquí cuando inicia una etapa del proceso (pestaña «Etapas y términos» de cada caso) o cuando crea un recordatorio de tipo «Vencimiento» en la Agenda.
        </div>
    @endif

    @foreach($data['groups'] as $group)
        @continue(count($group['items']) === 0)
        <section class="lw-dl-group {{ in_array($group['key'], ['overdue', 'today'], true) ? 'is-urgent' : '' }}" aria-label="{{ $group['title'] }}">
            <h3>{{ $group['title'] }} <span>{{ count($group['items']) }}</span></h3>
            <div class="lw-dl-list">
                @foreach($group['items'] as $item)
                    @php
                        $remaining = $item['remaining'];
                        $when = match (true) {
                            $remaining < -1 => 'Venció hace '.abs($remaining).' días hábiles',
                            $remaining === -1 => 'Venció ayer',
                            $remaining === 0 => 'Vence hoy',
                            $remaining === 1 => 'En 1 día hábil',
                            default => "En {$remaining} días hábiles",
                        };
                        $whenClass = $remaining <= 0 ? 'is-urgent' : ($remaining <= 3 ? 'is-soon' : '');
                    @endphp
                    <div class="lw-dl-row">
                        <div class="lw-dl-date" aria-hidden="true">
                            <strong>{{ $item['date']->format('j') }}</strong>
                            <small>{{ $months[$item['date']->month - 1] }}</small>
                        </div>
                        <div style="min-width: 0;">
                            <div class="lw-dl-title">{{ $item['title'] }}</div>
                            <div class="lw-dl-meta">
                                {{ $kindLabels[$item['kind']] ?? '' }} · {{ $item['date']->format('d/m/Y') }}
                                @if($item['case_url'])
                                    · <a href="{{ $item['case_url'] }}" wire:navigate>{{ $item['case_number'] }}</a>
                                @endif
                                @if($item['detail'])
                                    · {{ \Illuminate\Support\Str::limit($item['detail'], 90) }}
                                @endif
                            </div>
                        </div>
                        <div class="lw-dl-when {{ $whenClass }}">{{ $when }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <details>
        <summary>Cómo se calculan los vencimientos</summary>
        <p style="margin: 8px 0 0;">
            El plazo de cada etapa corre en días hábiles desde que usted la inicia, según el calendario judicial colombiano: sin fines de semana, festivos, Semana Santa ni la vacancia de fin de año.
            Le avisamos por correo 3 días hábiles antes, 1 día antes y el día del vencimiento. Para un cálculo puntual use la Calculadora de términos.
        </p>
    </details>
</div>
