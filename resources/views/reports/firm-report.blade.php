<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #1E3A5F; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #1E3A5F; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #666; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 14px; color: #1E3A5F; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 10px; }
        .kpi-grid { display: table; width: 100%; margin-bottom: 20px; }
        .kpi-item { display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #eee; }
        .kpi-value { font-size: 24px; font-weight: bold; color: #1E3A5F; }
        .kpi-label { font-size: 10px; color: #888; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 11px; }
        th { background: #f5f7fa; color: #1E3A5F; font-weight: 600; }
        .bar-container { width: 100%; height: 8px; background: #eee; border-radius: 4px; }
        .bar-fill { height: 8px; border-radius: 4px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #999; }
        .two-col { display: table; width: 100%; }
        .col { display: table-cell; width: 48%; vertical-align: top; padding-right: 2%; }
        .col:last-child { padding-right: 0; padding-left: 2%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $firm->name }}</h1>
        @if($firm->nit)<p>NIT: {{ $firm->nit }}</p>@endif
        <p>Reporte generado el {{ $generated_at->format('d/m/Y H:i') }}</p>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi-item">
            <div class="kpi-value">{{ $total_cases }}</div>
            <div class="kpi-label">Total Casos</div>
        </div>
        <div class="kpi-item">
            <div class="kpi-value">{{ $total_clients }}</div>
            <div class="kpi-label">Clientes</div>
        </div>
        <div class="kpi-item">
            <div class="kpi-value">{{ $closed_cases }}</div>
            <div class="kpi-label">Casos Cerrados</div>
        </div>
        <div class="kpi-item">
            <div class="kpi-value">{{ $avg_days_per_case }}</div>
            <div class="kpi-label">Promedio dias/caso</div>
        </div>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="section">
                <h2>Casos por Estado</h2>
                <table>
                    <tr><th>Estado</th><th>Cantidad</th><th>%</th></tr>
                    @foreach($cases_by_status as $status => $count)
                        @php $percent = $total_cases > 0 ? round(($count / $total_cases) * 100) : 0; @endphp
                        <tr>
                            <td>{{ $status_labels[$status] ?? $status }}</td>
                            <td>{{ $count }}</td>
                            <td>{{ $percent }}%</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="section">
                <h2>Casos por Prioridad</h2>
                <table>
                    <tr><th>Prioridad</th><th>Cantidad</th></tr>
                    @foreach($cases_by_priority as $priority => $count)
                        <tr>
                            <td>{{ $priority_labels[$priority] ?? $priority }}</td>
                            <td>{{ $count }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="col">
            <div class="section">
                <h2>Casos por Tipo de Proceso</h2>
                <table>
                    <tr><th>Tipo</th><th>Cantidad</th></tr>
                    @foreach($cases_by_type as $type => $count)
                        <tr>
                            <td>{{ $type }}</td>
                            <td>{{ $count }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="section">
                <h2>Productividad por Abogado</h2>
                <table>
                    <tr><th>Abogado</th><th>Casos</th></tr>
                    @foreach($cases_by_lawyer as $lawyer => $count)
                        <tr>
                            <td>{{ $lawyer }}</td>
                            <td>{{ $count }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Actividad</h2>
        <div class="kpi-grid">
            <div class="kpi-item">
                <div class="kpi-value" style="font-size: 18px;">{{ $recent_events }}</div>
                <div class="kpi-label">Actuaciones (30 dias)</div>
            </div>
            <div class="kpi-item">
                <div class="kpi-value" style="font-size: 18px;">{{ $completed_steps }}</div>
                <div class="kpi-label">Pasos Completados</div>
            </div>
            <div class="kpi-item">
                <div class="kpi-value" style="font-size: 18px;">{{ $pending_steps }}</div>
                <div class="kpi-label">Pasos Pendientes</div>
            </div>
            <div class="kpi-item">
                <div class="kpi-value" style="font-size: 18px;">{{ $total_users }}</div>
                <div class="kpi-label">Usuarios</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Reporte generado por LegalWeb - {{ $firm->name }} - {{ $generated_at->format('d/m/Y H:i') }}<br>
        Este reporte es de uso interno. legalweb.com.co
    </div>
</body>
</html>
