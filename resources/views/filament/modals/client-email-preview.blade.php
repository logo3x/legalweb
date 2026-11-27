{{-- Vista previa del correo que recibira el cliente, plegada detras de un boton. --}}
<div x-data="{ abierto: false }" style="text-align: left;">
    <p style="margin: 0 0 10px; font-size: 13.5px; color: #475569;">
        Asunto: <strong style="color: #0F172A;">{{ $subject }}</strong>
    </p>

    <x-filament::button
        type="button"
        color="gray"
        size="sm"
        icon="heroicon-o-eye"
        x-on:click="abierto = ! abierto"
        x-bind:aria-expanded="abierto.toString()"
    >
        <span x-text="abierto ? 'Ocultar el correo' : 'Ver el correo'">Ver el correo</span>
    </x-filament::button>

    <div x-show="abierto" x-collapse style="display: none; margin-top: 12px;">
        <iframe
            title="Vista previa del correo para el cliente"
            srcdoc="{{ $html }}"
            sandbox=""
            style="display: block; width: 100%; height: 420px; border: 1px solid #E2E8F0; border-radius: 10px; background: #FFFFFF;"
        ></iframe>
        <p style="margin: 8px 0 0; font-size: 12.5px; color: #64748B;">Así lo verá el cliente. Si quiere cambiar el texto, edite la actuación antes de enviarlo.</p>
    </div>
</div>
