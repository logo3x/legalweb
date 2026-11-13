<x-filament-panels::page>
    @php
        $data = $this->getAnalytics();
        $fmt = fn ($value) => $value === null ? '—' : number_format($value, 1, ',', '.').' d';
    @endphp

    @if (empty($data) || empty($data['courts']))
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
            Aun no hay casos con despacho asignado. Importe procesos desde la Rama Judicial o asigne el despacho en cada caso para ver estadisticas.
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
            @foreach ([
                ['label' => 'Despachos', 'value' => $data['totals']['courts'], 'color' => '#1E3A5F'],
                ['label' => 'Casos con despacho', 'value' => $data['totals']['cases'], 'color' => '#3A86FF'],
                ['label' => 'Actuaciones registradas', 'value' => $data['totals']['events'], 'color' => '#10b981'],
                ['label' => 'Casos sin movimiento (+'.\App\Services\CourtAnalyticsService::STALE_DAYS.' dias)', 'value' => $data['totals']['stale_cases'], 'color' => '#dc2626'],
            ] as $kpi)
                <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">{{ $kpi['label'] }}</div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $kpi['color'] }};">{{ $kpi['value'] }}</div>
                </div>
            @endforeach
        </div>

        @if ($data['slowest'] || $data['fastest'])
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
                @if ($data['slowest'])
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px;">
                        <div style="font-size: 12px; color: #b91c1c; font-weight: 600;">Despacho más lento</div>
                        <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;">{{ $data['slowest']['court'] }}</div>
                        <div style="font-size: 12px; color: #6b7280;">{{ $fmt($data['slowest']['avg_days_between_events']) }} en promedio entre actuaciones</div>
                    </div>
                @endif
                @if ($data['fastest'])
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px;">
                        <div style="font-size: 12px; color: #15803d; font-weight: 600;">Despacho más ágil</div>
                        <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;">{{ $data['fastest']['court'] }}</div>
                        <div style="font-size: 12px; color: #6b7280;">{{ $fmt($data['fastest']['avg_days_between_events']) }} en promedio entre actuaciones</div>
                    </div>
                @endif
            </div>
        @endif

        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f5f7fa; text-align: left; color: #1E3A5F;">
                        <th style="padding: 12px;">Despacho</th>
                        <th style="padding: 12px; text-align: center;">Casos</th>
                        <th style="padding: 12px; text-align: center;">Activos</th>
                        <th style="padding: 12px; text-align: center;">Actuaciones</th>
                        <th style="padding: 12px; text-align: center;" title="Promedio de dias entre una actuacion y la siguiente">Días entre actuaciones</th>
                        <th style="padding: 12px; text-align: center;" title="Promedio de dias desde la ultima actuacion en casos activos">Días sin movimiento</th>
                        <th style="padding: 12px; text-align: center;" title="Duracion promedio de los casos cerrados">Duración casos cerrados</th>
                        <th style="padding: 12px; text-align: center;">Estancados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['courts'] as $court)
                        <tr style="border-top: 1px solid #f3f4f6;">
                            <td style="padding: 12px; font-weight: 600; color: #1f2937;">{{ $court['court'] }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $court['total_cases'] }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $court['active_cases'] }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $court['total_events'] }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $fmt($court['avg_days_between_events']) }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $fmt($court['avg_days_since_last_event']) }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $fmt($court['avg_closed_duration_days']) }}</td>
                            <td style="padding: 12px; text-align: center; {{ $court['stale_cases'] > 0 ? 'color: #dc2626; font-weight: 700;' : 'color: #9ca3af;' }}">{{ $court['stale_cases'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p style="font-size: 12px; color: #9ca3af; margin-top: 12px;">
            Calculado con las actuaciones registradas en LegalWeb (sincronizadas desde la Rama Judicial o creadas manualmente). Es una referencia estadistica de su propia cartera, no un indicador oficial.
        </p>
    @endif
</x-filament-panels::page>
