<x-filament-panels::page>
    <form wire:submit="buscar" class="mb-6">
        {{ $this->form }}

        <div class="mt-4 flex gap-2">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Buscar
            </x-filament::button>
            <x-filament::button color="gray" tag="a" href="{{ route('filament.admin.resources.clients.index') }}">
                Volver a Clientes
            </x-filament::button>
        </div>
    </form>

    @if($hasSearched)
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: #1e40af;">
            Se encontraron <strong>{{ count($procesos) }}</strong> proceso(s) en la Rama Judicial para <strong>{{ $searchedName }}</strong>.
            Los procesos ya importados aparecen marcados.
        </div>

        @if(empty($procesos))
            <div style="text-align: center; padding: 32px; color: #9ca3af; background: #f9fafb; border-radius: 8px;">
                <p style="font-weight: 500; font-size: 15px;">No se encontraron procesos</p>
                <p style="font-size: 13px;">Verifique la ortografia. La busqueda se hace por coincidencia de nombre exacto en la Rama Judicial.</p>
            </div>
        @else
            <div>
                @foreach($procesos as $p)
                    @php $yaImportado = in_array($p['radicado'], $existingRadicados); @endphp
                    <div style="border: 1px solid {{ $yaImportado ? '#bbf7d0' : '#e5e7eb' }}; border-radius: 8px; padding: 12px; margin-bottom: 8px; background: {{ $yaImportado ? '#f0fdf4' : '#fff' }};">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                                    <span style="font-size: 13px; font-weight: 600; color: #111827; font-family: monospace;">{{ $p['radicado'] }}</span>
                                    @if($yaImportado)
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 500; background: #dcfce7; color: #166534;">Ya importado</span>
                                    @endif
                                </div>
                                <div style="font-size: 12px; color: #374151; margin-bottom: 2px;">{{ $p['despacho'] ?? '' }}</div>
                                <div style="font-size: 11px; color: #6b7280;">{{ $p['departamento'] ?? '' }} | Inicio: {{ $p['fecha'] ?? '-' }} | &Uacute;ltima act: {{ $p['ultima_actuacion'] ?? '-' }}</div>
                                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $p['sujetos'] ?? '' }}</div>
                            </div>
                            @if(! $yaImportado)
                                <div style="flex-shrink: 0;">
                                    <button
                                        type="button"
                                        onclick="navigator.clipboard.writeText('{{ $p['radicado'] }}'); this.textContent='Copiado'; this.style.background='#dcfce7'; this.style.color='#166534';"
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
                    Para importar un proceso, copie el radicado y use el bot&oacute;n &laquo;Importar desde Tyba&raquo; o &laquo;Importaci&oacute;n Masiva&raquo; en la lista de casos.
                </div>
            @endif
        @endif
    @endif
</x-filament-panels::page>
