<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; padding: 15px 0; border-bottom: 3px solid #1E3A5F; margin-bottom: 15px; }
        .header h1 { font-size: 20px; color: #1E3A5F; margin-bottom: 2px; }
        .header p { font-size: 10px; color: #666; }
        .section { margin-bottom: 15px; }
        .section h2 { font-size: 13px; color: #1E3A5F; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { padding: 5px 6px; text-align: left; border-bottom: 1px solid #eee; font-size: 10px; }
        th { background: #f5f7fa; color: #1E3A5F; font-weight: 600; }
        .footer { text-align: center; margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 8px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $firm->name }}</h1>
        @if($firm->nit)<p>NIT: {{ $firm->nit }}</p>@endif
        <p>Reporte generado el {{ $generated_at->format('d/m/Y H:i') }}</p>
    </div>

    {{-- KPIs --}}
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="text-align: center; border: 1px solid #eee; padding: 10px;">
                <div style="font-size: 22px; font-weight: bold; color: #1E3A5F;">{{ $total_cases }}</div>
                <div style="font-size: 9px; color: #888;">Total Casos</div>
            </td>
            <td style="text-align: center; border: 1px solid #eee; padding: 10px;">
                <div style="font-size: 22px; font-weight: bold; color: #1E3A5F;">{{ $total_clients }}</div>
                <div style="font-size: 9px; color: #888;">Clientes</div>
            </td>
            <td style="text-align: center; border: 1px solid #eee; padding: 10px;">
                <div style="font-size: 22px; font-weight: bold; color: #1E3A5F;">{{ $closed_cases }}</div>
                <div style="font-size: 9px; color: #888;">Cerrados</div>
            </td>
            <td style="text-align: center; border: 1px solid #eee; padding: 10px;">
                <div style="font-size: 22px; font-weight: bold; color: #1E3A5F;">{{ $avg_days_per_case }}</div>
                <div style="font-size: 9px; color: #888;">Prom. dias/caso</div>
            </td>
        </tr>
    </table>

    {{-- Dos columnas con tabla --}}
    <table style="border: none;">
        <tr>
            <td style="width: 48%; vertical-align: top; border: none; padding-right: 10px;">
                <div class="section">
                    <h2>Casos por Estado</h2>
                    <table>
                        <tr><th>Estado</th><th>Cant.</th><th>%</th></tr>
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
                        <tr><th>Prioridad</th><th>Cant.</th></tr>
                        @foreach($cases_by_priority as $priority => $count)
                            <tr>
                                <td>{{ $priority_labels[$priority] ?? $priority }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td style="width: 48%; vertical-align: top; border: none; padding-left: 10px;">
                <div class="section">
                    <h2>Casos por Tipo</h2>
                    <table>
                        <tr><th>Tipo</th><th>Cant.</th></tr>
                        @foreach($cases_by_type as $type => $count)
                            <tr>
                                <td>{{ $type }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <h2>Por Abogado</h2>
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
            </td>
        </tr>
    </table>

    {{-- Actividad --}}
    <div class="section">
        <h2>Actividad</h2>
        <table>
            <tr>
                <td style="text-align: center; border: 1px solid #eee; padding: 8px;">
                    <div style="font-size: 16px; font-weight: bold; color: #1E3A5F;">{{ $recent_events }}</div>
                    <div style="font-size: 9px; color: #888;">Actuaciones (30 dias)</div>
                </td>
                <td style="text-align: center; border: 1px solid #eee; padding: 8px;">
                    <div style="font-size: 16px; font-weight: bold; color: #1E3A5F;">{{ $completed_steps }}</div>
                    <div style="font-size: 9px; color: #888;">Pasos Completados</div>
                </td>
                <td style="text-align: center; border: 1px solid #eee; padding: 8px;">
                    <div style="font-size: 16px; font-weight: bold; color: #1E3A5F;">{{ $pending_steps }}</div>
                    <div style="font-size: 9px; color: #888;">Pasos Pendientes</div>
                </td>
                <td style="text-align: center; border: 1px solid #eee; padding: 8px;">
                    <div style="font-size: 16px; font-weight: bold; color: #1E3A5F;">{{ $total_users }}</div>
                    <div style="font-size: 9px; color: #888;">Usuarios</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Reporte generado por LegalWeb - {{ $firm->name }} - {{ $generated_at->format('d/m/Y H:i') }}<br>
        Este reporte es de uso interno. legalweb.com.co
    </div>
</body>
</html>
