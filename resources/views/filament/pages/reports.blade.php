<x-filament-panels::page>
    @php $data = $this->getReportData(); @endphp

    @if(empty($data))
        <div style="text-align: center; padding: 40px; color: #9ca3af;">No hay datos para mostrar.</div>
    @else
        {{-- KPIs principales --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
            @foreach([
                ['label' => 'Total Casos', 'value' => $data['total_cases'], 'color' => '#3A86FF'],
                ['label' => 'Clientes', 'value' => $data['total_clients'], 'color' => '#10b981'],
                ['label' => 'Casos Cerrados', 'value' => $data['closed_cases'], 'color' => '#8b5cf6'],
                ['label' => 'Promedio dias/caso', 'value' => $data['avg_days_per_case'] . ' dias', 'color' => '#f59e0b'],
            ] as $kpi)
                <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">{{ $kpi['label'] }}</div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $kpi['color'] }};">{{ $kpi['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;">
            {{-- Casos por Estado --}}
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin-bottom: 16px;">Casos por Estado</h3>
                @php $statusColors = ['abierto' => '#3b82f6', 'en_progreso' => '#f59e0b', 'en_espera' => '#6b7280', 'cerrado' => '#10b981', 'archivado' => '#ef4444']; @endphp
                @foreach($data['cases_by_status'] as $status => $count)
                    @php $percent = $data['total_cases'] > 0 ? round(($count / $data['total_cases']) * 100) : 0; @endphp
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #374151;">{{ $data['status_labels'][$status] ?? $status }}</span>
                            <span style="font-weight: 600;">{{ $count }} ({{ $percent }}%)</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: #f3f4f6; border-radius: 999px;">
                            <div style="width: {{ $percent }}%; height: 100%; background: {{ $statusColors[$status] ?? '#6b7280' }}; border-radius: 999px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Casos por Tipo --}}
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin-bottom: 16px;">Casos por Tipo de Proceso</h3>
                @foreach($data['cases_by_type'] as $type => $count)
                    @php $percent = $data['total_cases'] > 0 ? round(($count / $data['total_cases']) * 100) : 0; @endphp
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #374151;">{{ $type }}</span>
                            <span style="font-weight: 600;">{{ $count }}</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: #f3f4f6; border-radius: 999px;">
                            <div style="width: {{ $percent }}%; height: 100%; background: #3A86FF; border-radius: 999px;"></div>
                        </div>
                    </div>
                @endforeach
                @if(empty($data['cases_by_type']))
                    <div style="color: #9ca3af; font-size: 13px;">Sin datos</div>
                @endif
            </div>

            {{-- Casos por Prioridad --}}
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin-bottom: 16px;">Casos por Prioridad</h3>
                @php $priorityColors = ['baja' => '#6b7280', 'media' => '#3b82f6', 'alta' => '#f59e0b', 'urgente' => '#ef4444']; @endphp
                @foreach($data['cases_by_priority'] as $priority => $count)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9fafb;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 10px; height: 10px; border-radius: 999px; background: {{ $priorityColors[$priority] ?? '#6b7280' }};"></div>
                            <span style="font-size: 13px; color: #374151;">{{ $data['priority_labels'][$priority] ?? $priority }}</span>
                        </div>
                        <span style="font-size: 14px; font-weight: 600; color: #111827;">{{ $count }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Casos por Abogado --}}
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin-bottom: 16px;">Productividad por Abogado</h3>
                @foreach($data['cases_by_lawyer'] as $lawyer => $count)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9fafb;">
                        <span style="font-size: 13px; color: #374151;">{{ $lawyer }}</span>
                        <span style="font-size: 14px; font-weight: 600; color: #111827;">{{ $count }} casos</span>
                    </div>
                @endforeach
                @if(empty($data['cases_by_lawyer']))
                    <div style="color: #9ca3af; font-size: 13px;">Sin datos</div>
                @endif
            </div>
        </div>

        {{-- Resumen de actividad --}}
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: #3A86FF;">{{ $data['recent_events'] }}</div>
                <div style="font-size: 13px; color: #6b7280;">Actuaciones (30 dias)</div>
            </div>
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: #10b981;">{{ $data['completed_steps'] }}</div>
                <div style="font-size: 13px; color: #6b7280;">Pasos Completados</div>
            </div>
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; text-align: center;">
                <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">{{ $data['pending_steps'] }}</div>
                <div style="font-size: 13px; color: #6b7280;">Pasos Pendientes</div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
