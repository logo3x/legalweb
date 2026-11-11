<div>
    @php
        $actuaciones = $cambios['actuaciones'] ?? [];
        $campos = $cambios['campos'] ?? [];
    @endphp

    @if (! empty($campos))
        <div style="margin-bottom: 20px;">
            <div style="font-size: 13px; font-weight: 700; color: #1E3A5F; margin-bottom: 8px;">Datos del proceso que cambiaron</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr style="background: #f5f7fa; text-align: left;">
                        <th style="padding: 8px; border: 1px solid #e5e7eb;">Campo</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb;">Antes</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb;">Ahora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campos as $campo)
                        <tr>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: 600;">{{ $campo['campo'] }}</td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; color: #b91c1c; text-decoration: line-through;">{{ $campo['antes'] }}</td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; color: #15803d; font-weight: 600; background: #f0fdf4;">{{ $campo['despues'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (! empty($actuaciones))
        <div style="font-size: 13px; font-weight: 700; color: #1E3A5F; margin-bottom: 8px;">Actuaciones nuevas ({{ count($actuaciones) }})</div>
        @foreach ($actuaciones as $actuacion)
            <div style="border-left: 3px solid #3A86FF; background: #f5f7fa; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px;">
                <div style="font-size: 12px; font-weight: 600; color: #1f2937;">
                    <span style="color: #3A86FF;">{{ $actuacion['fecha'] }}</span> &middot; {{ $actuacion['tipo'] }}
                </div>
                @if (! empty($actuacion['anotacion']))
                    <div style="font-size: 12px; color: #4b5563; margin-top: 4px;">{{ $actuacion['anotacion'] }}</div>
                @endif
            </div>
        @endforeach
    @endif
</div>
