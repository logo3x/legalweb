# Brief para la junta: LegalWeb (SaaS de vigilancia judicial para abogados en Colombia)

Este documento es todo lo que sabes del negocio. No hay más contexto. Todos los precios están en pesos colombianos (COP). Referencia: 1 USD ≈ 4.000 COP (aproximado, solo para orden de magnitud).

## La idea

- **Qué es:** un SaaS web (legalweb.com.co) que vigila automáticamente los procesos judiciales de un abogado colombiano. Cada madrugada (3:00 a.m.) consulta la Rama Judicial (consulta de procesos) y SAMAI (Consejo de Estado y tribunales administrativos) y envía un correo cuando aparece una actuación nueva. Alrededor de eso hay una suite de gestión de la práctica.
- **Para quién:** abogados litigantes independientes y firmas pequeñas en Colombia (1 a 10 abogados), con 10 a 200 procesos activos, que hoy revisan proceso por proceso en las páginas oficiales, usan Excel, o pagan un dependiente judicial o un servicio de vigilancia.
- **Precio:** prueba de 3 meses con todo incluido, sin tarjeta. Después: plan **Gratuito** para siempre (hasta 5 casos activos, 1 usuario, 10 consultas de IA al mes, sincronización semanal, sin vigilancia por cédula/NIT); **Profesional** $120.000/mes o $600.000 por 6 meses (casos ilimitados, hasta 3 usuarios); **Firma** $250.000/mes o $1.250.000 por 6 meses (hasta 10 usuarios). Cobro con Wompi (pasarela colombiana).
- **Cómo llega al cliente:** landing propia, registro con Google en 60 segundos, correos automáticos de ciclo de vida, módulo interno de campañas de correo masivo (26 plantillas). No se conoce inversión en pauta ni canal de ventas.
- **Presupuesto y equipo:** no declarados. Por el repositorio se infiere un fundador único que construye con asistentes de IA de programación. Hosting compartido (cPanel, sin SSH, tareas programadas disparadas por HTTP). No se conocen: número de firmas registradas, clientes que pagan, churn, costos mensuales ni presupuesto de mercadeo.

## Lo que el producto hace hoy (verificado en el código, noviembre de 2026)

1. **Vigilancia judicial:** importa un proceso por radicado (trae despacho, juez, sujetos, actuaciones), re-sincroniza a diario, detecta actuaciones nuevas y avisa por correo con lo que cambió. Importación masiva de hasta 20 radicados e importación desde Excel. Búsqueda de procesos por nombre, cédula o radicado en todas las fuentes a la vez.
2. **Vigilancia de clientes por cédula/NIT:** cada noche busca a los clientes del abogado en la Rama Judicial y avisa si aparecen en un proceso que el abogado aún no tiene (por ejemplo, demandaron a su cliente). Nota: la Rama Judicial retiró recientemente la consulta por cédula, y el producto tuvo que pasar a buscar por nombre.
3. **Términos y agenda:** calculadora de términos en días hábiles con calendario judicial colombiano, tablero de vencimientos, recordatorios, reglas que crean vencimientos automáticos según el tipo de actuación (auto admisorio, inadmisorio, traslado, sentencia, etc.), 21 flujos procesales (CGP, CPT, Ley 906, CPACA).
4. **Portal del cliente:** enlace seguro sin cuenta; el cliente ve el estado del proceso, acepta términos, y responde solicitudes de documentos. Reporte mensual en PDF automático por correo.
5. **IA jurídica:** resumen del caso, siguiente paso sugerido, borradores en Word (demanda, tutela, memorial, poder...). Los datos personales se reemplazan por marcadores antes de salir al proveedor de IA (seudonimización) y se prefieren proveedores que no entrenan con los datos. Usa modelos de nivel gratuito de tres proveedores (Gemini, OpenRouter, NVIDIA) con un monitor que verifica cada día qué modelos siguen funcionando.
6. **Gestión:** expediente digital, clientes, equipo con permisos por caso, facturación por caso (horas, gastos), reportes, analítica de despachos (cuánto tarda cada juzgado en los procesos propios), marca blanca parcial (logo de la firma en panel, correos y documentos Word), integración con n8n por webhooks, notificaciones por WhatsApp construidas pero apagadas.
7. **Negocio:** planes con límites aplicados en código, pagos con Wompi, códigos de descuento, ciclo de impago en 4 etapas con modo solo-lectura, CRM interno de firmas registradas, correos masivos.

## Hechos técnicos relevantes para evaluar riesgo

- La fuente de datos son **APIs públicas no documentadas ni contractuales** de la Rama Judicial y SAMAI. Ya cambiaron al menos una vez (retiro de la consulta por cédula). Muchas actuaciones llegan con la anotación vacía; no hay acceso por API a los documentos (autos, providencias) de cada actuación.
- La IA depende de **niveles gratuitos** de terceros que cambian sin aviso.
- 22.000 líneas de PHP propio (Laravel 13 + Filament), 30 archivos de pruebas automáticas (~230 pruebas), varias rondas de auditoría de seguridad (OWASP) corregidas en noviembre de 2026.
- Infraestructura: hosting compartido, sin integración continua.

## Lo que NO se sabe (no lo inventes)

Tamaño real del mercado (número de abogados litigantes activos en Colombia), costo de adquirir un cliente, cuántos usuarios se registran y cuántos pagan, precios y cuota de los competidores, costos mensuales del negocio. Si tu lente necesita uno de esos números, nómbralo como pregunta abierta.
