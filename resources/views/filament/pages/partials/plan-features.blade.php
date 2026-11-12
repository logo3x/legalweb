{{-- Lista de funciones de un plan: $features = [[incluida (bool), texto], ...] --}}
<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 9px;">
    @foreach($features as [$included, $label])
        <li style="display: flex; align-items: flex-start; gap: 8px; font-size: 13.5px; color: {{ $included ? '#374151' : '#9ca3af' }};">
            @if($included)
                <svg width="16" height="16" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 2px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            @else
                <svg width="16" height="16" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 2px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            @endif
            <span style="{{ $included ? '' : 'text-decoration: line-through;' }}">{{ $label }}</span>
        </li>
    @endforeach
</ul>
