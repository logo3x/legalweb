<div>
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: #1e40af;">
        Se encontraron <strong>{{ $totalFound }}</strong> proceso(s) en la Rama Judicial para <strong>{{ $clientName }}</strong>.
        Los procesos ya importados aparecen marcados.
    </div>

    @if(empty($procesos))
        <div style="text-align: center; padding: 32px; color: #9ca3af;">
            <p style="font-weight: 500;">No se encontraron procesos</p>
            <p style="font-size: 13px;">Verifique que el nombre del cliente sea correcto en la Rama Judicial.</p>
        </div>
    @else
        <div style="max-height: 400px; overflow-y: auto;">
            @foreach($procesos as $p)
                @php $yaImportado = in_array($p['radicado'], $existingRadicados); @endphp
                <div style="border: 1px solid {{ $yaImportado ? '#bbf7d0' : '#e5e7eb' }}; border-radius: 8px; padding: 12px; margin-bottom: 8px; background: {{ $yaImportado ? '#f0fdf4' : '#fff' }};">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <span style="font-size: 13px; font-weight: 600; color: #111827; font-family: monospace;">{{ $p['radicado'] }}</span>
                                @if($yaImportado)
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 500; background: #dcfce7; color: #166534;">Ya importado</span>
                                @endif
                            </div>
                            <div style="font-size: 12px; color: #374151; margin-bottom: 2px;">{{ $p['despacho'] }}</div>
                            <div style="font-size: 11px; color: #6b7280;">{{ $p['departamento'] }} | Inicio: {{ $p['fecha'] }} | Ultima act: {{ $p['ultima_actuacion'] }}</div>
                            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 500px;">{{ $p['sujetos'] }}</div>
                        </div>
                        @if(! $yaImportado)
                            <div style="flex-shrink: 0; margin-left: 12px;">
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText('{{ $p['radicado'] }}'); this.textContent='Copiado!'; this.style.background='#dcfce7'; this.style.color='#166534';"
                                    style="padding: 4px 12px; font-size: 11px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; color: #374151;">
                                    Copiar radicado
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if(collect($procesos)->filter(fn($p) => !in_array($p['radicado'], $existingRadicados))->count() > 0)
            <div style="margin-top: 12px; padding: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 12px; color: #92400e;">
                Para importar un proceso, copie el radicado y use el boton "Importar desde Tyba" o "Importacion Masiva" en la lista de casos.
            </div>
        @endif
    @endif
</div>
