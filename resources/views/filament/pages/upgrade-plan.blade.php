<x-filament-panels::page>

    @php
        $allPlans = $this->getPlans();
        $currentPlan = $this->getCurrentPlan();
        $currentSlug = $currentPlan?->slug ?? 'gratuito';

        // El producto se vende con un solo precio: gratis 3 meses + $120.000 COP/mes.
        // Los modelos en BD pueden tener varios planes, pero la pagina pinta solo dos
        // tarjetas alineadas con la landing: Empieza gratis · Profesional.
        $freePlan = $allPlans->firstWhere('price_monthly', 0)
            ?? $allPlans->firstWhere('slug', 'gratuito');

        $paidPlan = $allPlans->where('price_monthly', '>', 0)->sortBy('price_monthly')->first();

        $paidPrice = $paidPlan?->price_monthly ?? 120000;
        $formattedPrice = '$'.number_format($paidPrice, 0, ',', '.');

        $includes = [
            'Casos y clientes ilimitados',
            'Importacion y vigilancia de la Rama Judicial',
            'Alertas de actuaciones por correo',
            'Calculadora de terminos en dias habiles',
            'Asistente IA juridico',
            'Portal del cliente y reportes PDF',
            '21 flujos procesales + facturacion por caso',
            'Equipo de trabajo con permisos por caso',
        ];

        $isOnFree = $currentSlug === ($freePlan?->slug ?? 'gratuito') || ($currentPlan && $currentPlan->price_monthly == 0);
    @endphp

    <div style="max-width: 980px; margin: 0 auto;">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-size: 28px; font-weight: 700; color: #1E3A5F; margin: 0 0 10px; font-family: 'Poppins','Inter',sans-serif;">
                Un precio. Todo incluido.
            </h2>
            <p style="font-size: 15px; color: #6b7280; max-width: 640px; margin: 0 auto; line-height: 1.6;">
                Prueba LegalWeb completo y gratis por <strong>3 meses</strong>. Despues, una sola suscripcion de
                <strong>{{ $formattedPrice }} COP/mes</strong> &mdash; sin niveles, sin limites de casos, sin sorpresas.
            </p>
        </div>

        {{-- Planes --}}
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px;" class="lw-plans-grid">

            {{-- Plan gratis --}}
            <div style="background: #fff; border-radius: 20px; padding: 32px; border: {{ $isOnFree ? '2px solid #10b981' : '1px solid #e5e7eb' }}; position: relative; box-shadow: 0 1px 3px rgba(15,23,42,.06);">
                @if($isOnFree)
                    <div style="position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: #10b981; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .03em; padding: 4px 14px; border-radius: 999px; text-transform: uppercase;">Tu plan actual</div>
                @endif

                <div style="display: inline-flex; align-items: center; background: #ECFDF5; color: #047857; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: 5px 12px; border-radius: 999px; margin-bottom: 16px;">Prueba gratuita</div>
                <h3 style="font-family: 'Poppins','Inter',sans-serif; font-size: 24px; font-weight: 700; color: #1E3A5F; margin: 0 0 6px;">Empieza gratis</h3>
                <p style="font-size: 14px; color: #64748B; margin: 0 0 22px; line-height: 1.5;">Todo el producto, sin limites, durante 3 meses.</p>

                <div style="margin-bottom: 22px;">
                    <div style="display: flex; align-items: baseline; gap: 8px;">
                        <span style="font-family: 'Poppins','Inter',sans-serif; font-size: 3rem; font-weight: 800; color: #10b981; line-height: 1;">$0</span>
                        <span style="font-size: 16px; color: #64748B; font-weight: 600;">/ 3 meses</span>
                    </div>
                    <div style="font-size: 14px; color: #94A3B8; margin-top: 8px;">Sin tarjeta de credito</div>
                </div>

                @if($isOnFree)
                    <div style="text-align: center; padding: 14px; background: #ECFDF5; border-radius: 10px; font-size: 14px; font-weight: 600; color: #047857;">
                        Plan activo
                    </div>
                @else
                    <div style="text-align: center; padding: 14px; color: #94A3B8; font-size: 13px;">
                        Disponible solo al registrarse
                    </div>
                @endif

                <div style="font-size: 13px; color: #94A3B8; text-align: center; margin-top: 12px;">Configurelo en 60 segundos</div>
            </div>

            {{-- Plan pago --}}
            <div style="background: #fff; border-radius: 20px; padding: 32px; border: 2px solid #3A86FF; position: relative; box-shadow: 0 10px 28px rgba(58,134,255,.18);">
                <div style="position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: #3A86FF; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .03em; padding: 4px 14px; border-radius: 999px; text-transform: uppercase;">Recomendado</div>

                <div style="display: inline-flex; align-items: center; background: #EFF6FF; color: #1d4ed8; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: 5px 12px; border-radius: 999px; margin-bottom: 16px;">Suscripcion</div>
                <h3 style="font-family: 'Poppins','Inter',sans-serif; font-size: 24px; font-weight: 700; color: #1E3A5F; margin: 0 0 6px;">Profesional</h3>
                <p style="font-size: 14px; color: #64748B; margin: 0 0 22px; line-height: 1.5;">Cuando termine tu prueba, sigue con todo igual.</p>

                <div style="margin-bottom: 22px;">
                    <div style="display: flex; align-items: baseline; gap: 8px;">
                        <span style="font-family: 'Poppins','Inter',sans-serif; font-size: 3rem; font-weight: 800; color: #3A86FF; line-height: 1;">{{ $formattedPrice }}</span>
                        <span style="font-size: 16px; color: #64748B; font-weight: 600;">COP / mes</span>
                    </div>
                    <div style="font-size: 14px; color: #94A3B8; margin-top: 8px;">Una sola suscripcion &middot; cancela cuando quieras</div>
                </div>

                @if($paidPlan && $isOnFree)
                    <a href="{{ route('wompi.checkout') }}?plan_id={{ $paidPlan->id }}&billing_cycle=monthly"
                       style="display: block; width: 100%; padding: 14px; background: #3A86FF; color: #fff; font-size: 15px; font-weight: 600; border-radius: 10px; text-align: center; text-decoration: none; box-shadow: 0 8px 18px rgba(58,134,255,.28);"
                       onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3A86FF'">
                        Suscribirme ahora
                    </a>
                @elseif($paidPlan && $currentPlan?->id === $paidPlan->id)
                    <div style="text-align: center; padding: 14px; background: #EFF6FF; border-radius: 10px; font-size: 14px; font-weight: 600; color: #1d4ed8;">
                        Plan activo
                    </div>
                @else
                    <div style="text-align: center; padding: 14px; color: #94A3B8; font-size: 13px;">
                        Disponible al terminar la prueba
                    </div>
                @endif

                <div style="font-size: 13px; color: #94A3B8; text-align: center; margin-top: 12px;">Mismas funciones que la prueba</div>
            </div>

        </div>

        {{-- Lo que incluye --}}
        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 32px; box-shadow: 0 1px 3px rgba(15,23,42,.06);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <svg width="22" height="22" fill="none" stroke="#3A86FF" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <h3 style="font-family: 'Poppins','Inter',sans-serif; font-size: 20px; font-weight: 700; color: #1E3A5F; margin: 0;">Ambos planes incluyen todo</h3>
            </div>
            <ul style="list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 32px;" class="lw-incluye-grid">
                @foreach($includes as $feature)
                    <li style="display: flex; align-items: flex-start; gap: 10px; font-size: 14px; color: #374151;">
                        <svg width="16" height="16" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 2px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <p style="text-align: center; font-size: 13px; color: #94A3B8; margin-top: 28px;">
            Precio en pesos colombianos. La diferencia entre los planes es solo el tiempo: gratis los primeros 3 meses, luego {{ $formattedPrice }}/mes.
        </p>
    </div>

    <style>
        @media (max-width: 768px) {
            .lw-plans-grid { grid-template-columns: 1fr !important; }
            .lw-incluye-grid { grid-template-columns: 1fr !important; }
        }
    </style>

</x-filament-panels::page>
