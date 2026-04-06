@extends('layouts.portal')

@section('title', 'Terminos y Condiciones')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 prose prose-gray max-w-none">
        <h1>Terminos y Condiciones de Uso</h1>
        <p class="text-sm text-gray-500">Ultima actualizacion: 05 de abril de 2026 | Version 2.0</p>

        <h2>1. Objeto y Naturaleza del Servicio</h2>
        <p>LegalWeb (en adelante "la Plataforma") es una herramienta tecnologica de gestion juridica de tipo Software como Servicio (SaaS) que permite a abogados y firmas de abogados administrar casos, documentos, actuaciones, flujos procesales y comunicaciones con sus clientes.</p>
        <p><strong>DECLARACION IMPORTANTE:</strong> La Plataforma actua UNICAMENTE como un medio tecnologico de organizacion y seguimiento. NO presta servicios juridicos, NO emite conceptos legales, NO sustituye el criterio profesional del abogado y NO garantiza resultados de ningun proceso legal. Los contenidos generados por inteligencia artificial son borradores orientativos que DEBEN ser revisados, verificados y ajustados por un profesional del derecho antes de cualquier uso.</p>

        <h2>2. Definiciones</h2>
        <ul>
            <li><strong>Plataforma:</strong> El software LegalWeb, incluyendo todas sus funcionalidades, interfaces, APIs y servicios asociados.</li>
            <li><strong>Abogado/Usuario:</strong> Profesional del derecho debidamente registrado que utiliza la Plataforma.</li>
            <li><strong>Firma:</strong> La organizacion juridica (persona natural o juridica) registrada en la Plataforma.</li>
            <li><strong>Administrador:</strong> El usuario que creo la cuenta de la Firma y tiene control total sobre la misma.</li>
            <li><strong>Colaborador:</strong> Usuario invitado por el Administrador con permisos limitados sobre casos especificos.</li>
            <li><strong>Cliente:</strong> Persona que accede al Portal del Cliente mediante enlace compartido por su abogado.</li>
            <li><strong>Portal del Cliente:</strong> Interfaz de solo lectura para consulta del estado del proceso.</li>
            <li><strong>Asistente IA:</strong> Funcionalidad que utiliza modelos de inteligencia artificial para generar resumenes, sugerencias y borradores de documentos.</li>
            <li><strong>Datos Personales:</strong> Cualquier informacion vinculada a una persona natural identificada o identificable (Ley 1581 de 2012).</li>
            <li><strong>Datos Sensibles:</strong> Informacion relacionada con procesos judiciales, sanciones penales, datos de menores y cualquier dato que pueda generar discriminacion (Art. 5, Ley 1581 de 2012).</li>
        </ul>

        <h2>3. Aceptacion de los Terminos</h2>
        <p>Al registrarse en la Plataforma, el Usuario declara:</p>
        <ul>
            <li>Que ha leido, entendido y acepta integramente estos Terminos y Condiciones.</li>
            <li>Que tiene capacidad legal para contratar y obligarse conforme a la legislacion colombiana.</li>
            <li>Que si actua en representacion de una firma o persona juridica, cuenta con la autorizacion suficiente para vincularla.</li>
            <li>Que la aceptacion de estos terminos constituye un acuerdo vinculante entre el Usuario y LegalWeb.</li>
        </ul>

        <h2>4. Condiciones de Uso para Abogados</h2>
        <p>El abogado que utiliza la Plataforma declara y garantiza que:</p>
        <ul>
            <li>Cuenta con <strong>tarjeta profesional vigente</strong> expedida por el Consejo Superior de la Judicatura de Colombia.</li>
            <li>La informacion que registra corresponde a casos reales bajo su responsabilidad profesional.</li>
            <li>Ha obtenido la <strong>autorizacion expresa e informada</strong> de sus clientes para el tratamiento de datos personales conforme a la Ley 1581 de 2012 ANTES de registrarlos en la Plataforma.</li>
            <li>Es el <strong>unico responsable</strong> de la veracidad, oportunidad, actualizacion y legalidad de la informacion registrada.</li>
            <li>Mantendra la <strong>confidencialidad</strong> de sus credenciales de acceso y notificara inmediatamente cualquier uso no autorizado.</li>
            <li>No utilizara la Plataforma para fines ilicitos, fraudulentos o contrarios a la etica profesional (Ley 1123 de 2007).</li>
            <li>Es responsable de la <strong>gestion de permisos</strong> de los Colaboradores que invite, incluyendo los casos compartidos y los niveles de acceso otorgados.</li>
            <li>Entiende que los <strong>flujos de proceso</strong> y plazos son orientativos y NO sustituyen su obligacion de verificar y controlar los terminos procesales vigentes.</li>
            <li>Los <strong>recordatorios y alertas</strong> de la Plataforma son ayudas complementarias y NO eximen al abogado de su deber profesional de control de terminos.</li>
        </ul>

        <h2>5. Uso del Asistente de Inteligencia Artificial</h2>
        <p>La Plataforma incluye funcionalidades de IA para generacion de resumenes, sugerencias y borradores de documentos. El Usuario reconoce y acepta que:</p>
        <ul>
            <li>Los contenidos generados por IA son <strong>borradores orientativos</strong> que requieren revision profesional obligatoria.</li>
            <li>La IA puede generar informacion <strong>imprecisa, incompleta o desactualizada</strong>. Es responsabilidad exclusiva del abogado verificar toda ley, articulo, jurisprudencia o dato legal citado.</li>
            <li>LegalWeb <strong>NO garantiza</strong> la exactitud, vigencia ni aplicabilidad de los contenidos generados por IA.</li>
            <li>El uso de contenidos generados por IA <strong>sin la debida revision profesional</strong> es responsabilidad exclusiva del abogado.</li>
            <li>Los datos del caso enviados al servicio de IA son procesados por proveedores terceros (Google Gemini, OpenRouter) bajo sus respectivas politicas de privacidad.</li>
            <li>LegalWeb no almacena las consultas ni respuestas de IA mas alla de la sesion activa.</li>
        </ul>

        <h2>6. Condiciones del Portal del Cliente</h2>
        <p>El cliente que accede al Portal declara y acepta que:</p>
        <ul>
            <li>El enlace de acceso es <strong>personal e intransferible</strong>.</li>
            <li>La informacion mostrada es de caracter <strong>informativo</strong> y puede no reflejar el estado procesal en tiempo real.</li>
            <li>La Plataforma <strong>no sustituye</strong> la comunicacion directa con su abogado.</li>
            <li>Autoriza el registro de su direccion IP, fecha, hora y dispositivo de acceso para fines de <strong>trazabilidad</strong>.</li>
            <li>La informacion esta protegida por el <strong>secreto profesional</strong> (Art. 74 C.P., Art. 28 Ley 1123 de 2007).</li>
            <li>No reproducira, distribuira ni divulgara la informacion a terceros no autorizados.</li>
        </ul>

        <h2>7. Colaboradores y Gestion de Permisos</h2>
        <p>El Administrador de la Firma es responsable de:</p>
        <ul>
            <li>Verificar la identidad y competencia profesional de los Colaboradores que invite.</li>
            <li>Asignar permisos apropiados y proporcionales a la funcion de cada Colaborador.</li>
            <li>Revocar oportunamente el acceso de Colaboradores que ya no deban tenerlo.</li>
            <li>Supervisar el uso que los Colaboradores hagan de la informacion compartida.</li>
        </ul>
        <p>LegalWeb <strong>no es responsable</strong> por el uso indebido que un Colaborador haga de la informacion a la que tenga acceso por autorizacion del Administrador.</p>

        <h2>8. Propiedad de los Datos</h2>
        <ul>
            <li>Los datos registrados por el Usuario (casos, clientes, documentos, actuaciones) son y seguiran siendo <strong>propiedad del Usuario</strong>.</li>
            <li>LegalWeb actua unicamente como <strong>Encargado del Tratamiento</strong> conforme al Art. 25 del Decreto 1377 de 2013.</li>
            <li>El Usuario puede solicitar en cualquier momento la <strong>exportacion</strong> de sus datos en formato estandar.</li>
            <li>En caso de terminacion del servicio, LegalWeb mantendra los datos disponibles para descarga durante <strong>treinta (30) dias</strong>, transcurridos los cuales seran eliminados de forma segura.</li>
        </ul>

        <h2>9. Propiedad Intelectual</h2>
        <p>Todos los elementos de la Plataforma (diseno, codigo fuente, logotipos, textos, interfaces, algoritmos) son propiedad exclusiva de LegalWeb y estan protegidos por la legislacion colombiana e internacional de propiedad intelectual (Ley 23 de 1982, Ley 1915 de 2018, Decision Andina 351 de 1993). Se prohibe su reproduccion, modificacion, distribucion o uso no autorizado.</p>

        <h2>10. Limitacion de Responsabilidad</h2>
        <p>LegalWeb:</p>
        <ul>
            <li><strong>No es responsable</strong> por el resultado de los procesos legales gestionados a traves de la Plataforma.</li>
            <li><strong>No garantiza</strong> la disponibilidad ininterrumpida del servicio, aunque hara esfuerzos comercialmente razonables por mantener una disponibilidad del 99.5%.</li>
            <li><strong>No es responsable</strong> por la veracidad, legalidad o actualidad de la informacion registrada por los usuarios.</li>
            <li><strong>No reemplaza</strong> la obligacion del abogado de cumplir con sus deberes profesionales, incluyendo el control de terminos procesales, la revision de documentos y la asesoria a sus clientes.</li>
            <li><strong>No asume responsabilidad</strong> por perdida de datos derivada de caso fortuito, fuerza mayor, acciones de terceros o negligencia del usuario.</li>
            <li><strong>No es responsable</strong> por decisiones tomadas con base en contenidos generados por el Asistente IA sin la debida revision profesional.</li>
            <li>La responsabilidad maxima de LegalWeb por cualquier reclamacion no excedera el valor pagado por el Usuario en los ultimos <strong>doce (12) meses</strong> de servicio.</li>
        </ul>

        <h2>11. Indemnizacion</h2>
        <p>El Usuario se compromete a indemnizar y mantener indemne a LegalWeb, sus directores, empleados y representantes, de cualquier reclamacion, demanda, perdida, dano o gasto (incluyendo honorarios de abogados) que surja de:</p>
        <ul>
            <li>El incumplimiento de estos Terminos.</li>
            <li>El uso negligente o indebido de la Plataforma.</li>
            <li>La violacion de derechos de terceros, incluyendo derechos de privacidad.</li>
            <li>La publicacion de informacion falsa, ilegal o que infrinja derechos de propiedad intelectual.</li>
            <li>El uso de contenidos generados por IA sin la debida revision profesional.</li>
        </ul>

        <h2>12. Planes y Pagos</h2>
        <ul>
            <li>La Plataforma ofrece planes con diferentes niveles de funcionalidad y capacidad.</li>
            <li>Los pagos se realizan a traves de la pasarela Wompi y se rigen por sus terminos de servicio.</li>
            <li>El incumplimiento en el pago podra resultar en la suspension o degradacion del plan.</li>
            <li>No se realizan reembolsos por periodos parciales de uso, salvo disposicion legal en contrario.</li>
            <li>LegalWeb se reserva el derecho de modificar los precios con <strong>treinta (30) dias</strong> de preaviso.</li>
        </ul>

        <h2>13. Suspension y Terminacion</h2>
        <p>LegalWeb se reserva el derecho de suspender o terminar el acceso cuando:</p>
        <ul>
            <li>Se detecte uso indebido, fraudulento o contrario a estos terminos.</li>
            <li>Se incumplan las obligaciones de pago.</li>
            <li>Se reciba orden judicial o administrativa.</li>
            <li>Se comprometa la seguridad de la Plataforma o de datos de otros usuarios.</li>
            <li>Se utilice la Plataforma para actividades ilicitas o contrarias a la etica profesional.</li>
        </ul>
        <p>El Usuario puede cancelar su cuenta en cualquier momento. Aplica la clausula de retencion de datos del numeral 8.</p>

        <h2>14. Disponibilidad y Mantenimiento</h2>
        <ul>
            <li>LegalWeb realizara mantenimientos programados preferiblemente en horarios de baja demanda.</li>
            <li>Se notificaran mantenimientos mayores con al menos <strong>24 horas</strong> de anticipacion.</li>
            <li>LegalWeb no sera responsable por interrupciones causadas por terceros (proveedores de hosting, internet, ataques informaticos).</li>
        </ul>

        <h2>15. Ley Aplicable y Resolucion de Controversias</h2>
        <p>Estos terminos se rigen por las leyes de la Republica de Colombia. Las partes acuerdan:</p>
        <ul>
            <li>Intentar resolver cualquier controversia de manera directa y amigable.</li>
            <li>En caso de no lograrlo, acudir a un Centro de Conciliacion y Arbitraje autorizado en Bogota D.C.</li>
            <li>Subsidiariamente, someterse a la jurisdiccion de los jueces y tribunales de Bogota D.C., Colombia.</li>
        </ul>

        <h2>16. Modificaciones</h2>
        <p>LegalWeb se reserva el derecho de modificar estos terminos. Los cambios seran notificados con al menos <strong>quince (15) dias</strong> de antelacion. El uso continuado despues de la fecha de vigencia constituye aceptacion de los nuevos terminos. Para cambios sustanciales se requerira aceptacion expresa.</p>

        <h2>17. Disposiciones Finales</h2>
        <ul>
            <li>Si alguna clausula es declarada nula o inaplicable, las demas mantendran plena vigencia.</li>
            <li>La falta de ejercicio de un derecho no constituye renuncia al mismo.</li>
            <li>Estos terminos constituyen el acuerdo completo entre las partes respecto al uso de la Plataforma.</li>
        </ul>

        <h2>18. Contacto</h2>
        <p>Para consultas sobre estos terminos: <strong>contacto@legalweb.com.co</strong></p>
        <p>Para solicitudes de datos personales: <strong>datos@legalweb.com.co</strong></p>
    </div>
@endsection
