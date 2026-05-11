<div>
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; font-size: 13px; color: #1e40af;">
        Se enviar&aacute; el correo a <strong>{{ $count }}</strong> destinatario(s).
    </div>

    @if($count === 0)
        <div style="text-align: center; padding: 32px; color: #9ca3af;">
            <p style="font-weight: 500;">Ning&uacute;n usuario coincide con los filtros de audiencia.</p>
            <p style="font-size: 13px;">Revise los criterios de seleccion en el formulario.</p>
        </div>
    @else
        <div style="max-height: 420px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead style="background: #f9fafb; position: sticky; top: 0;">
                    <tr>
                        <th style="text-align: left; padding: 10px 12px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Nombre</th>
                        <th style="text-align: left; padding: 10px 12px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Correo</th>
                        <th style="text-align: left; padding: 10px 12px; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Firma</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 8px 12px; color: #111827;">{{ $user->name }}</td>
                            <td style="padding: 8px 12px; color: #6b7280;">{{ $user->email }}</td>
                            <td style="padding: 8px 12px; color: #6b7280;">{{ $user->firm?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
