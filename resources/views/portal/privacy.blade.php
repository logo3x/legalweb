@extends('layouts.portal')

@section('title', 'Politica de Privacidad')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 prose prose-gray max-w-none">
        <h1>Politica de Privacidad y Tratamiento de Datos Personales</h1>
        <p class="text-sm text-gray-500">Ultima actualizacion: {{ date('d/m/Y') }}</p>

        <p>En cumplimiento de la <strong>Ley Estatutaria 1581 de 2012</strong> "Por la cual se dictan disposiciones generales para la proteccion de datos personales", el <strong>Decreto 1377 de 2013</strong> (compilado en el Decreto 1074 de 2015) y demas normatividad concordante, LegalWeb establece la siguiente politica de tratamiento de datos personales.</p>

        <h2>1. Responsable del Tratamiento</h2>
        <table class="text-sm">
            <tr><td class="font-semibold pr-4">Razon Social</td><td>LegalWeb S.A.S.</td></tr>
            <tr><td class="font-semibold pr-4">Domicilio</td><td>Barrancabermeja, Santander, Colombia</td></tr>
            <tr><td class="font-semibold pr-4">Correo electronico</td><td>legalwebco@gmail.com</td></tr>
            <tr><td class="font-semibold pr-4">Telefono</td><td>-</td></tr>
        </table>

        <h2>2. Datos Personales Recopilados</h2>
        <p>La Plataforma recopila y trata las siguientes categorias de datos:</p>

        <h3>2.1. Datos de Abogados/Usuarios</h3>
        <ul>
            <li>Nombre completo y datos de identificacion</li>
            <li>Correo electronico y telefono de contacto</li>
            <li>Numero de tarjeta profesional</li>
            <li>Informacion de la firma o despacho juridico</li>
            <li>Datos de acceso (credenciales cifradas)</li>
        </ul>

        <h3>2.2. Datos de Clientes</h3>
        <ul>
            <li>Nombre completo</li>
            <li>Tipo y numero de documento de identidad</li>
            <li>Correo electronico y telefono de contacto</li>
            <li>Direccion y ciudad</li>
        </ul>

        <h3>2.3. Datos Sensibles</h3>
        <p>Dada la naturaleza de los servicios juridicos, la Plataforma puede almacenar informacion relacionada con:</p>
        <ul>
            <li>Procesos judiciales y administrativos</li>
            <li>Informacion sobre controversias legales</li>
            <li>Documentos judiciales y probatorios</li>
        </ul>
        <p>Estos datos se consideran <strong>datos sensibles</strong> conforme al Art. 5 de la Ley 1581 de 2012 y reciben un nivel de proteccion reforzado. Su tratamiento requiere <strong>autorizacion expresa</strong> del titular.</p>

        <h3>2.4. Datos de Navegacion</h3>
        <ul>
            <li>Direccion IP</li>
            <li>Fecha y hora de acceso</li>
            <li>Navegador y sistema operativo (User Agent)</li>
            <li>Cookies de sesion estrictamente necesarias</li>
        </ul>

        <h2>3. Finalidades del Tratamiento</h2>
        <p>Los datos personales seran utilizados para:</p>
        <ul>
            <li>Prestacion del servicio de gestion de casos juridicos</li>
            <li>Permitir el acceso del cliente al portal de seguimiento de su caso</li>
            <li>Envio de notificaciones relacionadas con el avance del caso</li>
            <li>Generacion de reportes y estadisticas de uso (datos anonimizados)</li>
            <li>Cumplimiento de obligaciones legales y requerimientos judiciales</li>
            <li>Garantizar la seguridad y trazabilidad del sistema</li>
            <li>Atencion de consultas, quejas y reclamos</li>
        </ul>

        <h2>4. Autorizacion y Consentimiento</h2>
        <p>Conforme al Art. 9 de la Ley 1581 de 2012:</p>
        <ul>
            <li>El <strong>abogado</strong> otorga su autorizacion al registrarse en la Plataforma y aceptar los Terminos y Condiciones.</li>
            <li>El <strong>cliente</strong> otorga su autorizacion al aceptar los terminos del Portal del Cliente al acceder por primera vez.</li>
            <li>Para datos sensibles, se requiere autorizacion <strong>expresa y previa</strong> del titular, la cual se obtiene a traves del abogado responsable del caso.</li>
        </ul>
        <p>El abogado que registra datos de sus clientes declara haber obtenido previamente la autorizacion de estos para el tratamiento de sus datos personales conforme a la ley.</p>

        <h2>5. Derechos de los Titulares (Art. 8, Ley 1581 de 2012)</h2>
        <p>Los titulares de datos personales tienen derecho a:</p>
        <ul>
            <li><strong>Conocer, actualizar y rectificar</strong> sus datos personales.</li>
            <li><strong>Solicitar prueba</strong> de la autorizacion otorgada.</li>
            <li>Ser informado sobre el <strong>uso</strong> que se ha dado a sus datos.</li>
            <li>Presentar <strong>quejas</strong> ante la Superintendencia de Industria y Comercio (SIC) por infracciones a la ley.</li>
            <li><strong>Revocar</strong> la autorizacion y/o solicitar la <strong>supresion</strong> de sus datos, cuando no exista obligacion legal o contractual que lo impida.</li>
            <li>Acceder de forma <strong>gratuita</strong> a sus datos personales.</li>
        </ul>

        <h2>6. Procedimiento para Ejercer sus Derechos</h2>
        <p>Los titulares pueden ejercer sus derechos mediante comunicacion escrita dirigida a <strong>legalwebco@gmail.com</strong> indicando:</p>
        <ul>
            <li>Nombre completo y documento de identidad del titular</li>
            <li>Descripcion de los hechos y solicitud concreta</li>
            <li>Direccion de correspondencia y datos de contacto</li>
            <li>Documentos que soporten la solicitud (si aplica)</li>
        </ul>
        <p>Plazos de respuesta conforme al Art. 14 de la Ley 1581 de 2012:</p>
        <ul>
            <li><strong>Consultas:</strong> maximo diez (10) dias habiles desde la recepcion.</li>
            <li><strong>Reclamos:</strong> maximo quince (15) dias habiles desde la recepcion.</li>
        </ul>

        <h2>7. Medidas de Seguridad</h2>
        <p>LegalWeb implementa medidas tecnicas, humanas y administrativas para proteger los datos personales, incluyendo:</p>
        <ul>
            <li><strong>Cifrado</strong> de datos en transito (TLS/SSL) y en reposo</li>
            <li><strong>Control de acceso</strong> basado en roles con autenticacion segura</li>
            <li><strong>Copias de seguridad</strong> periodicas con almacenamiento cifrado</li>
            <li><strong>Registro de auditoria</strong> de accesos y operaciones sobre datos sensibles</li>
            <li><strong>Politicas de contrasenas</strong> robustas y autenticacion de dos factores (opcional)</li>
            <li><strong>Segregacion de datos</strong> entre diferentes firmas y abogados</li>
            <li>Capacitacion periodica al personal sobre proteccion de datos</li>
        </ul>

        <h2>8. Secreto Profesional</h2>
        <p>La Plataforma reconoce y protege el <strong>secreto profesional abogado-cliente</strong> consagrado en:</p>
        <ul>
            <li>Art. 74 de la Constitucion Politica de Colombia</li>
            <li>Art. 28 de la Ley 1123 de 2007 (Codigo Disciplinario del Abogado)</li>
            <li>Art. 385 del Codigo General del Proceso (deber de confidencialidad)</li>
        </ul>
        <p>La informacion de los casos juridicos almacenada en la Plataforma se considera protegida por el secreto profesional. LegalWeb no accedera al contenido de los casos salvo para fines tecnicos estrictamente necesarios (soporte, mantenimiento) y con las debidas garantias de confidencialidad.</p>

        <h2>9. Transferencia y Transmision de Datos</h2>
        <p>LegalWeb podra realizar:</p>
        <ul>
            <li><strong>Transmision nacional:</strong> a proveedores de servicios tecnologicos (hosting, correo electronico) que actuan como Encargados del Tratamiento, con quienes se suscriben contratos de transmision de datos conforme al Art. 25 del Decreto 1377 de 2013.</li>
            <li><strong>Transferencia internacional:</strong> unicamente a paises que cuenten con niveles adecuados de proteccion de datos conforme a la certificacion de la SIC, o bajo las excepciones previstas en el Art. 26 de la Ley 1581 de 2012.</li>
        </ul>

        <h2>10. Conservacion de Datos</h2>
        <ul>
            <li>Los datos de abogados se conservan mientras la cuenta este activa y hasta <strong>un (1) ano</strong> despues de su cancelacion.</li>
            <li>Los datos de casos y clientes se conservan durante la vigencia de la relacion profesional y por un periodo adicional de <strong>cinco (5) anos</strong> para cumplir con obligaciones legales de conservacion documental.</li>
            <li>Los datos de navegacion y logs de acceso se conservan por <strong>seis (6) meses</strong>.</li>
            <li>Transcurridos estos plazos, los datos seran eliminados de forma segura o anonimizados.</li>
        </ul>

        <h2>11. Cookies</h2>
        <p>La Plataforma utiliza unicamente <strong>cookies de sesion estrictamente necesarias</strong> para el funcionamiento del servicio. No se utilizan cookies de rastreo, publicitarias ni de terceros.</p>

        <h2>12. Vigencia</h2>
        <p>Esta politica entra en vigencia a partir de su publicacion y permanecera vigente mientras LegalWeb trate datos personales. Cualquier modificacion sera comunicada a los titulares con al menos <strong>quince (15) dias</strong> de anticipacion.</p>

        <h2>13. Autoridad de Control</h2>
        <p>La <strong>Superintendencia de Industria y Comercio (SIC)</strong> es la autoridad encargada de vigilar el cumplimiento de la normatividad de proteccion de datos personales en Colombia.</p>
        <table class="text-sm">
            <tr><td class="font-semibold pr-4">Direccion</td><td>Barrancabermeja, Santander</td></tr>
            <tr><td class="font-semibold pr-4">Correo</td><td>legalwebco@gmail.com</td></tr>
            <tr><td class="font-semibold pr-4">Sitio web</td><td>www.sic.gov.co</td></tr>
        </table>
    </div>
@endsection
