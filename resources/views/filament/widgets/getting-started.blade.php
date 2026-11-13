@php
    $steps = $this->getSteps();
    $done = collect($steps)->where('done', true)->count();
    $total = count($steps);
    $percent = $total ? round($done / $total * 100) : 100;
    $next = collect($steps)->firstWhere('done', false);
@endphp

<x-filament-widgets::widget>
    <div style="background: linear-gradient(135deg, #EFF6FF 0%, #ffffff 60%); border: 1px solid #DBEAFE; border-radius: 12px; padding: 20px 24px; font-family: Inter, system-ui, sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <div>
                <div style="font-size: 16px; font-weight: 700; color: #1E3A5F;">Primeros pasos en LegalWeb</div>
                <div style="font-size: 13px; color: #6b7280;">{{ $done }} de {{ $total }} completados. En 10 minutos deja LegalWeb trabajando por usted.</div>
            </div>
            <button type="button" wire:click="dismiss" style="font-size: 12px; color: #64748b; background: none; border: none; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>

        <div style="width: 100%; height: 8px; background: #E2E8F0; border-radius: 999px; overflow: hidden; margin-bottom: 16px;">
            <div style="width: {{ max(4, $percent) }}%; height: 100%; background: #3A86FF; border-radius: 999px;"></div>
        </div>

        <div class="lw-steps-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px;">
            @foreach($steps as $step)
                @php $isNext = $next && $step['title'] === $next['title']; @endphp
                <div style="display: flex; gap: 10px; padding: 12px; border-radius: 10px; background: #fff; border: 1px solid {{ $isNext ? '#3A86FF' : '#eef2f7' }}; {{ $step['done'] ? 'opacity: .65;' : '' }}">
                    <div style="flex-shrink: 0; width: 22px; height: 22px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: {{ $step['done'] ? '#10B981' : '#E2E8F0' }}; color: #fff; margin-top: 1px;">
                        @if($step['done'])
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 13.5px; font-weight: 600; color: #111827; {{ $step['done'] ? 'text-decoration: line-through;' : '' }}">{{ $step['title'] }}</div>
                        @unless($step['done'])
                            <div style="font-size: 12px; color: #6b7280; line-height: 1.45; margin: 3px 0 6px;">{{ $step['description'] }}</div>
                            <a href="{{ $step['url'] }}" style="font-size: 12.5px; font-weight: 600; color: #2563EB; text-decoration: none;">{{ $step['cta'] }} &rarr;</a>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
