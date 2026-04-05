<x-filament-panels::page>
    @php
        $members = $this->getMembers();
        $invitations = $this->getPendingInvitations();
        $isAdmin = auth()->user()->isAdmin();
    @endphp

    {{-- Miembros actuales --}}
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">Miembros del Equipo</h3>
        </div>

        @foreach($members as $member)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #f9fafb;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="{{ $member->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) . '&background=3A86FF&color=fff&size=40' }}"
                         alt="{{ $member->name }}" style="width: 40px; height: 40px; border-radius: 999px; object-fit: cover;">
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: #111827;">
                            {{ $member->name }}
                            @if($member->id === auth()->id())
                                <span style="font-size: 11px; color: #6b7280; font-weight: 400;">(tu)</span>
                            @endif
                        </div>
                        <div style="font-size: 13px; color: #6b7280;">{{ $member->email }}</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 500;
                        {{ $member->role === 'admin' ? 'background: #fef3c7; color: #92400e;' : ($member->role === 'superadmin' ? 'background: #fee2e2; color: #991b1b;' : 'background: #dbeafe; color: #1e40af;') }}">
                        {{ $member->role === 'admin' ? 'Administrador' : ($member->role === 'superadmin' ? 'Super Admin' : ucfirst($member->role)) }}
                    </span>
                    @if($member->google_id)
                        <span style="display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 500; background: #dcfce7; color: #166534;">Google</span>
                    @endif
                </div>
            </div>
        @endforeach

        @if($members->isEmpty())
            <div style="padding: 40px; text-align: center; color: #9ca3af;">No hay miembros en el equipo.</div>
        @endif
    </div>

    {{-- Invitaciones pendientes --}}
    @if($invitations->isNotEmpty())
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; margin-top: 20px;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6;">
                <h3 style="font-size: 16px; font-weight: 600; color: #111827;">Invitaciones Pendientes</h3>
            </div>

            @foreach($invitations as $invitation)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #f9fafb;">
                    <div>
                        <div style="font-size: 14px; font-weight: 500; color: #111827;">{{ $invitation->email }}</div>
                        <div style="font-size: 12px; color: #9ca3af;">
                            Invitado por {{ $invitation->invitedBy->name }} &middot; Expira {{ $invitation->expires_at->format('d/m/Y') }}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; background: #fef3c7; color: #92400e;">
                            Pendiente - {{ ucfirst($invitation->role) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Instrucciones --}}
    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 16px 20px; margin-top: 20px;">
        <div style="display: flex; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="#0284c7" viewBox="0 0 24 24" style="min-width: 20px; margin-top: 2px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div style="font-size: 13px; color: #0369a1;">
                <strong>Como funciona:</strong> Al invitar un colaborador, se crea una invitacion vinculada a su correo de Google.
                Cuando el colaborador inicie sesion con Google en LegalWeb, automaticamente se vinculara a su firma con los permisos que usted asigno.
                La invitacion expira en 7 dias.
            </div>
        </div>
    </div>
</x-filament-panels::page>
