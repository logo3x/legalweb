<x-filament-panels::page>
    @php $data = $this->getDeadlinesData(); @endphp

    {{-- Descripcion --}}
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <div style="flex-shrink: 0; margin-top: 2px;">
                <svg style="width: 20px; height: 20px; color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
            </div>
            <div style="font-size: 13px; color: #1e40af; line-height: 1.5;">
                Este panel muestra los plazos y vencimientos de sus procesos judiciales. Las alertas se generan automaticamente cuando la Rama Judicial registra actuaciones como autos, traslados, audiencias o sentencias. Los plazos se calculan en dias habiles segun el calendario judicial colombiano (excluyendo fines de semana, festivos y vacancia judicial).
            </div>
        </div>
    </div>

    {{-- Resumen --}}
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px;">
        @foreach([
            ['label' => 'Vencidos', 'value' => $data['summary']['vencidos'], 'bg' => '#fef2f2', 'border' => '#fecaca', 'color' => '#dc2626'],
            ['label' => 'Vencen hoy', 'value' => $data['summary']['hoy'], 'bg' => '#fff7ed', 'border' => '#fed7aa', 'color' => '#ea580c'],
            ['label' => '1-3 dias', 'value' => $data['summary']['urgentes'], 'bg' => '#fefce8', 'border' => '#fde68a', 'color' => '#ca8a04'],
            ['label' => '4-7 dias', 'value' => $data['summary']['proximos'], 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#2563eb'],
            ['label' => 'Total pendientes', 'value' => $data['summary']['total'], 'bg' => '#f9fafb', 'border' => '#e5e7eb', 'color' => '#4b5563'],
        ] as $stat)
            <div style="background: {{ $stat['bg'] }}; border: 1px solid {{ $stat['border'] }}; border-radius: 12px; padding: 16px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: {{ $stat['color'] }};">{{ $stat['value'] }}</div>
                <div style="font-size: 12px; color: {{ $stat['color'] }}; opacity: 0.8;">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        {{-- Timeline de vencimientos --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6;">
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">Timeline de Vencimientos</h3>
                <p style="font-size: 12px; color: #9ca3af; margin: 4px 0 0 0;">Proximos 30 dias - alertas desde la Rama Judicial</p>
            </div>

            @forelse ($data['reminders'] as $reminder)
                @php
                    $borderColor = match($reminder['status']) {
                        'vencido' => '#ef4444',
                        'hoy' => '#f97316',
                        'urgente' => '#eab308',
                        'proximo' => '#3b82f6',
                        default => '#e5e7eb',
                    };
                    $bgColor = match($reminder['status']) {
                        'vencido' => '#fef2f2',
                        'hoy' => '#fff7ed',
                        default => '#ffffff',
                    };
                @endphp
                <div style="padding: 14px 20px; border-bottom: 1px solid #f3f4f6; border-left: 4px solid {{ $borderColor }}; background: {{ $bgColor }}; display: flex; align-items: flex-start; gap: 16px;">
                    {{-- Contenido --}}
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span style="font-size: 14px; font-weight: 600; color: #111827;">{{ $reminder['title'] }}</span>
                            @if($reminder['type'] === 'audiencia')
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #fef3c7; color: #92400e;">Audiencia</span>
                            @elseif($reminder['type'] === 'vencimiento')
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #fee2e2; color: #991b1b;">Vencimiento</span>
                            @endif
                            @if($reminder['priority'] === 'urgente')
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #fee2e2; color: #991b1b;">Urgente</span>
                            @elseif($reminder['priority'] === 'alta')
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #ffedd5; color: #9a3412;">Alta</span>
                            @endif
                        </div>
                        @if($reminder['description'])
                            <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit($reminder['description'], 120) }}</p>
                        @endif
                        @if($reminder['case_number'])
                            <a href="{{ route('filament.admin.resources.legal-cases.view', $reminder['case_id']) }}" style="font-size: 12px; color: #3A86FF; text-decoration: none; margin-top: 4px; display: inline-block;">
                                {{ $reminder['case_number'] }}
                            </a>
                        @endif
                    </div>

                    {{-- Countdown --}}
                    <div style="flex-shrink: 0; text-align: right; min-width: 90px;">
                        @php
                            $countdownColor = match($reminder['status']) {
                                'vencido' => '#dc2626',
                                'hoy' => '#ea580c',
                                'urgente' => '#ca8a04',
                                default => '#6b7280',
                            };
                            $countdownWeight = in_array($reminder['status'], ['vencido', 'hoy']) ? '700' : '500';
                        @endphp
                        <div style="font-size: 14px; font-weight: {{ $countdownWeight }}; color: {{ $countdownColor }}; font-family: monospace;">
                            @if($reminder['days'] < 0)
                                {{ abs($reminder['days']) }}d vencido
                            @elseif($reminder['days'] === 0)
                                HOY
                            @elseif($reminder['days'] === 1)
                                MANANA
                            @else
                                {{ $reminder['days'] }} dias
                            @endif
                        </div>
                        <div style="font-size: 11px; color: #9ca3af;">{{ $reminder['due_date'] }}</div>
                    </div>
                </div>
            @empty
                <div style="padding: 40px; text-align: center; color: #9ca3af;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px; color: #86efac;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p style="font-weight: 500; margin: 0;">Sin vencimientos pendientes</p>
                    <p style="font-size: 13px; margin: 4px 0 0 0;">Las alertas se generan automaticamente al sincronizar con la Rama Judicial.</p>
                </div>
            @endforelse
        </div>

        {{-- Panel lateral --}}
        <div>
            {{-- Actuaciones recientes --}}
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; margin-bottom: 16px;">
                <div style="padding: 14px 16px; border-bottom: 1px solid #f3f4f6;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Actuaciones Recientes</h3>
                    <p style="font-size: 11px; color: #9ca3af; margin: 2px 0 0 0;">Ultimos 15 dias desde Rama Judicial</p>
                </div>
                <div style="max-height: 320px; overflow-y: auto;">
                    @forelse ($data['recentActuaciones'] as $act)
                        <div style="padding: 10px 16px; border-bottom: 1px solid #f9fafb;">
                            <div style="font-size: 13px; font-weight: 500; color: #374151;">{{ $act['title'] }}</div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                <span style="font-size: 11px; color: #9ca3af;">{{ $act['date'] }}</span>
                                @if($act['case_id'])
                                    <a href="{{ route('filament.admin.resources.legal-cases.view', $act['case_id']) }}" style="font-size: 11px; color: #3A86FF; text-decoration: none;">{{ $act['case_number'] }}</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align: center; font-size: 13px; color: #9ca3af;">Sin actuaciones recientes</div>
                    @endforelse
                </div>
            </div>

            {{-- Casos sin sincronizar --}}
            @if($data['casesStale'] > 0)
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 18px; height: 18px; color: #d97706; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        <span style="font-size: 13px; font-weight: 600; color: #92400e;">{{ $data['casesStale'] }} caso(s) sin sincronizar</span>
                    </div>
                    <p style="font-size: 11px; color: #a16207; margin: 6px 0 0 0;">
                        No se han consultado en la Rama Judicial en mas de 48 horas. La sincronizacion automatica se ejecuta diariamente a las 3:00 AM.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
