<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="max-w-3xl">
        @php
            $firm = auth()->user()->firm;
            $isOnboarding = $firm && !$firm->onboarding_completed;
        @endphp

        @if($isOnboarding)
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg width="24" height="24" style="min-width:24px;min-height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-blue-800">Bienvenido a LegalWeb</h3>
                        <p class="text-sm text-blue-700 mt-1">
                            Complete los datos de su firma para personalizar la plataforma. Hemos cargado 3 casos de ejemplo, 3 clientes y los flujos de proceso basados en la legislacion colombiana para que pueda explorar todas las funcionalidades.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button type="submit" size="lg">
                    {{ $isOnboarding ? 'Guardar y comenzar' : 'Guardar cambios' }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
