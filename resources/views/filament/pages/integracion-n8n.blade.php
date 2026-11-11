<x-filament-panels::page>
    @php
        $status = $this->getStatus();
        $events = $this->getEvents();
        $deliveries = $this->getDeliveries();
        $statusColors = ['enviado' => ['#dcfce7', '#166534'], 'fallido' => ['#fee2e2', '#991b1b'], 'pendiente' => ['#fef3c7', '#92400e']];
        $variables = [
            'webhook_url' => ['N8N_WEBHOOK_URL', 'URL del webhook de n8n que recibe los eventos'],
            'webhook_secret' => ['N8N_WEBHOOK_SECRET', 'Firma cada envio (cabecera X-LegalWeb-Signature)'],
            'api_token' => ['N8N_API_TOKEN', 'Permite a n8n consultar /n8n/firmas'],
        ];
    @endphp

    @if (! $this->isReady())
        <div style="padding: 20px; background: #FEF3C7; border-radius: 12px; border-left: 4px solid #D97706; color: #78350F; font-size: 14px;">
            <strong>Falta migrar.</strong> Abra setup.php y ejecute el paso <strong>Migrar</strong>.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
        @foreach ($variables as $key => $info)
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="font-size: 12px; color: #1E3A5F; font-weight: 700;">{{ $info[0] }}</code>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; {{ $status[$key] ? 'background:#dcfce7;color:#166534;' : 'background:#f3f4f6;color:#6b7280;' }}">
                        {{ $status[$key] ? 'Configurado' : 'Sin configurar' }}
                    </span>
                </div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 6px;">{{ $info[1] }}</div>
            </div>
        @endforeach
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px;">
        <div style="font-size: 14px; font-weight: 700; color: #1E3A5F; margin-bottom: 4px;">Eventos</div>
        <div style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">
            Se eligen con <code>N8N_EVENTS</code> en el .env (separados por coma, o <code>*</code> para todos). Todos llegan al mismo webhook; en n8n use un nodo <em>Switch</em> sobre el campo <code>event</code>.
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 8px;">
            @foreach ($events as $event => $info)
                <div style="display: flex; gap: 8px; align-items: flex-start; font-size: 13px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; background: {{ $info['enabled'] ? '#16a34a' : '#d1d5db' }};"></span>
                    <div>
                        <code style="font-size: 12px; color: #1E3A5F;">{{ $event }}</code>
                        <div style="color: #6b7280; font-size: 12px;">{{ $info['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow-x: auto;">
        <div style="padding: 16px 16px 0; font-size: 14px; font-weight: 700; color: #1E3A5F;">Ultimos envios</div>
        @if ($deliveries->isEmpty())
            <div style="padding: 16px; font-size: 13px; color: #9ca3af;">Aun no hay envios.</div>
        @else
            <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 8px;">
                <thead>
                    <tr style="background: #f5f7fa; text-align: left; color: #1E3A5F;">
                        <th style="padding: 10px 12px;">Fecha</th>
                        <th style="padding: 10px 12px;">Evento</th>
                        <th style="padding: 10px 12px;">Firma</th>
                        <th style="padding: 10px 12px;">Estado</th>
                        <th style="padding: 10px 12px;">Intentos</th>
                        <th style="padding: 10px 12px;">Detalle</th>
                        <th style="padding: 10px 12px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deliveries as $delivery)
                        @php
                            $colors = $statusColors[$delivery->status] ?? ['#f3f4f6', '#374151'];
                        @endphp
                        <tr style="border-top: 1px solid #f3f4f6;">
                            <td style="padding: 10px 12px; white-space: nowrap;">{{ $delivery->created_at->format('d/m/Y H:i') }}</td>
                            <td style="padding: 10px 12px;"><code>{{ $delivery->event }}</code></td>
                            <td style="padding: 10px 12px;">{{ $delivery->firm?->name ?? '—' }}</td>
                            <td style="padding: 10px 12px;">
                                <span style="padding: 2px 8px; border-radius: 4px; font-weight: 600; background: {{ $colors[0] }}; color: {{ $colors[1] }};">{{ ucfirst($delivery->status) }}</span>
                            </td>
                            <td style="padding: 10px 12px; text-align: center;">{{ $delivery->attempts }}</td>
                            <td style="padding: 10px 12px; color: #6b7280; max-width: 320px;">
                                {{ $delivery->response_code ? 'HTTP '.$delivery->response_code : '' }} {{ \Illuminate\Support\Str::limit((string) $delivery->error, 120) }}
                            </td>
                            <td style="padding: 10px 12px;">
                                @if ($delivery->status !== 'enviado')
                                    <button wire:click="retry({{ $delivery->id }})" style="font-size: 12px; font-weight: 600; color: #3A86FF; background: none; border: none; cursor: pointer;">Reintentar</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-filament-panels::page>
