<x-filament-panels::page>
    @php $data = $this->getDeadlinesData(); @endphp
    @include('filament.partials.deadlines-content', ['data' => $data])
</x-filament-panels::page>
