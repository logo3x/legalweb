<x-filament-panels::page>
    <div style="margin-bottom: 24px; padding: 16px 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; color: #1e40af; font-size: 14px; line-height: 1.6;">
        <p style="margin: 0;"><strong>&iquest;C&oacute;mo funciona?</strong> Seleccione el tipo de notificaci&oacute;n y reciba en su correo (<strong>{{ auth()->user()->email }}</strong>) un ejemplo de c&oacute;mo le llegar&aacute; cuando ocurra el evento real. Usa datos de su primer caso registrado para que la previsualizaci&oacute;n sea realista.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
        @foreach($this->getNotifications() as $n)
            @php
                $colorMap = [
                    'primary' => '#3A86FF',
                    'success' => '#10B981',
                    'warning' => '#F59E0B',
                    'danger' => '#EF4444',
                    'info' => '#06B6D4',
                    'gray' => '#6B7280',
                ];
                $bgMap = [
                    'primary' => '#dbeafe',
                    'success' => '#d1fae5',
                    'warning' => '#fef3c7',
                    'danger' => '#fee2e2',
                    'info' => '#cffafe',
                    'gray' => '#f3f4f6',
                ];
                $color = $colorMap[$n['color']] ?? '#3A86FF';
                $bg = $bgMap[$n['color']] ?? '#dbeafe';
            @endphp
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 12px; transition: box-shadow .2s, transform .2s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(15,23,42,0.06)';this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='none';this.style.transform='none';">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: {{ $bg }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <x-filament::icon :icon="$n['icon']" style="width: 22px; height: 22px; color: {{ $color }};" />
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h3 style="margin: 0 0 2px 0; font-size: 15px; font-weight: 600; color: #111827; line-height: 1.3;">{{ $n['label'] }}</h3>
                    </div>
                </div>
                <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.55; flex: 1;">{{ $n['description'] }}</p>
                <button
                    wire:click="enviarPrueba('{{ $n['key'] }}')"
                    wire:loading.attr="disabled"
                    wire:target="enviarPrueba"
                    style="background: {{ $color }}; color: #ffffff; border: none; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity .15s; align-self: flex-start; box-shadow: 0 2px 6px rgba(0,0,0,0.06);"
                    onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                    <span wire:loading.remove wire:target="enviarPrueba('{{ $n['key'] }}')">Enviarme prueba</span>
                    <span wire:loading wire:target="enviarPrueba('{{ $n['key'] }}')">Enviando&hellip;</span>
                </button>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 32px; padding: 16px 20px; background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; color: #854d0e; font-size: 13px; line-height: 1.6;">
        <p style="margin: 0;"><strong>Nota:</strong> el correo puede tardar hasta un minuto en llegar. Si no aparece en la bandeja de entrada, revise la carpeta de <strong>spam</strong> o <strong>promociones</strong>. Si su servicio marca nuestros correos como spam, ag&eacute;guelos a su libreta de direcciones para evitarlo a futuro.</p>
    </div>
</x-filament-panels::page>
