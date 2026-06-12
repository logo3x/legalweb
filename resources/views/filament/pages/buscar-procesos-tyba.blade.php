<x-filament-panels::page>

    {{-- Hero educativo --}}
    <div style="background: linear-gradient(135deg, #EFF6FF 0%, #F0F9FF 100%); border: 1px solid #DBEAFE; border-radius: 14px; padding: 20px 24px; margin-bottom: 20px; display: flex; gap: 16px; align-items: flex-start;">
        <div style="flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; background: #3A86FF; color: #fff; display: flex; align-items: center; justify-content: center;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <div style="flex: 1; min-width: 0;">
            <h3 style="margin: 0 0 4px; font-size: 17px; font-weight: 700; color: #1E3A5F;">Encuentra procesos en la Rama Judicial</h3>
            <p style="margin: 0; font-size: 13.5px; color: #475569; line-height: 1.55;">
                Busca por <strong>nombre del cliente</strong>, <strong>cedula / NIT</strong> o <strong>numero de radicado</strong>.
                Importa el proceso completo con un click — actuaciones, sujetos, despacho y vigilancia automatica activada.
            </p>
        </div>
    </div>

    <form wire:submit="buscar" style="margin-bottom: 24px;">
        {{ $this->form }}

        <div style="margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap;">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass" size="lg">
                Buscar en la Rama
            </x-filament::button>
            <x-filament::button color="gray" tag="a" href="{{ url('/admin') }}">
                Volver al dashboard
            </x-filament::button>
        </div>
    </form>

    @if($errorMessage)
        <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 10px; padding: 14px 18px; margin-bottom: 16px; color: #991B1B; font-size: 14px;">
            {{ $errorMessage }}
        </div>
    @endif

    @if($hasSearched && ! $errorMessage)
        @php
            $labelMode = match($searchedMode) {
                'radicado' => 'radicado',
                'documento' => 'documento',
                default => 'nombre',
            };
            $totalEncontrados = count($procesos);
            $totalNuevos = collect($procesos)->filter(fn($p) => !in_array($p['radicado'], $existingRadicados))->count();
        @endphp

        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; font-size: 14px; color: #1E40AF; display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>{{ $totalEncontrados }}</strong> proceso(s) encontrado(s)</span>
            </div>
            @if($totalNuevos > 0 && $totalNuevos < $totalEncontrados)
                <div style="padding-left: 12px; border-left: 1px solid #BFDBFE;">
                    <strong style="color: #047857;">{{ $totalNuevos }}</strong> nuevo(s) para importar
                </div>
            @endif
            <div style="margin-left: auto; font-size: 12px; color: #64748B;">
                Buscaste por {{ $labelMode }}: <code style="background: #fff; padding: 2px 8px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; color: #1E3A5F;">{{ $searchedTerm }}</code>
            </div>
        </div>

        @if(empty($procesos))
            <div style="text-align: center; padding: 48px 24px; color: #64748B; background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1;">
                <div style="font-size: 40px; margin-bottom: 12px;">&#128269;</div>
                <p style="font-weight: 600; font-size: 16px; color: #334155; margin: 0 0 6px;">No se encontraron procesos</p>
                <p style="font-size: 13.5px; margin: 0; line-height: 1.55;">
                    Verifique los datos. La Rama busca por coincidencia exacta &mdash;<br>
                    intente con menos palabras o el documento sin puntos ni guiones.
                </p>
            </div>
        @else
            <div>
                @foreach($procesos as $p)
                    @php $yaImportado = in_array($p['radicado'], $existingRadicados); @endphp
                    <div style="border: 1px solid {{ $yaImportado ? '#A7F3D0' : '#E2E8F0' }}; border-radius: 12px; padding: 16px 18px; margin-bottom: 10px; background: {{ $yaImportado ? '#ECFDF5' : '#fff' }}; transition: all .2s cubic-bezier(0.23, 1, 0.32, 1);"
                         onmouseover="if(!{{ $yaImportado ? 'true' : 'false' }}){this.style.borderColor='#3A86FF';this.style.boxShadow='0 6px 16px rgba(58,134,255,.10)';this.style.transform='translateY(-1px)'}"
                         onmouseout="if(!{{ $yaImportado ? 'true' : 'false' }}){this.style.borderColor='#E2E8F0';this.style.boxShadow='none';this.style.transform='translateY(0)'}">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                                    <span style="font-size: 13.5px; font-weight: 600; color: #0F172A; font-family: 'JetBrains Mono', monospace; letter-spacing: -.01em;">{{ $p['radicado'] }}</span>
                                    @if($yaImportado)
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #D1FAE5; color: #065F46;">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Ya importado
                                        </span>
                                    @else
                                        <span style="display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #DBEAFE; color: #1E40AF;">Nuevo</span>
                                    @endif
                                </div>
                                <div style="font-size: 13.5px; color: #1E293B; margin-bottom: 4px; font-weight: 500;">{{ $p['despacho'] ?? '' }}</div>
                                <div style="font-size: 12.5px; color: #64748B; margin-bottom: 6px;">
                                    {{ $p['departamento'] ?? '' }}
                                    @if(!empty($p['fecha'])) &middot; Radicado el {{ $p['fecha'] }} @endif
                                    @if(!empty($p['ultima_actuacion'])) &middot; Ultima actuacion: <strong style="color: #1E3A5F;">{{ $p['ultima_actuacion'] }}</strong> @endif
                                </div>
                                @if(!empty($p['sujetos']))
                                    <div style="font-size: 12px; color: #94A3B8; line-height: 1.5; max-height: 36px; overflow: hidden;">{{ $p['sujetos'] }}</div>
                                @endif
                            </div>
                            <div style="flex-shrink: 0; display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                                @if(! $yaImportado)
                                    <button
                                        type="button"
                                        onclick="navigator.clipboard.writeText('{{ $p['radicado'] }}'); this.innerHTML='<svg width=12 height=12 fill=none stroke=currentColor stroke-width=2.5 viewBox=\'0 0 24 24\'><path stroke-linecap=round stroke-linejoin=round d=\'M5 13l4 4L19 7\'/></svg> Radicado copiado'; this.style.background='#D1FAE5'; this.style.color='#065F46'; this.style.borderColor='#A7F3D0';"
                                        style="display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; font-size: 12px; font-weight: 600; border: 1px solid #CBD5E1; border-radius: 8px; background: #fff; cursor: pointer; color: #334155; transition: all .15s ease;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1m-6-12h6a2 2 0 012 2v8a2 2 0 01-2 2H10a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                                        Copiar radicado
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($totalNuevos > 0)
                <div style="margin-top: 16px; padding: 16px 18px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; display: flex; gap: 12px; align-items: flex-start;">
                    <svg width="20" height="20" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 2px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div style="font-size: 13px; color: #78350F; line-height: 1.55;">
                        <strong>Para importar:</strong> copia el radicado del proceso nuevo y ve a
                        <a href="{{ url('/admin/cases/create') }}" style="color: #1D4ED8; font-weight: 600; text-decoration: underline;">Casos &rarr; Nuevo caso</a>,
                        o usa la
                        <a href="{{ url('/admin/cases?action=importar-masivo') }}" style="color: #1D4ED8; font-weight: 600; text-decoration: underline;">importacion masiva</a>
                        para varios a la vez.
                    </div>
                </div>
            @endif
        @endif
    @endif
</x-filament-panels::page>
