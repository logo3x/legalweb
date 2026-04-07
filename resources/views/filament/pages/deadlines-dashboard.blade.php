<x-filament-panels::page>
    @php $data = $this->getDeadlinesData(); @endphp

    {{-- Resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-red-600">{{ $data['summary']['vencidos'] }}</div>
            <div class="text-sm text-red-600/80">Vencidos</div>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-orange-600">{{ $data['summary']['hoy'] }}</div>
            <div class="text-sm text-orange-600/80">Vencen hoy</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-yellow-600">{{ $data['summary']['urgentes'] }}</div>
            <div class="text-sm text-yellow-600/80">1-3 dias</div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $data['summary']['proximos'] }}</div>
            <div class="text-sm text-blue-600/80">4-7 dias</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ $data['summary']['total'] }}</div>
            <div class="text-sm text-gray-500">Total pendientes</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Timeline de vencimientos --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Timeline de Vencimientos</h3>
                    <p class="text-sm text-gray-500">Proximos 30 dias - alertas generadas automaticamente desde la Rama Judicial</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($data['reminders'] as $reminder)
                        <div class="p-4 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            {{-- Indicador de urgencia --}}
                            <div class="flex-shrink-0 mt-1">
                                @if($reminder['status'] === 'vencido')
                                    <div class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></div>
                                @elseif($reminder['status'] === 'hoy')
                                    <div class="w-3 h-3 rounded-full bg-orange-500 animate-pulse"></div>
                                @elseif($reminder['status'] === 'urgente')
                                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                @elseif($reminder['status'] === 'proximo')
                                    <div class="w-3 h-3 rounded-full bg-blue-400"></div>
                                @else
                                    <div class="w-3 h-3 rounded-full bg-gray-300"></div>
                                @endif
                            </div>

                            {{-- Contenido --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-sm">{{ $reminder['title'] }}</span>
                                    @if($reminder['type'] === 'audiencia')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Audiencia</span>
                                    @elseif($reminder['type'] === 'vencimiento')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Vencimiento</span>
                                    @endif
                                    @if($reminder['priority'] === 'urgente')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Urgente</span>
                                    @elseif($reminder['priority'] === 'alta')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">Alta</span>
                                    @endif
                                </div>
                                @if($reminder['description'])
                                    <p class="text-xs text-gray-500 mt-1 truncate">{{ Str::limit($reminder['description'], 120) }}</p>
                                @endif
                                @if($reminder['case_number'])
                                    <a href="{{ route('filament.admin.resources.legal-cases.view', $reminder['case_id']) }}"
                                       class="text-xs text-primary-600 hover:underline mt-1 inline-block">
                                        {{ $reminder['case_number'] }}
                                    </a>
                                @endif
                            </div>

                            {{-- Countdown --}}
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm font-mono {{ match($reminder['status']) {
                                    'vencido' => 'text-red-600 font-bold',
                                    'hoy' => 'text-orange-600 font-bold',
                                    'urgente' => 'text-yellow-600',
                                    default => 'text-gray-500',
                                } }}">
                                    @if($reminder['days'] < 0)
                                        {{ abs($reminder['days']) }}d vencido
                                    @elseif($reminder['days'] === 0)
                                        HOY
                                    @elseif($reminder['days'] === 1)
                                        MANANA
                                    @else
                                        {{ $reminder['days'] }} dias
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400">{{ $reminder['due_date'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-2 text-green-400"/>
                            <p class="font-medium">Sin vencimientos pendientes</p>
                            <p class="text-sm">Las alertas se crean automaticamente al sincronizar con la Rama Judicial.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Panel lateral --}}
        <div class="space-y-6">
            {{-- Actuaciones recientes --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold">Actuaciones Recientes</h3>
                    <p class="text-xs text-gray-500">Ultimos 15 dias desde Rama Judicial</p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-80 overflow-y-auto">
                    @forelse ($data['recentActuaciones'] as $act)
                        <div class="p-3">
                            <div class="text-sm font-medium">{{ $act['title'] }}</div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-xs text-gray-400">{{ $act['date'] }}</span>
                                @if($act['case_id'])
                                    <a href="{{ route('filament.admin.resources.legal-cases.view', $act['case_id']) }}"
                                       class="text-xs text-primary-600 hover:underline">{{ $act['case_number'] }}</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-sm text-gray-500">Sin actuaciones recientes</div>
                    @endforelse
                </div>
            </div>

            {{-- Casos sin sincronizar --}}
            @if($data['casesStale'] > 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-700">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600"/>
                        <span class="font-medium text-yellow-800 dark:text-yellow-200">{{ $data['casesStale'] }} caso(s) sin sincronizar</span>
                    </div>
                    <p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">
                        Estos casos no se han consultado en la Rama Judicial en mas de 48 horas. La sincronizacion automatica se ejecuta a las 3:00 AM.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
