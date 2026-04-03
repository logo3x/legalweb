@extends('layouts.portal')

@section('title', 'Terminos y Condiciones')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 prose prose-gray max-w-none">
        <h1>Terminos y Condiciones de Uso</h1>
        <p class="text-sm text-gray-500">Ultima actualizacion: {{ date('d/m/Y') }}</p>

        <h2>1. Objeto</h2>
        <p>LegalWeb (en adelante "la Plataforma") es una herramienta tecnologica de gestion juridica que permite a abogados y firmas de abogados administrar casos, documentos, actuaciones y comunicaciones con sus clientes. La Plataforma actua unicamente como un medio tecnologico y <strong>no presta servicios juridicos ni sustituye el criterio profesional del abogado</strong>.</p>

        <h2>2. Definiciones</h2>
        <ul>
            <li><strong>Abogado/Usuario:</strong> Profesional del derecho debidamente registrado que utiliza la Plataforma para gestionar sus casos.</li>
            <li><strong>Cliente:</strong> Persona natural o juridica que accede al portal de seguimiento de su caso a traves de un enlace compartido por su abogado.</li>
            <li><strong>Portal del Cliente:</strong> Interfaz de solo lectura que permite al cliente visualizar el estado de su proceso legal.</li>
            <li><strong>Datos Personales:</strong> Cualquier informacion vinculada o que pueda asociarse a una persona natural identificada o identificable, conforme a la Ley 1581 de 2012.</li>
        </ul>

        <h2>3. Condiciones de Uso para Abogados</h2>
        <p>El abogado que utiliza la Plataforma declara y garantiza que:</p>
        <ul>
            <li>Cuenta con tarjeta profesional vigente expedida por el Consejo Superior de la Judicatura de Colombia.</li>
            <li>La informacion que registra en la Plataforma corresponde a casos reales bajo su responsabilidad profesional.</li>
            <li>Ha obtenido la <strong>autorizacion expresa</strong> de sus clientes para el tratamiento de datos personales conforme a la Ley 1581 de 2012 antes de registrarlos en la Plataforma.</li>
            <li>Es el unico responsable de la veracidad, oportunidad y actualizacion de la informacion registrada.</li>
            <li>Mantendra la confidencialidad de sus credenciales de acceso.</li>
            <li>No utilizara la Plataforma para fines ilicitos o contrarios a la etica profesional establecida en la Ley 1123 de 2007 (Codigo Disciplinario del Abogado).</li>
        </ul>

        <h2>4. Condiciones de Uso del Portal del Cliente</h2>
        <p>El cliente que accede al Portal declara y acepta que:</p>
        <ul>
            <li>El enlace de acceso es <strong>personal e intransferible</strong>. No debe compartirlo con terceros no autorizados.</li>
            <li>La informacion mostrada es de caracter <strong>informativo</strong> y puede no reflejar el estado procesal en tiempo real.</li>
            <li>La Plataforma <strong>no sustituye</strong> la comunicacion directa con su abogado.</li>
            <li>Autoriza el registro de su direccion IP, fecha y hora de acceso para fines de trazabilidad y seguridad.</li>
            <li>La informacion visualizada esta protegida por el <strong>secreto profesional</strong> (Art. 74 de la Constitucion Politica de Colombia).</li>
        </ul>

        <h2>5. Propiedad Intelectual</h2>
        <p>Todos los elementos de la Plataforma, incluyendo pero sin limitarse a su diseno, codigo fuente, logotipos, textos e interfaces, son propiedad de LegalWeb y estan protegidos por las normas de propiedad intelectual vigentes en Colombia (Ley 23 de 1982, Ley 1915 de 2018 y Decision Andina 351 de 1993).</p>

        <h2>6. Limitacion de Responsabilidad</h2>
        <p>La Plataforma:</p>
        <ul>
            <li><strong>No es responsable</strong> por el resultado de los procesos legales gestionados a traves de ella.</li>
            <li><strong>No garantiza</strong> la disponibilidad ininterrumpida del servicio, aunque se esforzara por mantener un nivel adecuado de disponibilidad.</li>
            <li><strong>No es responsable</strong> por la veracidad o actualidad de la informacion registrada por los abogados.</li>
            <li><strong>No reemplaza</strong> la obligacion del abogado de cumplir con sus deberes profesionales, incluyendo el control de terminos procesales.</li>
            <li>No asume responsabilidad por perdida de datos derivada de caso fortuito, fuerza mayor o acciones de terceros.</li>
        </ul>

        <h2>7. Suspension y Terminacion</h2>
        <p>LegalWeb se reserva el derecho de suspender o terminar el acceso a la Plataforma cuando:</p>
        <ul>
            <li>Se detecte uso indebido, fraudulento o contrario a estos terminos.</li>
            <li>Se incumplan las obligaciones de pago del servicio de suscripcion.</li>
            <li>Se reciba orden judicial o administrativa que asi lo requiera.</li>
            <li>Se comprometa la seguridad de la Plataforma o de los datos de otros usuarios.</li>
        </ul>

        <h2>8. Ley Aplicable y Jurisdiccion</h2>
        <p>Estos terminos se rigen por las leyes de la Republica de Colombia. Para la resolucion de cualquier controversia derivada de estos terminos, las partes se someten a la jurisdiccion de los jueces y tribunales de la ciudad de Bogota D.C., Colombia, salvo que la ley disponga una jurisdiccion diferente de manera imperativa.</p>

        <h2>9. Modificaciones</h2>
        <p>LegalWeb se reserva el derecho de modificar estos terminos en cualquier momento. Los cambios seran notificados a los usuarios a traves de la Plataforma con al menos <strong>quince (15) dias</strong> de antelacion a su entrada en vigencia. El uso continuado de la Plataforma despues de la fecha de vigencia constituye aceptacion de los nuevos terminos.</p>

        <h2>10. Contacto</h2>
        <p>Para cualquier consulta relacionada con estos terminos, puede comunicarse a traves de los canales habilitados en la Plataforma o al correo electronico del responsable del tratamiento de datos indicado en la Politica de Privacidad.</p>
    </div>
@endsection
