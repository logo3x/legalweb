<x-filament-panels::page>

    @php
        $allPlans = $this->getPlans();
        $limits = auth()->user()->firm?->planLimits();
        $tier = $limits?->tier() ?? 'unmanaged';
        $currentPlan = $limits?->plan();

        $freePlan = $allPlans->firstWhere('price_monthly', 0);
        $paidPlans = $allPlans->where('price_monthly', '>', 0)->sortBy('price_monthly')->values();

        $money = fn (int $value): string => '$'.number_format($value, 0, ',', '.');

        $planFeatures = function ($plan): array {
            if ((int) $plan->price_monthly === 0) {
                return [
                    [true, 'Hasta '.($plan->max_cases ?: 5).' casos activos'],
                    [true, '1 usuario'],
                    [true, ($plan->ai_monthly_limit ?? 10).' consultas de IA al mes'],
                    [true, 'Rama Judicial y SAMAI (sincronización semanal)'],
                    [true, 'Portal del cliente, agenda y calculadora de términos'],
                    ...(\App\Services\WhatsAppService::isEnabled() ? [[false, 'Avisos por WhatsApp']] : []),
                    [false, 'Vigilancia de clientes en la Rama Judicial'],
                ];
            }

            // Segun los limites de cada plan de pago (Individual, Profesional, Firma).
            return [
                [true, $plan->max_cases ? 'Hasta '.$plan->max_cases.' procesos activos' : 'Casos y clientes ilimitados'],
                [true, (int) $plan->max_users === 1 ? '1 usuario' : 'Hasta '.$plan->max_users.' usuarios con permisos por caso'],
                [true, $plan->ai_monthly_limit ? $plan->ai_monthly_limit.' consultas de IA al mes' : 'Asistente IA sin límite mensual'],
                [true, 'Rama Judicial y SAMAI (revisión diaria)'],
                [true, 'Aviso el mismo día si una consulta falla e informe de verificación cada lunes'],
                [true, 'Portal del cliente, reportes PDF y facturación'],
                ...(\App\Services\WhatsAppService::isEnabled() ? [[(bool) $plan->has_whatsapp, 'Avisos por WhatsApp']] : []),
                [(bool) $plan->has_client_monitoring, 'Vigilancia de clientes en la Rama Judicial'],
            ];
        };

        $daysLeft = $tier === 'trial' ? max(0, (int) now()->startOfDay()->diffInDays($limits->limitsStartAt()->copy()->startOfDay(), false)) : null;
    @endphp

    <div style="max-width: 1100px; margin: 0 auto;">

        <div style="text-align: center; margin-bottom: 36px;">
            <h2 style="font-size: 28px; font-weight: 700; color: #1E3A5F; margin: 0 0 10px; font-family: 'Poppins','Inter',sans-serif;">
                Elija el plan para su práctica
            </h2>
            <p style="font-size: 15px; color: #6b7280; max-width: 680px; margin: 0 auto; line-height: 1.6;">
                Todas las cuentas empiezan con <strong>3 meses gratis con todo incluido</strong>. Después puede seguir gratis con el plan
                Gratuito o pasar a Individual, Profesional o Firma. Sus datos se conservan siempre.
            </p>
            @if($tier === 'trial')
                <div style="display: inline-block; margin-top: 16px; padding: 8px 16px; background: #FEF3C7; color: #92400E; border-radius: 999px; font-size: 13px; font-weight: 600;">
                    Le quedan {{ $daysLeft }} días de prueba. Desde el {{ $limits->limitsStartAt()->format('d/m/Y') }} aplica el plan Gratuito.
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat({{ 1 + $paidPlans->count() }}, 1fr); gap: 20px; margin-bottom: 28px;" class="lw-plans-grid">

            @if($freePlan)
                @php $isCurrent = in_array($tier, ['trial', 'free'], true); @endphp
                <div style="background: #fff; border-radius: 16px; padding: 28px; border: {{ $isCurrent ? '2px solid #10b981' : '1px solid #e5e7eb' }}; position: relative; box-shadow: 0 1px 3px rgba(15,23,42,.06);">
                    @if($isCurrent)
                        <div style="position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: #10b981; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 999px; text-transform: uppercase; white-space: nowrap;">{{ $tier === 'trial' ? 'En prueba' : 'Tu plan actual' }}</div>
                    @endif
                    <h3 style="font-family: 'Poppins','Inter',sans-serif; font-size: 22px; font-weight: 700; color: #1E3A5F; margin: 0 0 6px;">Gratuito</h3>
                    <p style="font-size: 14px; color: #64748B; margin: 0 0 18px; line-height: 1.5;">Para empezar o para una práctica pequeña.</p>
                    <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 20px;">
                        <span style="font-family: 'Poppins','Inter',sans-serif; font-size: 2.6rem; font-weight: 800; color: #10b981; line-height: 1;">$0</span>
                        <span style="font-size: 15px; color: #64748B; font-weight: 600;">siempre</span>
                    </div>
                    @include('filament.pages.partials.plan-features', ['features' => $planFeatures($freePlan)])
                </div>
            @endif

            @foreach($paidPlans as $plan)
                @php
                    $isCurrent = $tier === 'paid' && $currentPlan?->id === $plan->id;
                    $isRecommended = $loop->first;
                    $semesterSaving = max(0, $plan->price_monthly * 6 - $plan->price_yearly);
                @endphp
                <div style="background: #fff; border-radius: 16px; padding: 28px; position: relative; {{ $isCurrent ? 'border: 2px solid #10b981;' : ($isRecommended ? 'border: 2px solid #3A86FF; box-shadow: 0 14px 32px rgba(58,134,255,.18);' : 'border: 1px solid #e5e7eb;') }}">
                    @if($isCurrent)
                        <div style="position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: #10b981; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 999px; text-transform: uppercase;">Tu plan actual</div>
                    @elseif($isRecommended)
                        <div style="position: absolute; top: -13px; left: 50%; transform: translateX(-50%); background: #3A86FF; color: #fff; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 999px; text-transform: uppercase;">Recomendado</div>
                    @endif
                    <h3 style="font-family: 'Poppins','Inter',sans-serif; font-size: 22px; font-weight: 700; color: #1E3A5F; margin: 0 0 6px;">{{ $plan->name }}</h3>
                    <p style="font-size: 14px; color: #64748B; margin: 0 0 18px; line-height: 1.5;">{{ $plan->description ?: 'Para abogados independientes y equipos pequeños.' }}</p>
                    <div style="display: flex; align-items: baseline; gap: 8px;">
                        <span style="font-family: 'Poppins','Inter',sans-serif; font-size: 2.6rem; font-weight: 800; color: #3A86FF; line-height: 1;">{{ $money($plan->price_monthly) }}</span>
                        <span style="font-size: 15px; color: #64748B; font-weight: 600;">COP / mes</span>
                    </div>
                    <div style="font-size: 13px; color: #64748B; margin: 6px 0 20px;">
                        o {{ $money($plan->price_yearly) }} por 6 meses @if($semesterSaving > 0)<strong style="color: #047857;">(ahorra {{ $money($semesterSaving) }})</strong>@endif
                    </div>

                    @include('filament.pages.partials.plan-features', ['features' => $planFeatures($plan)])

                    @if($isCurrent)
                        <div style="text-align: center; padding: 12px; background: #ECFDF5; border-radius: 10px; font-size: 14px; font-weight: 600; color: #047857; margin-top: 18px;">Plan activo</div>
                    @else
                        <form method="POST" action="{{ route('wompi.checkout') }}" style="display: flex; flex-direction: column; gap: 8px; margin-top: 18px;">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <select name="billing_cycle" style="padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E3A5F;">
                                <option value="monthly">Mensual: {{ $money($plan->price_monthly) }}</option>
                                <option value="biannual">Semestral: {{ $money($plan->price_yearly) }}</option>
                            </select>
                            <details class="lw-coupon" style="font-size: 13px; color: #475569;">
                                <summary style="cursor: pointer; padding: 6px 0; font-weight: 600; color: #1E3A5F;">Tengo un código de descuento</summary>
                                <input type="text" name="discount_code" placeholder="EJEMPLO20" oninput="this.value = this.value.toUpperCase()"
                                    style="margin-top: 6px; width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; text-transform: uppercase;">
                            </details>
                            <button type="submit"
                                style="width: 100%; padding: 13px; background: {{ $isRecommended ? '#3A86FF' : '#1E3A5F' }}; color: #fff; font-size: 15px; font-weight: 600; border: none; border-radius: 10px; cursor: pointer;">
                                {{ $tier === 'paid' ? 'Cambiar a '.$plan->name : 'Suscribirme a '.$plan->name }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        @if($this->canManageAutoRenew())
            @php $firm = auth()->user()->firm; @endphp
            {{-- Pago automatico: la tarjeta se tokeniza en el navegador directamente con Wompi; a LegalWeb solo llega el token. --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
                <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #1E3A5F;">Pago automático de la renovación</h3>

                @if($firm->hasAutoRenewCard())
                    <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">
                        Activo con su <strong>{{ $firm->card_brand }} terminada en {{ $firm->card_last_four }}</strong>.
                        Cobramos la renovación el día anterior al vencimiento y le avisamos si el banco no la aprueba.
                    </p>
                    <x-filament::button color="gray" wire:click="removeCard" wire:confirm="¿Quitar la tarjeta y desactivar el pago automático?">
                        Quitar tarjeta y desactivar
                    </x-filament::button>
                @else
                    @php $acceptance = $this->getWompiAcceptance(); @endphp
                    <p style="margin: 0 0 16px; font-size: 14px; color: #475569;">
                        Registre una tarjeta y renovamos su plan solos el día anterior al vencimiento. Puede quitarla cuando quiera.
                        Los datos de la tarjeta van directo a Wompi: LegalWeb nunca los ve ni los guarda.
                    </p>

                    @if(! $acceptance)
                        <p style="font-size: 13px; color: #B45309;">Wompi no está respondiendo en este momento. Intente de nuevo en unos minutos.</p>
                    @else
                        <div x-data="{
                                number: '', expMonth: '', expYear: '', cvc: '', holder: '',
                                acceptTerms: false, acceptData: false, authorize: false,
                                busy: false, error: '',
                                async submit() {
                                    this.error = '';
                                    if (! this.acceptTerms || ! this.acceptData || ! this.authorize) { this.error = 'Debe aceptar las tres autorizaciones.'; return; }
                                    this.busy = true;
                                    try {
                                        const response = await fetch(@js(config('services.wompi.base_url').'/tokens/cards'), {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + @js((string) config('services.wompi.public_key')) },
                                            body: JSON.stringify({
                                                number: this.number.replace(/\s+/g, ''),
                                                cvc: this.cvc.trim(),
                                                exp_month: this.expMonth.trim().padStart(2, '0'),
                                                exp_year: this.expYear.trim().slice(-2),
                                                card_holder: this.holder.trim(),
                                            }),
                                        });
                                        const json = await response.json();
                                        if (! response.ok || ! json.data || ! json.data.id) { throw new Error('Wompi no aceptó los datos de la tarjeta. Revíselos.'); }
                                        // Se borran del formulario: el numero nunca se envia a LegalWeb.
                                        this.number = ''; this.cvc = '';
                                        await $wire.registerCard(json.data.id, @js($acceptance['acceptance_token']), @js($acceptance['personal_auth_token']), this.authorize);
                                    } catch (e) {
                                        this.error = e.message || 'No se pudo registrar la tarjeta.';
                                    } finally {
                                        this.busy = false;
                                    }
                                },
                            }" style="display: grid; gap: 12px; max-width: 520px;">
                            <input x-model="holder" type="text" autocomplete="cc-name" placeholder="Nombre como aparece en la tarjeta" class="fi-input" style="border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px;">
                            <input x-model="number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="Número de la tarjeta" class="fi-input" style="border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                <input x-model="expMonth" type="text" inputmode="numeric" autocomplete="cc-exp-month" placeholder="Mes (MM)" class="fi-input" style="border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px;">
                                <input x-model="expYear" type="text" inputmode="numeric" autocomplete="cc-exp-year" placeholder="Año (AA)" class="fi-input" style="border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px;">
                                <input x-model="cvc" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="CVC" class="fi-input" style="border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px;">
                            </div>
                            <label style="font-size: 13px; color: #334155; display: flex; gap: 8px; align-items: flex-start;">
                                <input type="checkbox" x-model="acceptTerms" style="margin-top: 3px;">
                                <span>Acepto los <a href="{{ $acceptance['acceptance_permalink'] }}" target="_blank" rel="noopener" style="color: #2563EB; text-decoration: underline;">términos y condiciones de Wompi</a>.</span>
                            </label>
                            <label style="font-size: 13px; color: #334155; display: flex; gap: 8px; align-items: flex-start;">
                                <input type="checkbox" x-model="acceptData" style="margin-top: 3px;">
                                <span>Autorizo a Wompi el <a href="{{ $acceptance['personal_auth_permalink'] }}" target="_blank" rel="noopener" style="color: #2563EB; text-decoration: underline;">tratamiento de mis datos personales</a>.</span>
                            </label>
                            <label style="font-size: 13px; color: #334155; display: flex; gap: 8px; align-items: flex-start;">
                                <input type="checkbox" x-model="authorize" style="margin-top: 3px;">
                                <span>Autorizo a LegalWeb a cobrar automáticamente a esta tarjeta el valor de mi plan en cada renovación, hasta que la quite en Mi Plan.</span>
                            </label>
                            <p x-show="error" x-text="error" style="font-size: 13px; color: #B91C1C; margin: 0;"></p>
                            <div>
                                <x-filament::button type="button" x-on:click="submit()" x-bind:disabled="busy">
                                    <span x-show="! busy">Registrar tarjeta y activar pago automático</span>
                                    <span x-show="busy">Registrando…</span>
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        <p style="text-align: center; font-size: 13px; color: #94A3B8;">
            Precios en pesos colombianos. Pago seguro con Wompi. Si su cuenta supera los límites del plan Gratuito, sus casos se conservan: solo no podrá agregar casos activos nuevos.
        </p>
    </div>

    <style>
        @media (max-width: 900px) {
            .lw-plans-grid { grid-template-columns: 1fr !important; }
        }
        .lw-coupon summary::-webkit-details-marker { display: none; }
        .lw-coupon summary { list-style: none; }
    </style>

</x-filament-panels::page>
