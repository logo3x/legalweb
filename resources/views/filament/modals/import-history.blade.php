<div>
    @if($logs->isEmpty())
        <div style="text-align: center; padding: 32px; color: #9ca3af;">
            <p style="font-weight: 500;">No hay importaciones registradas</p>
            <p style="font-size: 13px;">Use el boton "Importacion Masiva" para importar procesos desde la Rama Judicial.</p>
        </div>
    @else
        <div style="font-size: 12px; color: #6b7280; margin-bottom: 16px;">
            Ultimas 10 importaciones masivas realizadas por su firma.
        </div>

        @foreach($logs as $log)
            <details style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px; overflow: hidden;">
                <summary style="padding: 12px 16px; cursor: pointer; background: #f9fafb; display: flex; align-items: center; gap: 12px; list-style: none;">
                    <div style="flex: 1;">
                        <div style="font-size: 13px; font-weight: 500; color: #111827;">
                            {{ $log->created_at->format('d/m/Y H:i') }} - {{ $log->user?->name ?? 'Usuario' }}
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                            {{ $log->total_radicados }} radicado(s) procesados
                        </div>
                    </div>
                    <div style="display: flex; gap: 6px; flex-shrink: 0;">
                        @if($log->importados > 0)
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #dcfce7; color: #166534;">{{ $log->importados }} importado(s)</span>
                        @endif
                        @if($log->duplicados > 0)
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #fef3c7; color: #92400e;">{{ $log->duplicados }} duplicado(s)</span>
                        @endif
                        @if($log->no_encontrados > 0)
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #ffedd5; color: #9a3412;">{{ $log->no_encontrados }} no encontrado(s)</span>
                        @endif
                        @if($log->errores > 0)
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #fee2e2; color: #991b1b;">{{ $log->errores }} error(es)</span>
                        @endif
                    </div>
                </summary>

                @if(!empty($log->detalle))
                    <div style="border-top: 1px solid #e5e7eb;">
                        @foreach($log->detalle as $r)
                            @php
                                $rowBg = match($r['status'] ?? '') {
                                    'ok' => '#f0fdf4',
                                    'duplicado' => '#fffbeb',
                                    'no_encontrado' => '#fff7ed',
                                    'error' => '#fef2f2',
                                    default => '#fff',
                                };
                                $badgeBg = match($r['status'] ?? '') {
                                    'ok' => '#dcfce7',
                                    'duplicado' => '#fef3c7',
                                    'no_encontrado' => '#ffedd5',
                                    'error' => '#fee2e2',
                                    default => '#f3f4f6',
                                };
                                $badgeColor = match($r['status'] ?? '') {
                                    'ok' => '#166534',
                                    'duplicado' => '#92400e',
                                    'no_encontrado' => '#9a3412',
                                    'error' => '#991b1b',
                                    default => '#374151',
                                };
                                $badgeText = match($r['status'] ?? '') {
                                    'ok' => 'Importado',
                                    'duplicado' => 'Ya existia',
                                    'no_encontrado' => 'No encontrado',
                                    'error' => 'Error',
                                    default => $r['status'] ?? '-',
                                };
                            @endphp
                            <div style="padding: 8px 16px; border-bottom: 1px solid #f3f4f6; background: {{ $rowBg }}; display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 12px; font-family: monospace; color: #374151; flex: 1;">{{ $r['radicado'] ?? '-' }}</span>
                                <span style="font-size: 11px; color: #6b7280; flex: 1;">{{ Str::limit($r['msg'] ?? '', 50) }}</span>
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 500; background: {{ $badgeBg }}; color: {{ $badgeColor }}; flex-shrink: 0;">{{ $badgeText }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </details>
        @endforeach
    @endif
</div>
