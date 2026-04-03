@extends('layouts.portal')

@section('title', 'Caso ' . $case->case_number)

@section('content')
    {{-- Modal de aceptacion de terminos --}}
    @unless($hasAccepted)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                <div class="text-center mb-4">
                    <svg class="w-12 h-12 text-blue-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <h2 class="text-xl font-bold text-gray-800">Autorizacion de Acceso</h2>
                </div>

                <div class="text-sm text-gray-600 space-y-3 max-h-64 overflow-y-auto mb-4">
                    <p>Estimado(a) <strong>{{ $case->client->full_name }}</strong>,</p>

                    <p>Al acceder a este portal, usted acepta los siguientes terminos:</p>

                    <ul class="list-disc pl-5 space-y-1">
                        <li>La informacion aqui presentada es <strong>confidencial</strong> y esta protegida por el secreto profesional abogado-cliente.</li>
                        <li>Usted autoriza el tratamiento de sus datos personales conforme a la <strong>Ley 1581 de 2012</strong> y sus decretos reglamentarios.</li>
                        <li>Esta plataforma es una <strong>herramienta tecnologica de gestion</strong> que no sustituye el criterio profesional del abogado.</li>
                        <li>Los estados y avances mostrados son <strong>informativos</strong> y pueden estar sujetos a actualizacion.</li>
                        <li>Usted se compromete a <strong>no compartir</strong> el enlace de acceso con terceros no autorizados.</li>
                        <li>El acceso queda registrado con su direccion IP y fecha para fines de <strong>trazabilidad</strong>.</li>
                    </ul>

                    <p>Para mas informacion, consulte nuestros
                        <a href="{{ route('portal.terms') }}" class="text-blue-600 underline" target="_blank">Terminos y Condiciones</a> y
                        <a href="{{ route('portal.privacy') }}" class="text-blue-600 underline" target="_blank">Politica de Privacidad y Tratamiento de Datos</a>.
                    </p>
                </div>

                <form action="{{ route('portal.accept', $case->portal_token) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-blue-700 transition">
                        Acepto los terminos y condiciones
                    </button>
                </form>
            </div>
        </div>
    @endunless

    {{-- Encabezado del caso --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $case->title }}</h1>
                    @php
                        $statusColors = ['abierto' => 'blue', 'en_progreso' => 'yellow', 'en_espera' => 'gray', 'cerrado' => 'green', 'archivado' => 'red'];
                        $statusLabels = ['abierto' => 'Abierto', 'en_progreso' => 'En Progreso', 'en_espera' => 'En Espera', 'cerrado' => 'Cerrado', 'archivado' => 'Archivado'];
                        $color = $statusColors[$case->status] ?? 'gray';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $color }}-100 text-{{ $color }}-800">
                        {{ $statusLabels[$case->status] ?? $case->status }}
                    </span>
                </div>
                <p class="text-gray-500">
                    Caso <strong>{{ $case->case_number }}</strong>
                    @if($case->external_case_number)
                        &middot; Radicado {{ $case->external_case_number }}
                    @endif
                    &middot; {{ $case->caseType->name }}
                </p>
            </div>
            <div class="text-right text-sm text-gray-500 flex flex-col items-end gap-2">
                @if($firmLogo)
                    <img src="{{ $firmLogo }}" alt="Logo Firma" class="h-12 w-12 rounded-lg object-cover">
                @endif
                @if($case->user->firm)
                    <p class="font-semibold text-gray-700">{{ $case->user->firm->name }}</p>
                @endif
                <p>Abogado: <strong>{{ $case->user->name }}</strong></p>
                @if($case->started_at)
                    <p>Inicio: {{ $case->started_at->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Resumen en tarjetas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-700">Sus Datos</h3>
            </div>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Nombre</dt><dd class="font-medium">{{ $case->client->full_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Documento</dt><dd class="font-medium">{{ $case->client->document_type }} {{ $case->client->document_number }}</dd></div>
                @if($case->client->phone)
                    <div class="flex justify-between"><dt class="text-gray-500">Telefono</dt><dd class="font-medium">{{ $case->client->phone }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-700">Juzgado</h3>
            </div>
            <dl class="text-sm space-y-1">
                <div><dt class="text-gray-500">Despacho</dt><dd class="font-medium">{{ $case->court ?? 'Sin asignar' }}</dd></div>
                <div><dt class="text-gray-500">Juez</dt><dd class="font-medium">{{ $case->judge ?? 'Sin asignar' }}</dd></div>
            </dl>
        </div>

        @if($case->caseFlow)
            @php
                $totalSteps = $case->flowProgress->count();
                $completedSteps = $case->flowProgress->where('status', 'completado')->count();
                $percentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-700">Progreso</h3>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500">{{ $completedSteps }} de {{ $totalSteps }} pasos</span>
                        <span class="font-bold text-green-600">{{ $percentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                <p class="text-xs text-gray-500">{{ $case->caseFlow->name }}</p>
            </div>
        @endif
    </div>

    {{-- Flujo de Proceso --}}
    @if($case->caseFlow && $case->flowProgress->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Flujo del Proceso</h2>
            <div class="space-y-3">
                @foreach($case->flowProgress->sortBy('flowStep.order') as $progress)
                    @php
                        $step = $progress->flowStep;
                        $isCompleted = $progress->status === 'completado';
                        $isInProgress = $progress->status === 'en_progreso';
                        $isOmitted = $progress->status === 'omitido';
                    @endphp
                    <div class="flex items-start gap-4 {{ $isCompleted ? '' : ($isInProgress ? '' : 'opacity-50') }}">
                        <div class="flex-shrink-0 mt-1">
                            @if($isCompleted)
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @elseif($isInProgress)
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center animate-pulse">
                                    <div class="w-3 h-3 bg-white rounded-full"></div>
                                </div>
                            @elseif($isOmitted)
                                <div class="w-8 h-8 bg-red-400 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold text-gray-500">{{ $step->order }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 pb-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-gray-800 {{ $isCompleted ? 'line-through text-gray-500' : '' }}">
                                    {{ $step->name }}
                                </h4>
                                @if($isCompleted && $progress->completed_at)
                                    <span class="text-xs text-gray-400">{{ $progress->completed_at->format('d/m/Y') }}</span>
                                @elseif($isInProgress)
                                    <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-1 rounded">En curso</span>
                                @elseif($step->days_limit)
                                    <span class="text-xs text-gray-400">Plazo: {{ $step->days_limit }} dias</span>
                                @endif
                            </div>
                            @if($step->description)
                                <p class="text-sm text-gray-500 mt-1">{{ $step->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Ultimas Actuaciones --}}
    @if($case->events->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Ultimas Actuaciones</h2>
            <div class="space-y-4">
                @foreach($case->events->take(10) as $event)
                    @php
                        $typeColors = ['audiencia' => 'yellow', 'sentencia' => 'green', 'notificacion' => 'blue', 'actuacion' => 'gray', 'memorial' => 'indigo', 'auto' => 'purple'];
                        $typeLabels = ['audiencia' => 'Audiencia', 'sentencia' => 'Sentencia', 'notificacion' => 'Notificacion', 'actuacion' => 'Actuacion', 'memorial' => 'Memorial', 'auto' => 'Auto'];
                        $eColor = $typeColors[$event->event_type] ?? 'gray';
                    @endphp
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 text-center">
                            <div class="text-sm font-bold text-gray-800">{{ $event->event_date->format('d') }}</div>
                            <div class="text-xs text-gray-500 uppercase">{{ $event->event_date->translatedFormat('M Y') }}</div>
                        </div>
                        <div class="flex-1 pb-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $eColor }}-100 text-{{ $eColor }}-800">
                                    {{ $typeLabels[$event->event_type] ?? $event->event_type }}
                                </span>
                                @if($event->is_milestone)
                                    <span class="text-xs text-orange-500 font-semibold">Hito</span>
                                @endif
                            </div>
                            <h4 class="font-medium text-gray-800">{{ $event->title }}</h4>
                            @if($event->description)
                                <p class="text-sm text-gray-500 mt-1">{{ $event->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
