<div>
    @php
        $imported = collect($results)->where('status', 'ok')->count();
        $duplicados = collect($results)->where('status', 'duplicado')->count();
        $noEncontrados = collect($results)->where('status', 'no_encontrado')->count();
        $errores = collect($results)->where('status', 'error')->count();
        $total = count($results);
    @endphp

    {{-- Resumen --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: #16a34a;">{{ $imported }}</div>
            <div style="font-size: 11px; color: #15803d;">Importados</div>
        </div>
        <div style="background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: #ca8a04;">{{ $duplicados }}</div>
            <div style="font-size: 11px; color: #a16207;">Duplicados</div>
        </div>
        <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: #ea580c;">{{ $noEncontrados }}</div>
            <div style="font-size: 11px; color: #c2410c;">No encontrados</div>
        </div>
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: #dc2626;">{{ $errores }}</div>
            <div style="font-size: 11px; color: #b91c1c;">Errores</div>
        </div>
    </div>

    {{-- Detalle por radicado --}}
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        {{-- Header --}}
        <div style="display: grid; grid-template-columns: 1fr 200px 100px; background: #f9fafb; padding: 10px 16px; border-bottom: 1px solid #e5e7eb;">
            <div style="font-size: 12px; font-weight: 600; color: #374151;">Radicado</div>
            <div style="font-size: 12px; font-weight: 600; color: #374151;">Resultado</div>
            <div style="font-size: 12px; font-weight: 600; color: #374151; text-align: center;">Estado</div>
        </div>

        @foreach($results as $r)
            @php
                $rowBg = match($r['status']) {
                    'ok' => '#f0fdf4',
                    'duplicado' => '#fffbeb',
                    'no_encontrado' => '#fff7ed',
                    'error' => '#fef2f2',
                    default => '#fff',
                };
                $badgeBg = match($r['status']) {
                    'ok' => '#dcfce7',
                    'duplicado' => '#fef3c7',
                    'no_encontrado' => '#ffedd5',
                    'error' => '#fee2e2',
                    default => '#f3f4f6',
                };
                $badgeColor = match($r['status']) {
                    'ok' => '#166534',
                    'duplicado' => '#92400e',
                    'no_encontrado' => '#9a3412',
                    'error' => '#991b1b',
                    default => '#374151',
                };
                $badgeText = match($r['status']) {
                    'ok' => 'Importado',
                    'duplicado' => 'Ya existe',
                    'no_encontrado' => 'No encontrado',
                    'error' => 'Error',
                    default => $r['status'],
                };
            @endphp
            <div style="display: grid; grid-template-columns: 1fr 200px 100px; padding: 12px 16px; border-bottom: 1px solid #f3f4f6; background: {{ $rowBg }}; align-items: center;">
                <div>
                    <div style="font-size: 13px; font-weight: 500; color: #111827; font-family: monospace;">{{ $r['radicado'] }}</div>
                    <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">{{ $r['msg'] }}</div>
                    @if(!empty($r['case_number']))
                        <a href="{{ route('filament.admin.resources.legal-cases.view', $r['case_id'] ?? 0) }}" style="font-size: 11px; color: #3A86FF; text-decoration: none; margin-top: 2px; display: inline-block;">
                            Ver {{ $r['case_number'] }}
                        </a>
                    @endif
                </div>
                <div style="font-size: 12px; color: #6b7280;">
                    @if($r['status'] === 'ok')
                        {{ $r['case_number'] ?? '' }}
                    @elseif($r['status'] === 'duplicado')
                        Ya registrado en su firma
                    @elseif($r['status'] === 'no_encontrado')
                        Sin datos en Rama Judicial
                    @else
                        Fallo al procesar
                    @endif
                </div>
                <div style="text-align: center;">
                    <span style="display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; background: {{ $badgeBg }}; color: {{ $badgeColor }};">
                        {{ $badgeText }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    @if($imported > 0)
        <div style="margin-top: 16px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 12px; color: #166534;">
            Los casos importados ya estan disponibles en su lista de casos con todas sus actuaciones y datos de la Rama Judicial.
        </div>
    @endif
</div>
