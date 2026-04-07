<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #1E3A5F; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #1E3A5F; margin-bottom: 2px; }
        .header h2 { font-size: 13px; color: #3A86FF; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        .section { margin-bottom: 18px; }
        .section h3 { font-size: 12px; color: #1E3A5F; border-bottom: 2px solid #3A86FF; padding-bottom: 3px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 10px; }
        th { background: #f0f4ff; color: #1E3A5F; font-weight: 600; }
        .info-row { display: block; margin-bottom: 3px; }
        .info-label { color: #666; font-size: 9px; display: inline-block; width: 120px; }
        .info-value { color: #111; font-weight: 500; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; margin-top: 25px; padding-top: 10px; border-top: 2px solid #1E3A5F; font-size: 8px; color: #999; }
        .footer strong { color: #1E3A5F; }
        .highlight { background: #fffbeb; padding: 8px 12px; border-left: 3px solid #f59e0b; margin-bottom: 10px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $firm->name }}</h1>
        <h2>Reporte Mensual del Caso</h2>
        <p>Periodo: {{ $periodo }} | Generado: {{ $generated_at->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Datos del caso --}}
    <div class="section">
        <h3>Informacion del Caso</h3>
        <div class="info-row"><span class="info-label">Caso:</span> <span class="info-value">{{ $case->case_number }}</span></div>
        <div class="info-row"><span class="info-label">Titulo:</span> <span class="info-value">{{ $case->title }}</span></div>
        <div class="info-row"><span class="info-label">Radicado:</span> <span class="info-value">{{ $case->external_case_number ?? 'Sin radicado' }}</span></div>
        <div class="info-row"><span class="info-label">Tipo:</span> <span class="info-value">{{ $case->caseType->name ?? '-' }}</span></div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="badge badge-{{ $case->status === 'abierto' ? 'info' : ($case->status === 'cerrado' ? 'success' : 'warning') }}">
                {{ ucfirst(str_replace('_', ' ', $case->status)) }}
            </span>
        </div>
        <div class="info-row"><span class="info-label">Despacho:</span> <span class="info-value">{{ $case->court ?? '-' }}</span></div>
        <div class="info-row"><span class="info-label">Juez/Ponente:</span> <span class="info-value">{{ $case->judge ?? '-' }}</span></div>
        <div class="info-row"><span class="info-label">Abogado:</span> <span class="info-value">{{ $case->user->name ?? '-' }}</span></div>
    </div>

    {{-- Cliente --}}
    <div class="section">
        <h3>Cliente</h3>
        <div class="info-row"><span class="info-label">Nombre:</span> <span class="info-value">{{ $client->full_name }}</span></div>
        <div class="info-row"><span class="info-label">Documento:</span> <span class="info-value">{{ $client->document_type }} {{ $client->document_number }}</span></div>
    </div>

    {{-- Resumen del mes --}}
    @if(!empty($resumen))
    <div class="section">
        <h3>Resumen del Periodo</h3>
        <table>
            <tr>
                <td style="text-align: center; border: 1px solid #eee; padding: 10px; width: 33%;">
                    <div style="font-size: 20px; font-weight: bold; color: #3A86FF;">{{ $resumen['nuevas_actuaciones'] }}</div>
                    <div style="font-size: 9px; color: #888;">Nuevas actuaciones</div>
                </td>
                <td style="text-align: center; border: 1px solid #eee; padding: 10px; width: 33%;">
                    <div style="font-size: 20px; font-weight: bold; color: #f59e0b;">{{ $resumen['recordatorios_pendientes'] }}</div>
                    <div style="font-size: 9px; color: #888;">Recordatorios pendientes</div>
                </td>
                <td style="text-align: center; border: 1px solid #eee; padding: 10px; width: 33%;">
                    <div style="font-size: 20px; font-weight: bold; color: #10b981;">{{ $resumen['sincronizaciones'] }}</div>
                    <div style="font-size: 9px; color: #888;">Sincronizaciones</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Actuaciones del mes --}}
    @if($actuaciones->isNotEmpty())
    <div class="section">
        <h3>Actuaciones del Periodo ({{ $actuaciones->count() }})</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Fecha</th>
                    <th>Actuacion</th>
                    <th style="width: 80px;">Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($actuaciones as $event)
                <tr>
                    <td>{{ $event->event_date->format('d/m/Y') }}</td>
                    <td>{{ $event->title }}</td>
                    <td><span class="badge badge-info">{{ $event->event_type }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="highlight">No se registraron actuaciones nuevas durante este periodo.</div>
    @endif

    {{-- Flujo procesal --}}
    @if($flowProgress->isNotEmpty())
    <div class="section">
        <h3>Estado del Flujo Procesal</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Etapa</th>
                    <th style="width: 80px;">Estado</th>
                    <th style="width: 80px;">Completado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flowProgress as $step)
                <tr>
                    <td>{{ $step->flowStep->order ?? '-' }}</td>
                    <td>{{ $step->flowStep->name ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $step->status === 'completado' ? 'success' : ($step->status === 'en_progreso' ? 'warning' : 'info') }}">
                            {{ ucfirst(str_replace('_', ' ', $step->status)) }}
                        </span>
                    </td>
                    <td>{{ $step->completed_at?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Proximos vencimientos --}}
    @if($vencimientos->isNotEmpty())
    <div class="section">
        <h3>Proximos Vencimientos</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Vence</th>
                    <th>Descripcion</th>
                    <th style="width: 60px;">Prioridad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vencimientos as $reminder)
                <tr>
                    <td>{{ $reminder->due_date->format('d/m/Y') }}</td>
                    <td>{{ $reminder->title }}</td>
                    <td>
                        <span class="badge badge-{{ $reminder->priority === 'urgente' ? 'danger' : ($reminder->priority === 'alta' ? 'warning' : 'info') }}">
                            {{ ucfirst($reminder->priority) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <strong>{{ $firm->name }}</strong> | Reporte generado automaticamente por LegalWeb<br>
        Este documento es informativo. Para consultas contacte a su abogado: {{ $case->user->email ?? '' }}
    </div>
</body>
</html>
