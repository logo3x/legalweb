<x-filament-panels::page>
    @php
        $members = $this->getMembers();
        $invitations = $this->getPendingInvitations();
        $firmCases = $this->getFirmCases();
    @endphp

    {{-- Miembros actuales --}}
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827;">Miembros del Equipo</h3>
        </div>

        @foreach($members as $member)
            <div style="border-bottom: 1px solid #f3f4f6;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px;">
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
                        @if(!$member->isAdmin() && $member->id !== auth()->id())
                            <span style="font-size: 12px; color: #6b7280;">
                                {{ $member->casePermissions->count() }} caso(s) asignados
                            </span>
                        @elseif($member->isAdmin())
                            <span style="font-size: 12px; color: #10b981;">Acceso total</span>
                        @endif
                    </div>
                </div>

                {{-- Asignacion de casos para colaboradores --}}
                @if(!$member->isAdmin() && $member->id !== auth()->id() && $firmCases->isNotEmpty())
                    <div style="padding: 0 20px 14px 72px;">
                        <details>
                            <summary style="font-size: 13px; color: #3A86FF; cursor: pointer; font-weight: 500;">
                                Gestionar casos asignados
                            </summary>
                            <div style="margin-top: 10px;">
                                <form method="POST" action="{{ url('/admin/team/assign-cases/' . $member->id) }}">
                                    @csrf
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 8px; margin-bottom: 12px;">
                                        @foreach($firmCases as $case)
                                            @php
                                                $cp = $member->casePermissions->firstWhere('legal_case_id', $case->id);
                                                $isAssigned = (bool)$cp;
                                            @endphp
                                            <div style="border: 1px solid {{ $isAssigned ? '#3A86FF' : '#e5e7eb' }}; border-radius: 8px; padding: 10px; background: {{ $isAssigned ? '#f0f7ff' : '#fff' }};">
                                                <label style="display: flex; align-items: start; gap: 8px; cursor: pointer;">
                                                    <input type="checkbox" name="cases[{{ $case->id }}][enabled]" value="1"
                                                        {{ $isAssigned ? 'checked' : '' }}
                                                        style="margin-top: 3px;"
                                                        onchange="this.closest('div').querySelector('.perms').style.display = this.checked ? 'block' : 'none'">
                                                    <div style="flex: 1;">
                                                        <div style="font-size: 13px; font-weight: 600; color: #111827;">{{ $case->case_number }}</div>
                                                        <div style="font-size: 12px; color: #6b7280;">{{ Str::limit($case->title, 40) }}</div>
                                                    </div>
                                                </label>
                                                <div class="perms" style="margin-top: 8px; padding-left: 24px; {{ $isAssigned ? '' : 'display: none;' }}">
                                                    @foreach(\App\Models\CasePermission::CASE_PERMISSIONS as $key => $label)
                                                        <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; color: #374151; margin-bottom: 2px;">
                                                            <input type="checkbox" name="cases[{{ $case->id }}][permissions][]" value="{{ $key }}"
                                                                {{ $cp && in_array($key, $cp->permissions ?? []) ? 'checked' : '' }}>
                                                            {{ $label }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="submit" style="padding: 8px 16px; background: #3A86FF; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                        Guardar asignacion
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                @endif
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
                            Invitado por {{ $invitation->invitedBy->name }} &middot;
                            {{ count($invitation->permissions['case_ids'] ?? []) }} caso(s) pre-asignados &middot;
                            Expira {{ $invitation->expires_at->format('d/m/Y') }}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; background: #fef3c7; color: #92400e;">
                            Pendiente - {{ ucfirst($invitation->role) }}
                        </span>
                        <form method="POST" action="{{ url('/admin/team/delete-invite/' . $invitation->id) }}" onsubmit="return confirm('Cancelar esta invitacion?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="padding: 4px 10px; background: #fee2e2; color: #991b1b; border: none; border-radius: 6px; font-size: 11px; cursor: pointer;">
                                Cancelar
                            </button>
                        </form>
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
                <strong>Como funciona:</strong><br>
                1. Invite un colaborador con su correo de Google<br>
                2. Cuando inicie sesion se vinculara automaticamente a su firma<br>
                3. Despliegue "Gestionar casos asignados" para asignar casos especificos con permisos individuales<br>
                4. El colaborador solo vera los casos que usted le asigne<br>
                5. El administrador siempre tiene acceso total a todos los casos
            </div>
        </div>
    </div>
</x-filament-panels::page>
