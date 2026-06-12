<x-filament-panels::page>

    @if(! $this->isReady())
        <div style="padding: 24px; background: #FEF3C7; border-radius: 12px; border-left: 4px solid #D97706; color: #78350F; font-size: 14px; line-height: 1.6;">
            <strong>Modulo aun no instalado.</strong> La tabla <code>ai_usage_logs</code> no existe en este servidor.
            Ejecute <code>php artisan migrate --force</code> para activar el seguimiento de tokens.
        </div>
    @endif

    @php
        $kpis = $this->getKpis();
        $byFirm = $this->getByFirm();
        $byAction = $this->getByAction();
        $byProvider = $this->getByProvider();
        $daily = $this->getDailySeries();

        $maxDailyTokens = max(array_column($daily, 'tokens')) ?: 1;
        $maxFirmTokens = $byFirm ? max(array_column($byFirm, 'tokens')) : 1;

        $rangeOptions = [
            '7d' => 'Ultimos 7 dias',
            '30d' => 'Ultimos 30 dias',
            '90d' => 'Ultimos 90 dias',
            'all' => 'Todo el tiempo',
        ];
    @endphp

    {{-- Selector de rango --}}
    <div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
        @foreach($rangeOptions as $key => $label)
            <button wire:click="setRange('{{ $key }}')"
                style="padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid {{ $rangeKey === $key ? '#3A86FF' : '#e5e7eb' }}; background: {{ $rangeKey === $key ? '#3A86FF' : '#ffffff' }}; color: {{ $rangeKey === $key ? '#ffffff' : '#374151' }};">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- KPIs --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 28px;">
        <x-uso-ia-kpi label="Llamadas IA" :value="number_format($kpis['total_calls'])" color="#3A86FF" />
        <x-uso-ia-kpi label="Tokens totales" :value="number_format($kpis['total_tokens'])" color="#10b981" />
        <x-uso-ia-kpi label="Firmas activas" :value="number_format($kpis['active_firms'])" color="#8b5cf6" />
        <x-uso-ia-kpi label="Fallos" :value="number_format($kpis['failed_calls'])" :color="$kpis['failed_calls'] > 0 ? '#ef4444' : '#94a3b8'" />
        <x-uso-ia-kpi label="Latencia promedio" :value="number_format($kpis['avg_latency_ms']).' ms'" color="#f59e0b" />
    </div>

    {{-- Tokens por firma (barra horizontal) --}}
    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 16px; font-family: 'Poppins',sans-serif;">Tokens por firma</h3>

        @if(empty($byFirm))
            <p style="color: #94a3b8; font-size: 14px; text-align: center; padding: 32px 0;">
                A&uacute;n no hay llamadas registradas en este rango.
            </p>
        @else
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f9fafb;">
                        <th style="text-align: left; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Firma</th>
                        <th style="text-align: right; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Llamadas</th>
                        <th style="text-align: right; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Prompt</th>
                        <th style="text-align: right; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Respuesta</th>
                        <th style="text-align: right; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Total tokens</th>
                        <th style="padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">% del top</th>
                        <th style="text-align: center; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Fallos</th>
                        <th style="text-align: right; padding: 10px 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">&Uacute;ltimo uso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byFirm as $row)
                        @php $pct = $maxFirmTokens > 0 ? round(($row['tokens'] / $maxFirmTokens) * 100) : 0; @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 10px 12px; color: #111827; font-weight: 500;">{{ $row['firm_name'] }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #374151;">{{ number_format($row['calls']) }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #6b7280;">{{ number_format($row['prompt_tokens']) }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #6b7280;">{{ number_format($row['completion_tokens']) }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #1E3A5F; font-weight: 700;">{{ number_format($row['tokens']) }}</td>
                            <td style="padding: 10px 12px; width: 30%;">
                                <div style="background: #f1f5f9; border-radius: 999px; height: 8px; overflow: hidden;">
                                    <div style="background: #3A86FF; height: 100%; width: {{ max(2, $pct) }}%;"></div>
                                </div>
                            </td>
                            <td style="padding: 10px 12px; text-align: center; color: {{ $row['fails'] > 0 ? '#ef4444' : '#94a3b8' }}; font-weight: {{ $row['fails'] > 0 ? '700' : '400' }};">{{ $row['fails'] }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #94a3b8; font-size: 12px;">
                                {{ $row['last_used_at']?->diffForHumans() ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Por accion y por proveedor --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;" class="uso-ia-2col">
        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 16px; font-family: 'Poppins',sans-serif;">Por funci&oacute;n</h3>
            @if(empty($byAction))
                <p style="color: #94a3b8; font-size: 14px;">Sin datos</p>
            @else
                @php $maxA = max(array_column($byAction, 'tokens')) ?: 1; @endphp
                @foreach($byAction as $a)
                    <div style="margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #374151; font-weight: 500;">{{ $a['label'] }}</span>
                            <span style="color: #6b7280;">{{ number_format($a['calls']) }} llamadas &middot; <strong style="color: #1E3A5F;">{{ number_format($a['tokens']) }}</strong> tokens</span>
                        </div>
                        <div style="background: #f1f5f9; border-radius: 999px; height: 8px; overflow: hidden;">
                            <div style="background: #8b5cf6; height: 100%; width: {{ max(2, round(($a['tokens'] / $maxA) * 100)) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 16px; font-family: 'Poppins',sans-serif;">Por proveedor</h3>
            @if(empty($byProvider))
                <p style="color: #94a3b8; font-size: 14px;">Sin datos</p>
            @else
                @php $maxP = max(array_column($byProvider, 'tokens')) ?: 1; @endphp
                @foreach($byProvider as $p)
                    <div style="margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #374151; font-weight: 500;">{{ $p['provider'] }}</span>
                            <span style="color: #6b7280;">{{ number_format($p['calls']) }} llamadas &middot; <strong style="color: #1E3A5F;">{{ number_format($p['tokens']) }}</strong> tokens</span>
                        </div>
                        <div style="background: #f1f5f9; border-radius: 999px; height: 8px; overflow: hidden;">
                            <div style="background: #10b981; height: 100%; width: {{ max(2, round(($p['tokens'] / $maxP) * 100)) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Tendencia diaria --}}
    @if(! empty($daily))
        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 16px; font-family: 'Poppins',sans-serif;">Tendencia diaria de tokens</h3>
            <div style="display: flex; align-items: flex-end; gap: 4px; height: 160px;">
                @foreach($daily as $d)
                    @php $h = max(2, round(($d['tokens'] / $maxDailyTokens) * 150)); @endphp
                    <div style="flex: 1; position: relative;" title="{{ $d['day'] }}: {{ number_format($d['tokens']) }} tokens en {{ $d['calls'] }} llamadas">
                        <div style="background: linear-gradient(180deg, #3A86FF, #1E3A5F); border-radius: 4px 4px 0 0; height: {{ $h }}px; transition: opacity .2s;" onmouseover="this.style.opacity=0.75" onmouseout="this.style.opacity=1"></div>
                    </div>
                @endforeach
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; margin-top: 6px;">
                <span>{{ $daily[0]['day'] ?? '' }}</span>
                <span>{{ $daily[count($daily) - 1]['day'] ?? '' }}</span>
            </div>
        </div>
    @endif

    <style>
        @media (max-width: 768px) { .uso-ia-2col { grid-template-columns: 1fr !important; } }
    </style>

</x-filament-panels::page>
