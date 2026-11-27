<x-filament-panels::page>

    {{-- Hero educativo --}}
    <div style="background: linear-gradient(135deg, #EFF6FF 0%, #F0F9FF 100%); border: 1px solid #DBEAFE; border-radius: 14px; padding: 20px 24px; margin-bottom: 20px; display: flex; gap: 16px; align-items: flex-start;">
        <div style="flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; background: #3A86FF; color: #fff; display: flex; align-items: center; justify-content: center;">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <div style="flex: 1; min-width: 0;">
            <h3 style="margin: 0 0 4px; font-size: 17px; font-weight: 700; color: #1E3A5F;">Encuentra procesos en la Rama Judicial y en SAMAI</h3>
            <p style="margin: 0; font-size: 13.5px; color: #475569; line-height: 1.55;">
                Escribe el <strong>nombre</strong>, el <strong>número de radicado</strong> o la <strong>cédula / NIT de uno de tus clientes</strong> en un solo campo: detectamos el tipo de búsqueda.
                Por defecto buscamos en <strong>todas las fuentes</strong> (Rama Judicial y SAMAI) a la vez.
                Luego importas el proceso completo con un clic: actuaciones, sujetos, despacho y vigilancia automática.
            </p>
        </div>
    </div>

    <form wire:submit="buscar" style="margin-bottom: 24px;"
          x-data
          x-on:submit="setTimeout(() => { const el = document.querySelector('[data-lw-loading]'); if (el && el.offsetParent && el.getBoundingClientRect().top > window.innerHeight - 120) { window.scrollTo({ top: window.scrollY + el.getBoundingClientRect().top - window.innerHeight / 3, left: window.scrollX, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); } }, 350)">
        {{ $this->form }}

        <div style="margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap;">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass" size="lg">
                Buscar
            </x-filament::button>
            <x-filament::button color="gray" tag="a" href="{{ url('/admin') }}">
                Volver al dashboard
            </x-filament::button>
        </div>
    </form>

    {{-- Oculto de entrada: Livewire oculta por sí mismo solo los indicadores con un modificador, no ".delay.block". --}}
    <div wire:loading.delay.block wire:target="buscar,buscarPorNombreDePersona" aria-live="polite" data-lw-loading style="display: none;">
        <div style="display: flex; align-items: center; gap: 10px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 10px; padding: 14px 18px; margin-bottom: 14px; font-size: 14px; color: #1E40AF;">
            <span class="lw-spinner"></span>
            <span>Consultando las fuentes judiciales&hellip; la Rama Judicial puede tardar hasta un minuto.</span>
        </div>
        @foreach([72, 58, 66] as $width)
            <div class="lw-skeleton">
                <div class="lw-skeleton-line" style="width: 38%;"></div>
                <div class="lw-skeleton-line" style="width: {{ $width }}%;"></div>
                <div class="lw-skeleton-line" style="width: {{ $width - 22 }}%;"></div>
            </div>
        @endforeach
    </div>

    <div wire:loading.remove wire:target="buscar,buscarPorNombreDePersona">
    @if($documentoSinCliente)
        <section aria-labelledby="lw-persona-titulo" style="border: 1px solid #E2E8F0; border-radius: 12px; background: #fff; padding: 18px 20px; margin-bottom: 16px;">
            <h3 id="lw-persona-titulo" style="margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #0F172A;">
                El documento {{ $documentoSinCliente['numero'] }} no es de ninguno de sus clientes
            </h3>
            <p style="margin: 0 0 14px; font-size: 13.5px; color: #475569; line-height: 1.55; max-width: 72ch;">
                La Rama Judicial ya no busca por cédula o NIT. Escriba el nombre de esta persona y buscamos sus procesos; no hace falta registrarla como cliente.
            </p>
            <form wire:submit="buscarPorNombreDePersona" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: start;">
                <div>
                    <label for="lw-nombres" style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #0F172A;">Nombres</label>
                    <x-filament::input.wrapper :valid="! $errors->has('nombresPersona')">
                        <x-filament::input type="text" id="lw-nombres" wire:model="nombresPersona" placeholder="Ej: MARIA FERNANDA" autocomplete="off" maxlength="100" />
                    </x-filament::input.wrapper>
                    @error('nombresPersona')<p style="margin: 4px 0 0; font-size: 13px; color: #B91C1C;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lw-apellidos" style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #0F172A;">Apellidos</label>
                    <x-filament::input.wrapper :valid="! $errors->has('apellidosPersona')">
                        <x-filament::input type="text" id="lw-apellidos" wire:model="apellidosPersona" placeholder="Ej: GARCIA LOPEZ" autocomplete="off" maxlength="100" />
                    </x-filament::input.wrapper>
                    @error('apellidosPersona')<p style="margin: 4px 0 0; font-size: 13px; color: #B91C1C;">{{ $message }}</p>@enderror
                </div>
                <div style="padding-top: 28px;">
                    <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">Buscar por nombre</x-filament::button>
                </div>
            </form>
            <p style="margin: 14px 0 0; font-size: 13px; color: #475569;">
                ¿Es su cliente?
                <a href="{{ $this->urlRegistrarCliente() }}" wire:navigate style="color: #1D4ED8; font-weight: 600; text-decoration: underline;">Regístrelo con este documento</a>
                y la próxima vez lo encontramos solo con la cédula.
            </p>
        </section>
    @endif

    @if($errorMessage)
        <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 10px; padding: 14px 18px; margin-bottom: 16px; color: #991B1B; font-size: 14px;">
            {{ $errorMessage }}
        </div>
    @endif

    @foreach($avisos as $aviso)
        <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; color: #78350F; font-size: 13.5px; line-height: 1.5;">
            {{ $aviso }}
        </div>
    @endforeach

    @if($hasSearched && ! $errorMessage)
        @php
            $labelMode = match($searchedMode) {
                'radicado' => 'radicado',
                'documento' => 'cédula/NIT',
                default => 'nombre',
            };
            $labelFuente = match($searchedSource) {
                'rama' => 'Rama Judicial',
                'samai' => 'SAMAI',
                default => 'todas las fuentes',
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
                En {{ $labelFuente }}, por {{ $labelMode }}: <code style="background: #fff; padding: 2px 8px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; color: #1E3A5F;">{{ $searchedTerm }}</code>
            </div>
        </div>

        @if($buscoPorPersona && $documentoSinCliente)
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; border: 1px solid #E2E8F0; border-radius: 10px; background: #fff; padding: 12px 16px; margin-bottom: 18px; font-size: 13.5px; color: #334155; line-height: 1.5;">
                <span style="flex: 1 1 320px;">
                    ¿Guardar a <strong style="color: #0F172A;">{{ $searchedTerm }}</strong> (documento {{ $documentoSinCliente['numero'] }}) como cliente? Así la próxima vez lo encuentra solo con la cédula.
                </span>
                <span style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <x-filament::button wire:click="guardarComoCliente" size="sm" icon="heroicon-o-user-plus">Guardar como cliente</x-filament::button>
                    <x-filament::button color="gray" size="sm" tag="a" href="{{ $this->urlRegistrarCliente() }}">Registrar con más datos</x-filament::button>
                </span>
            </div>
        @endif

        @if(empty($procesos))
            <div style="text-align: center; padding: 48px 24px; color: #64748B; background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1;">
                <div style="font-size: 40px; margin-bottom: 12px;">&#128269;</div>
                <p style="font-weight: 600; font-size: 16px; color: #334155; margin: 0 0 6px;">No se encontraron procesos</p>
                <p style="font-size: 13.5px; margin: 0; line-height: 1.55;">
                    Verifique los datos. La Rama busca por coincidencia exacta &mdash;<br>
                    intente con menos palabras o con el documento sin puntos ni guiones.
                </p>
            </div>
        @else
            @if($totalNuevos > 1 && \App\Filament\Resources\LegalCases\LegalCaseResource::canCreate())
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 12px; font-size: 13.5px; color: #475569;">
                    <span>Marque los procesos que quiere importar, o impórtelos todos. Los que ya tiene no se repiten.</span>
                    <span style="display: flex; gap: 8px; flex-wrap: wrap;">
                        {{ ($this->importarAction)(['modo' => 'seleccionados']) }}
                        {{ ($this->importarAction)(['modo' => 'todos']) }}
                    </span>
                </div>
            @endif

            <div>
                @foreach($procesos as $p)
                    @php $yaImportado = in_array($p['radicado'], $existingRadicados); @endphp
                    <div class="lw-result" wire:key="proceso-{{ $p['radicado'] }}" style="border: 1px solid {{ $yaImportado ? '#A7F3D0' : '#E2E8F0' }}; border-radius: 12px; padding: 16px 18px; margin-bottom: 10px; background: {{ $yaImportado ? '#ECFDF5' : '#fff' }}; transition: all .2s cubic-bezier(0.23, 1, 0.32, 1);"
                         onmouseover="if(!{{ $yaImportado ? 'true' : 'false' }}){this.style.borderColor='#3A86FF';this.style.boxShadow='0 6px 16px rgba(58,134,255,.10)';this.style.transform='translateY(-1px)'}"
                         onmouseout="if(!{{ $yaImportado ? 'true' : 'false' }}){this.style.borderColor='#E2E8F0';this.style.boxShadow='none';this.style.transform='translateY(0)'}">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                                    @if(! $yaImportado && $totalNuevos > 1 && \App\Filament\Resources\LegalCases\LegalCaseResource::canCreate())
                                        <input type="checkbox" id="lw-sel-{{ $p['radicado'] }}" wire:model.live="seleccionados" value="{{ $p['radicado'] }}"
                                               aria-label="Seleccionar el proceso {{ $p['radicado'] }} para importar"
                                               style="width: 18px; height: 18px; accent-color: #2563EB; cursor: pointer; margin: 0;">
                                    @endif
                                    <label for="lw-sel-{{ $p['radicado'] }}" style="font-size: 13.5px; font-weight: 600; color: #0F172A; font-family: 'JetBrains Mono', monospace; letter-spacing: -.01em; cursor: {{ $yaImportado ? 'default' : 'pointer' }};">{{ $p['radicado'] }}</label>
                                    @if(($p['fuente'] ?? '') === 'samai')
                                        <span style="display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #EDE9FE; color: #5B21B6;">SAMAI</span>
                                    @elseif(($p['fuente'] ?? '') === 'rama' && $searchedSource === 'todas')
                                        <span style="display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #F1F5F9; color: #334155;">Rama Judicial</span>
                                    @endif
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
                                    @if(!empty($p['ultima_actuacion'])) &middot; Última actuación: <strong style="color: #1E3A5F;">{{ $p['ultima_actuacion'] }}</strong> @endif
                                </div>
                                @if(!empty($p['sujetos']))
                                    <div style="font-size: 12px; color: #94A3B8; line-height: 1.5; max-height: 36px; overflow: hidden;">{{ $p['sujetos'] }}</div>
                                @endif
                            </div>
                            <div style="flex-shrink: 0; display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                                @if(! empty($p['estado']))
                                    <span style="padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; {{ $p['estado'] === 'Vigente' ? 'background:#DBEAFE;color:#1E40AF;' : 'background:#F1F5F9;color:#475569;' }}">{{ $p['estado'] }}</span>
                                @endif
                                @if(! empty($p['url']) && str_starts_with((string) $p['url'], 'https://'))
                                    <a href="{{ $p['url'] }}" target="_blank" rel="noopener" style="font-size: 12px; font-weight: 600; color: #3A86FF; text-decoration: underline;">Ver en SAMAI</a>
                                @endif
                                @if(! $yaImportado)
                                    {{ ($this->importarAction)(['modo' => 'uno', 'radicado' => $p['radicado']]) }}
                                    <button
                                        type="button"
                                        data-radicado="{{ $p['radicado'] }}"
                                        onclick="navigator.clipboard.writeText(this.dataset.radicado); this.innerHTML='<svg width=12 height=12 fill=none stroke=currentColor stroke-width=2.5 viewBox=\'0 0 24 24\'><path stroke-linecap=round stroke-linejoin=round d=\'M5 13l4 4L19 7\'/></svg> Radicado copiado'; this.style.background='#D1FAE5'; this.style.color='#065F46'; this.style.borderColor='#A7F3D0';"
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

        @endif
    @endif
    </div>
</x-filament-panels::page>
