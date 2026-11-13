@php
    $data = $this->getData();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $firstName = explode(' ', trim((string) auth()->user()->name))[0] ?? '';
    $total = count($data['agenda']) + count($data['deadlines']) + count($data['news']) + count($data['documents']);

    $columns = [
        ['key' => 'deadlines', 'title' => 'Vencimientos', 'subtitle' => 'Próximos 3 días', 'color' => '#DC2626', 'bg' => '#FEF2F2', 'empty' => 'Ningún término por vencer.', 'link' => url('/admin/vencimientos')],
        ['key' => 'agenda', 'title' => 'Agenda', 'subtitle' => 'Hoy y mañana', 'color' => '#2563EB', 'bg' => '#EFF6FF', 'empty' => 'Sin audiencias ni reuniones.', 'link' => url('/admin/reminders')],
        ['key' => 'news', 'title' => 'Novedades judiciales', 'subtitle' => 'Últimas 24 horas', 'color' => '#7C3AED', 'bg' => '#F5F3FF', 'empty' => 'Sin actuaciones nuevas.', 'link' => url('/admin/case-events')],
        ['key' => 'documents', 'title' => 'Documentos de clientes', 'subtitle' => 'Últimos 3 días', 'color' => '#059669', 'bg' => '#ECFDF5', 'empty' => 'Ningún documento nuevo.', 'link' => url('/admin/documents')],
    ];
@endphp

<x-filament-widgets::widget>
    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px; font-family: Inter, system-ui, sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
            <div>
                <div style="font-size: 18px; font-weight: 700; color: #1E3A5F;">{{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}</div>
                <div style="font-size: 13px; color: #6b7280;">
                    @if($total === 0)
                        Todo al día. No hay pendientes urgentes para hoy.
                    @else
                        Esto es lo que necesita su atención hoy, {{ now()->translatedFormat('l j \d\e F') }}.
                    @endif
                </div>
            </div>
            <span style="font-size: 12px; color: #94a3b8;">Presione <kbd style="padding: 1px 6px; border: 1px solid #e5e7eb; border-radius: 4px; background: #f8fafc;">Ctrl</kbd> + <kbd style="padding: 1px 6px; border: 1px solid #e5e7eb; border-radius: 4px; background: #f8fafc;">K</kbd> para buscar un caso o cliente</span>
        </div>

        <div class="lw-myday-grid" style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px;">
            @foreach($columns as $column)
                @php $items = $data[$column['key']]; @endphp
                <div style="border: 1px solid #eef2f7; border-radius: 10px; padding: 14px; background: {{ $column['bg'] }}; display: flex; flex-direction: column; min-height: 150px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: {{ $column['color'] }};">{{ $column['title'] }}</div>
                            <div style="font-size: 11px; color: #6b7280;">{{ $column['subtitle'] }}</div>
                        </div>
                        <span style="min-width: 26px; text-align: center; padding: 2px 8px; border-radius: 999px; font-size: 13px; font-weight: 700; color: #fff; background: {{ count($items) ? $column['color'] : '#cbd5e1' }};">{{ count($items) }}</span>
                    </div>

                    @forelse($items as $item)
                        <a href="{{ $item['url'] ?? $column['link'] }}" style="display: block; text-decoration: none; padding: 8px 10px; margin-bottom: 6px; background: #fff; border-radius: 8px; border: 1px solid #eef2f7;">
                            <div style="font-size: 13px; font-weight: 600; color: #111827; line-height: 1.3;">{{ \Illuminate\Support\Str::limit($item['title'], 70) }}</div>
                            <div style="font-size: 11.5px; color: #6b7280; margin-top: 2px;">
                                @if($column['key'] === 'deadlines')
                                    @if($item['days'] < 0)
                                        <strong style="color: #DC2626;">Venció hace {{ abs($item['days']) }} día(s)</strong>
                                    @elseif($item['days'] === 0)
                                        <strong style="color: #DC2626;">Vence hoy</strong>
                                    @else
                                        Vence en {{ $item['days'] }} día(s) · {{ $item['when']->format('d/m') }}
                                    @endif
                                @elseif($column['key'] === 'agenda')
                                    {{ $item['kind'] }} · {{ $item['when']->isToday() ? 'Hoy' : 'Mañana' }} {{ $item['when']->format('g:i a') }}
                                @elseif($column['key'] === 'documents')
                                    {{ $item['status'] }}
                                @else
                                    {{ $item['when']?->format('d/m/Y') }}
                                @endif
                                @if($item['case'])
                                    · {{ $item['case'] }}
                                @endif
                            </div>
                        </a>
                    @empty
                        <div style="font-size: 12.5px; color: #94a3b8; margin: auto 0; text-align: center;">{{ $column['empty'] }}</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>

    <style>
        @media (max-width: 1100px) { .lw-myday-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
        @media (max-width: 640px) { .lw-myday-grid { grid-template-columns: 1fr !important; } }
    </style>
</x-filament-widgets::widget>
