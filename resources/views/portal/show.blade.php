@extends('layouts.portal')

@section('title', 'Caso ' . $case->case_number)

@section('content')
    {{-- Modal de aceptacion de terminos --}}
    @unless($hasAccepted)
        <div style="position:fixed;inset:0;background:rgba(30,58,95,0.95);backdrop-filter:blur(20px);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
            <div style="background: #fff; border-radius: 16px; max-width: 480px; width: 100%; padding: 32px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #3A86FF, #1E3A5F); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <svg style="width: 28px; height: 28px; color: #fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h2 style="font-size: 20px; font-weight: 700; color: #1E3A5F; margin: 0;">Autorizacion de Acceso</h2>
                    <p style="font-size: 13px; color: #6b7280; margin: 4px 0 0;">{{ $case->client->full_name }}</p>
                </div>

                <div style="font-size: 13px; color: #4b5563; max-height: 240px; overflow-y: auto; margin-bottom: 20px; line-height: 1.6;">
                    <p>Al acceder a este portal, usted acepta:</p>
                    <ul style="padding-left: 20px; margin: 8px 0;">
                        <li style="margin-bottom: 6px;">La informacion es <strong>confidencial</strong> y esta protegida por el secreto profesional abogado-cliente.</li>
                        <li style="margin-bottom: 6px;">Autoriza el tratamiento de sus datos personales conforme a la <strong>Ley 1581 de 2012</strong>.</li>
                        <li style="margin-bottom: 6px;">Los estados y avances son <strong>informativos</strong> y pueden estar sujetos a actualizacion.</li>
                        <li style="margin-bottom: 6px;">Se compromete a <strong>no compartir</strong> el enlace de acceso con terceros.</li>
                        <li>El acceso queda registrado con su IP y fecha para <strong>trazabilidad</strong>.</li>
                    </ul>
                    <p style="margin-top: 12px;">
                        <a href="{{ route('portal.terms') }}" target="_blank" style="color: #3A86FF; text-decoration: underline;">Terminos y Condiciones</a> |
                        <a href="{{ route('portal.privacy') }}" target="_blank" style="color: #3A86FF; text-decoration: underline;">Politica de Privacidad</a>
                    </p>
                </div>

                <form action="{{ route('portal.accept', $case->portal_token) }}" method="POST">
                    @csrf
                    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #3A86FF, #1E3A5F); color: #fff; font-weight: 600; padding: 14px; border-radius: 10px; border: none; font-size: 14px; cursor: pointer;">
                        Acepto los terminos y condiciones
                    </button>
                </form>
            </div>
        </div>
    @endunless

    @if($hasAccepted)
    {{-- Encabezado del caso --}}
    <div style="background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px;">
            <div style="flex: 1; min-width: 280px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                    <h1 style="font-size: 22px; font-weight: 700; color: #1E3A5F; margin: 0;">{{ $case->title }}</h1>
                    @php
                        $statusConfig = [
                            'abierto' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Abierto'],
                            'en_progreso' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'En Progreso'],
                            'en_espera' => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'En Espera'],
                            'cerrado' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Cerrado'],
                            'archivado' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Archivado'],
                        ];
                        $sc = $statusConfig[$case->status] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => $case->status];
                    @endphp
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; background: {{ $sc['bg'] }}; color: {{ $sc['color'] }};">{{ $sc['label'] }}</span>
                </div>
                <div style="font-size: 13px; color: #6b7280;">
                    Caso <strong>{{ $case->case_number }}</strong>
                    @if($case->external_case_number)
                        &middot; Radicado <strong>{{ $case->external_case_number }}</strong>
                    @endif
                    &middot; {{ $case->caseType->name }}
                </div>
            </div>
            <div style="text-align: right; font-size: 13px; color: #6b7280;">
                @if($firmLogo)
                    <img src="{{ $firmLogo }}" alt="Logo" style="height: 40px; width: 40px; border-radius: 8px; object-fit: cover; margin-left: auto; margin-bottom: 4px;">
                @endif
                @if($case->user->firm)
                    <div style="font-weight: 600; color: #1E3A5F;">{{ $case->user->firm->name }}</div>
                @endif
                <div>Abogado: {{ $case->user->name }}</div>
                @if($case->started_at)
                    <div>Inicio: {{ $case->started_at->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
        {{-- Datos del cliente --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <div style="width: 36px; height: 36px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 18px; height: 18px; color: #3A86FF;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <span style="font-size: 13px; font-weight: 600; color: #1E3A5F;">Sus Datos</span>
            </div>
            <div style="font-size: 12px; color: #4b5563;">
                <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #f3f4f6;"><span style="color: #9ca3af;">Nombre</span><span style="font-weight: 500;">{{ $case->client->full_name }}</span></div>
                <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #f3f4f6;"><span style="color: #9ca3af;">Documento</span><span style="font-weight: 500;">{{ $case->client->document_type }} {{ $case->client->document_number }}</span></div>
                @if($case->client->phone)
                <div style="display: flex; justify-content: space-between; padding: 4px 0;"><span style="color: #9ca3af;">Telefono</span><span style="font-weight: 500;">{{ $case->client->phone }}</span></div>
                @endif
            </div>
        </div>

        {{-- Juzgado --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <div style="width: 36px; height: 36px; background: #f5f3ff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 18px; height: 18px; color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span style="font-size: 13px; font-weight: 600; color: #1E3A5F;">Juzgado</span>
            </div>
            <div style="font-size: 12px; color: #4b5563;">
                <div style="padding: 4px 0; border-bottom: 1px solid #f3f4f6;"><span style="color: #9ca3af;">Despacho</span><div style="font-weight: 500; margin-top: 2px;">{{ $case->court ?? 'Sin asignar' }}</div></div>
                <div style="padding: 4px 0;"><span style="color: #9ca3af;">Juez</span><div style="font-weight: 500; margin-top: 2px;">{{ $case->judge ?? 'Sin asignar' }}</div></div>
            </div>
        </div>

        {{-- Progreso --}}
        @if($case->caseFlow)
            @php
                $totalSteps = $case->flowProgress->count();
                $completedSteps = $case->flowProgress->where('status', 'completado')->count();
                $percentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
            @endphp
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 36px; height: 36px; background: #f0fdf4; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 18px; height: 18px; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span style="font-size: 13px; font-weight: 600; color: #1E3A5F;">Progreso</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                    <span style="color: #6b7280;">{{ $completedSteps }} de {{ $totalSteps }} etapas</span>
                    <span style="font-weight: 700; color: #16a34a;">{{ $percentage }}%</span>
                </div>
                <div style="width: 100%; height: 10px; background: #f3f4f6; border-radius: 999px; overflow: hidden;">
                    <div style="width: {{ $percentage }}%; height: 100%; background: linear-gradient(90deg, #16a34a, #22c55e); border-radius: 999px; transition: width 0.5s;"></div>
                </div>
                <div style="font-size: 11px; color: #9ca3af; margin-top: 6px;">{{ $case->caseFlow->name }}</div>
            </div>
        @endif
    </div>

    {{-- Flujo de Proceso --}}
    @if($case->caseFlow && $case->flowProgress->isNotEmpty())
        <div style="background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 20px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 16px 0;">Flujo del Proceso</h2>
            @foreach($case->flowProgress->sortBy('flowStep.order') as $progress)
                @php
                    $step = $progress->flowStep;
                    $isCompleted = $progress->status === 'completado';
                    $isInProgress = $progress->status === 'en_progreso';
                    $isOmitted = $progress->status === 'omitido';
                @endphp
                <div style="display: flex; align-items: flex-start; gap: 14px; {{ $isCompleted || $isInProgress ? '' : 'opacity: 0.4;' }} {{ !$loop->last ? 'margin-bottom: 4px;' : '' }}">
                    {{-- Linea vertical + circulo --}}
                    <div style="display: flex; flex-direction: column; align-items: center; flex-shrink: 0;">
                        @if($isCompleted)
                            <div style="width: 28px; height: 28px; background: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 14px; height: 14px; color: #fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @elseif($isInProgress)
                            <div style="width: 28px; height: 28px; background: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 4px rgba(245,158,11,0.2);">
                                <div style="width: 10px; height: 10px; background: #fff; border-radius: 50%;"></div>
                            </div>
                        @elseif($isOmitted)
                            <div style="width: 28px; height: 28px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 14px; height: 14px; color: #fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                        @else
                            <div style="width: 28px; height: 28px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 11px; font-weight: 700; color: #9ca3af;">{{ $step->order }}</span>
                            </div>
                        @endif
                        @if(!$loop->last)
                            <div style="width: 2px; height: 24px; background: {{ $isCompleted ? '#16a34a' : '#e5e7eb' }};"></div>
                        @endif
                    </div>

                    {{-- Contenido --}}
                    <div style="flex: 1; padding-bottom: 4px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 13px; font-weight: 600; color: {{ $isCompleted ? '#6b7280' : '#1E3A5F' }}; {{ $isCompleted ? 'text-decoration: line-through;' : '' }}">{{ $step->name }}</span>
                            @if($isCompleted && $progress->completed_at)
                                <span style="font-size: 11px; color: #9ca3af;">{{ $progress->completed_at->format('d/m/Y') }}</span>
                            @elseif($isInProgress)
                                <span style="font-size: 10px; font-weight: 600; color: #92400e; background: #fef3c7; padding: 2px 8px; border-radius: 4px;">En curso</span>
                            @elseif($step->days_limit)
                                <span style="font-size: 11px; color: #9ca3af;">Plazo: {{ $step->days_limit }}d</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Ultimas Actuaciones --}}
    @if($case->events->isNotEmpty())
        <div style="background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 20px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 16px 0;">Ultimas Actuaciones</h2>
            @foreach($case->events->take(15) as $event)
                @php
                    $typeConfig = [
                        'audiencia' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Audiencia'],
                        'sentencia' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Sentencia'],
                        'notificacion' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Notificacion'],
                        'actuacion' => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Actuacion'],
                        'memorial' => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'label' => 'Memorial'],
                        'auto' => ['bg' => '#f5f3ff', 'color' => '#6d28d9', 'label' => 'Auto'],
                    ];
                    $tc = $typeConfig[$event->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => $event->event_type];
                @endphp
                <div style="display: flex; align-items: flex-start; gap: 14px; {{ !$loop->last ? 'padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid #f3f4f6;' : '' }}">
                    <div style="flex-shrink: 0; text-align: center; min-width: 44px;">
                        <div style="font-size: 18px; font-weight: 700; color: #1E3A5F; line-height: 1;">{{ $event->event_date->format('d') }}</div>
                        <div style="font-size: 10px; color: #9ca3af; text-transform: uppercase;">{{ $event->event_date->format('M') }}</div>
                        <div style="font-size: 10px; color: #9ca3af;">{{ $event->event_date->format('Y') }}</div>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; background: {{ $tc['bg'] }}; color: {{ $tc['color'] }};">{{ $tc['label'] }}</span>
                            @if($event->is_milestone)
                                <span style="font-size: 10px; color: #f59e0b; font-weight: 600;">★ Hito</span>
                            @endif
                        </div>
                        <div style="font-size: 13px; font-weight: 500; color: #1f2937;">{{ $event->title }}</div>
                        @if($event->description && !str_contains($event->description, 'Sincronizado'))
                            <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">{{ Str::limit($event->description, 100) }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Documentos del caso --}}
    @if($case->documents->isNotEmpty())
        @php
            $docsClient = $case->documents->where('responsible', 'cliente');
            $docsOther = $case->documents->where('responsible', '!=', 'cliente');
            $pendingClient = $docsClient->whereIn('status', ['pendiente', 'solicitado', 'en_tramite']);
        @endphp

        <div style="background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 20px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0 0 4px 0;">Documentos del Proceso</h2>
            <p style="font-size: 12px; color: #6b7280; margin: 0 0 16px 0;">Estado de la documentacion necesaria para su caso.</p>

            {{-- Alerta si hay documentos pendientes del cliente --}}
            @if($pendingClient->isNotEmpty())
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <svg style="width: 18px; height: 18px; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        <span style="font-size: 13px; font-weight: 600; color: #92400e;">Documentos que usted debe aportar ({{ $pendingClient->count() }})</span>
                    </div>
                    <p style="font-size: 12px; color: #a16207; margin: 0;">Los siguientes documentos son necesarios para su proceso. Por favor contacte a su abogado para hacerlos llegar.</p>
                </div>
            @endif

            {{-- Lista de documentos --}}
            @foreach($case->documents as $doc)
                @php
                    $statusConfig = [
                        'pendiente' => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Pendiente'],
                        'solicitado' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Solicitado'],
                        'en_tramite' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'En tramite'],
                        'recibido' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Recibido'],
                        'no_aplica' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'No aplica'],
                    ];
                    $sc = $statusConfig[$doc->status] ?? $statusConfig['pendiente'];

                    $responsibleLabels = [
                        'cliente' => 'Usted (cliente)',
                        'abogado' => 'Su abogado',
                        'firma' => 'La firma',
                        'contraparte' => 'Contraparte',
                        'juzgado' => 'Juzgado',
                        'otro' => 'Otro',
                    ];
                    $respLabel = $responsibleLabels[$doc->responsible ?? 'otro'] ?? 'Otro';
                @endphp

                <div style="display: flex; align-items: flex-start; gap: 14px; padding: 12px; border: 1px solid #f3f4f6; border-radius: 10px; margin-bottom: 8px; background: {{ $doc->responsible === 'cliente' && in_array($doc->status, ['pendiente', 'solicitado']) ? '#fffbeb' : '#fff' }};">
                    <div style="flex-shrink: 0; margin-top: 2px;">
                        @if($doc->status === 'recibido')
                            <div style="width: 32px; height: 32px; background: #dcfce7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 18px; height: 18px; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @elseif(in_array($doc->status, ['solicitado', 'en_tramite']))
                            <div style="width: 32px; height: 32px; background: #fef3c7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 18px; height: 18px; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        @else
                            <div style="width: 32px; height: 32px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 18px; height: 18px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        @endif
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                            <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $doc->name }}</span>
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; background: {{ $sc['bg'] }}; color: {{ $sc['color'] }};">{{ $sc['label'] }}</span>
                        </div>
                        @if($doc->description)
                            <p style="font-size: 12px; color: #6b7280; margin: 2px 0;">{{ Str::limit($doc->description, 150) }}</p>
                        @endif
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 4px; font-size: 11px; color: #9ca3af;">
                            <span><strong style="color: #6b7280;">Responsable:</strong> {{ $respLabel }}</span>
                            @if($doc->entity)
                                <span><strong style="color: #6b7280;">Entidad:</strong> {{ $doc->entity }}</span>
                            @endif
                            @if($doc->due_date && $doc->status !== 'recibido')
                                <span style="color: {{ $doc->due_date->isPast() ? '#dc2626' : '#9ca3af' }};">
                                    <strong style="color: {{ $doc->due_date->isPast() ? '#dc2626' : '#6b7280' }};">Necesario antes de:</strong> {{ $doc->due_date->format('d/m/Y') }}
                                </span>
                            @endif
                            @if($doc->received_at)
                                <span><strong style="color: #6b7280;">Recibido:</strong> {{ $doc->received_at->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Resumen --}}
            @php
                $totalDocs = $case->documents->count();
                $recibidos = $case->documents->where('status', 'recibido')->count();
                $porcentaje = $totalDocs > 0 ? round(($recibidos / $totalDocs) * 100) : 0;
            @endphp
            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 12px; color: #6b7280;"><strong>{{ $recibidos }}</strong> de <strong>{{ $totalDocs }}</strong> documentos recibidos</span>
                <span style="font-size: 12px; font-weight: 700; color: #16a34a;">{{ $porcentaje }}%</span>
            </div>
            <div style="width: 100%; height: 6px; background: #f3f4f6; border-radius: 999px; overflow: hidden; margin-top: 6px;">
                <div style="width: {{ max(3, $porcentaje) }}%; height: 100%; background: linear-gradient(90deg, #16a34a, #22c55e); border-radius: 999px;"></div>
            </div>
        </div>
    @endif

    {{-- Contacto del abogado --}}
    <div style="background: linear-gradient(135deg, #eff6ff, #f5f3ff); border-radius: 12px; border: 1px solid #c7d2fe; padding: 20px; text-align: center;">
        <div style="font-size: 13px; color: #4338ca; font-weight: 600; margin-bottom: 4px;">¿Tiene preguntas sobre su caso?</div>
        <div style="font-size: 12px; color: #6b7280;">
            Contacte a su abogado <strong>{{ $case->user->name }}</strong>
            @if($case->user->email)
                &middot; <a href="mailto:{{ $case->user->email }}" style="color: #3A86FF; text-decoration: none;">{{ $case->user->email }}</a>
            @endif
        </div>
    </div>
    @endif {{-- end hasAccepted --}}
@endsection
