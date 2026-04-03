<x-filament-panels::page></script>

    @php
        $plans = $this->getPlans();
        $currentPlan = $this->getCurrentPlan();
        $currentSlug = $currentPlan?->slug ?? 'gratuito';
        $firm = auth()->user()->firm;
    @endphp

    <div x-data="{ billing: 'monthly' }">
        {{-- Switch mensual/semestral --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <p style="font-size: 14px; color: #6b7280; margin-bottom: 12px;">Seleccione su ciclo de facturacion</p>
            <div style="display: inline-flex; background: #f3f4f6; border-radius: 999px; padding: 4px; gap: 2px;">
                <button
                    x-on:click="billing = 'monthly'"
                    x-bind:style="billing === 'monthly' ? 'background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.1);color:#111827;font-weight:600' : 'background:transparent;color:#6b7280;font-weight:400'"
                    style="padding: 8px 24px; border-radius: 999px; font-size: 14px; border: none; cursor: pointer;"
                    type="button">
                    Mensual
                </button>
                <button
                    x-on:click="billing = 'biannual'"
                    x-bind:style="billing === 'biannual' ? 'background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.1);color:#111827;font-weight:600' : 'background:transparent;color:#6b7280;font-weight:400'"
                    style="padding: 8px 24px; border-radius: 999px; font-size: 14px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"
                    type="button">
                    Semestral
                    <span style="background: #dcfce7; color: #166534; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px;">-17%</span>
                </button>
            </div>
        </div>

        {{-- Planes --}}
        <div style="display: grid; grid-template-columns: repeat({{ $plans->count() }}, 1fr); gap: 20px; max-width: 900px; margin: 0 auto;">
            @foreach($plans as $plan)
                @php
                    $isCurrent = $plan->slug === $currentSlug;
                    $isPopular = $plan->slug === 'profesional';
                    $isUpgrade = $plan->sort_order > ($currentPlan?->sort_order ?? 1);
                    $monthlyPrice = number_format($plan->price_monthly, 0, ',', '.');
                    $biannualPrice = number_format($plan->price_yearly, 0, ',', '.');
                    $monthlySaved = $plan->price_monthly > 0 ? number_format(round($plan->price_yearly / 6), 0, ',', '.') : '0';
                    $savings = $plan->price_monthly > 0 ? number_format(($plan->price_monthly * 6) - $plan->price_yearly, 0, ',', '.') : '0';
                @endphp

                <div style="background: #fff; border-radius: 16px; padding: 28px 24px; border: {{ $isPopular ? '2px solid #3A86FF' : ($isCurrent ? '2px solid #10b981' : '1px solid #e5e7eb') }}; position: relative; {{ $isPopular ? 'box-shadow: 0 4px 20px rgba(58,134,255,0.15);' : '' }}">

                    @if($isPopular)
                        <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #3A86FF; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 14px; border-radius: 999px;">Mas popular</div>
                    @endif

                    @if($isCurrent)
                        <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #10b981; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 14px; border-radius: 999px;">Tu plan actual</div>
                    @endif

                    <h3 style="font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 4px;">{{ $plan->name }}</h3>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">{{ $plan->description }}</p>

                    {{-- Precio --}}
                    <div style="margin-bottom: 20px; min-height: 70px;">
                        @if($plan->price_monthly === 0)
                            <div style="font-size: 36px; font-weight: 800; color: #111827;">$0</div>
                            <div style="font-size: 13px; color: #6b7280;">Gratis para siempre</div>
                        @else
                            <template x-if="billing === 'monthly'">
                                <div>
                                    <div><span style="font-size: 36px; font-weight: 800; color: #111827;">${{ $monthlyPrice }}</span><span style="font-size: 14px; color: #6b7280;">/mes</span></div>
                                </div>
                            </template>
                            <template x-if="billing === 'biannual'">
                                <div>
                                    <div><span style="font-size: 36px; font-weight: 800; color: #111827;">${{ $biannualPrice }}</span><span style="font-size: 14px; color: #6b7280;">/6 meses</span></div>
                                    <div style="font-size: 12px; color: #16a34a; font-weight: 500; margin-top: 2px;">${{ $monthlySaved }}/mes &middot; Ahorras ${{ $savings }}</div>
                                </div>
                            </template>
                        @endif
                    </div>

                    {{-- Features --}}
                    <ul style="list-style: none; padding: 0; margin: 0 0 24px 0; font-size: 13px;">
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #374151;">
                            <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="min-width:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <strong>{{ $plan->max_cases }}</strong>&nbsp;casos y clientes
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #374151;">
                            <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="min-width:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan->max_users }} usuario(s)
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #374151;">
                            <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="min-width:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            21 flujos de proceso
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; {{ $plan->has_portal ? 'color: #374151;' : 'color: #9ca3af; text-decoration: line-through;' }}">
                            <svg width="16" height="16" fill="none" stroke="{{ $plan->has_portal ? '#16a34a' : '#9ca3af' }}" viewBox="0 0 24 24" style="min-width:16px;">
                                @if($plan->has_portal)<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>@else<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>@endif
                            </svg>
                            Portal del cliente
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; {{ $plan->has_notifications ? 'color: #374151;' : 'color: #9ca3af; text-decoration: line-through;' }}">
                            <svg width="16" height="16" fill="none" stroke="{{ $plan->has_notifications ? '#16a34a' : '#9ca3af' }}" viewBox="0 0 24 24" style="min-width:16px;">
                                @if($plan->has_notifications)<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>@else<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>@endif
                            </svg>
                            Notificaciones
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; color: #374151;">
                            <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="min-width:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan->max_storage_mb >= 1024 ? round($plan->max_storage_mb / 1024) . ' GB' : $plan->max_storage_mb . ' MB' }} almacenamiento
                        </li>
                    </ul>

                    {{-- Boton --}}
                    @if($isCurrent)
                        <div style="text-align: center; padding: 12px; background: #f0fdf4; border-radius: 8px; font-size: 13px; font-weight: 600; color: #166534;">
                            Plan actual
                        </div>
                    @elseif($isUpgrade && $plan->price_monthly > 0)
                        <a
                            x-bind:href="'{{ route('wompi.checkout') }}?plan_id={{ $plan->id }}&billing_cycle=' + billing"
                            style="display: block; width: 100%; padding: 12px; background: {{ $isPopular ? 'linear-gradient(135deg, #3A86FF, #2563eb)' : '#111827' }}; color: #fff; font-size: 14px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none;"
                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                            Mejorar plan
                        </a>
                    @elseif($plan->price_monthly === 0)
                        <div style="text-align: center; padding: 12px; color: #6b7280; font-size: 13px;">
                            Plan base incluido
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <p style="text-align: center; font-size: 13px; color: #9ca3af; margin-top: 24px;">
            Todos los planes incluyen 21 flujos basados en legislacion colombiana. Prueba de 30 dias en planes de pago.
        </p>
    </div>
</x-filament-panels::page>
