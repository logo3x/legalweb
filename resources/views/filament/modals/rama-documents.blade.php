{{-- Documentos publicados por el despacho en una actuacion de la Rama Judicial. --}}
<div style="display: flex; flex-direction: column; gap: 10px;">
    @if ($error)
        <div style="padding: 12px 14px; border-radius: 10px; background: #FEF2F2; color: #991B1B; font-size: 13.5px;">
            <p style="margin: 0;">{{ $error }}</p>
            <p style="margin: 6px 0 0;">
                Mientras tanto puede buscarlo en la
                <a href="https://consultaprocesos.ramajudicial.gov.co/Procesos/NumeroRadicacion" target="_blank" rel="noopener" style="color: #991B1B; font-weight: 600; text-decoration: underline;">consulta de procesos de la Rama</a>@if (filled($radicado ?? null)) con el radicado <span style="font-family: 'JetBrains Mono', monospace;">{{ $radicado }}</span>@endif.
            </p>
        </div>
    @elseif ($documentos === [])
        <p style="margin: 0; font-size: 13.5px; color: #475569;">La Rama Judicial no tiene documentos publicados para esta actuación en este momento.</p>
    @else
        @foreach ($documentos as $doc)
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #E2E8F0; border-radius: 10px;">
                <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex-shrink: 0; border-radius: 8px; background: #F1F5F9; color: #475569; font-size: 10.5px; font-weight: 700; text-transform: uppercase;">{{ $doc['tipo'] !== '' ? mb_substr($doc['tipo'], 0, 4) : 'doc' }}</span>
                <div style="flex: 1; min-width: 0;">
                    <p style="margin: 0; font-size: 14px; font-weight: 600; color: #0F172A; overflow-wrap: anywhere;">{{ $doc['nombre'] }}</p>
                    @if ($doc['descripcion'] !== '')
                        <p style="margin: 2px 0 0; font-size: 12.5px; color: #475569;">{{ $doc['descripcion'] }}</p>
                    @endif
                    @if (in_array($doc['url'], $guardados, true))
                        <p style="margin: 2px 0 0; font-size: 12.5px; color: #15803D;">Ya está en los documentos del caso.</p>
                    @endif
                </div>
                <x-filament::button tag="a" :href="$doc['url']" target="_blank" rel="noopener" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                    Descargar
                </x-filament::button>
            </div>
        @endforeach
        <p style="margin: 2px 0 0; font-size: 12.5px; color: #64748B;">Los archivos se descargan directamente de la Rama Judicial.</p>
    @endif
</div>
