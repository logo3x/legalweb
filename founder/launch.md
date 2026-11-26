# Lanzamiento: LegalWeb

LegalWeb ya está en producción. Lo que se lanza es **la oferta «Vigilancia en Paralelo»**, y solo después de saldar la deuda de confiabilidad.

## 1. Probar antes de gastar

Dos pruebas reales, baratas, antes de invertir en mercadeo:

**Prueba A: la cohorte que ya existe.** El código aplica los límites del plan Gratuito desde el **12 de diciembre de 2026** (`FirmPlan::FREE_LIMITS_FROM`). Ese día las firmas cuya prueba terminó caen a 5 casos y revisión semanal. Es la primera medición real de prueba → pago y no cuesta nada.
- *Antes del 12:* contar cuántas firmas tienen más de 5 casos activos (son las que sentirán el límite).
- *Línea de éxito:* que pague al menos el **10 %** de esas firmas antes del 15 de enero de 2027.
- *Precaución:* el límite llega ocho días antes de la vacancia judicial. Si los arreglos 1 y 2 de `ops.md` no están listos, considere mover la fecha: degradar el servicio a clientes mientras los fallos son invisibles es el peor orden posible.

**Prueba B: diez pilotos en paralelo.** Diez litigantes conocidos, con 20 a 150 procesos en la Rama Judicial, corren LegalWeb 30 días al lado de su método actual, del 12 de enero al 11 de febrero de 2027.
- *Línea de éxito:* al menos 8 de 10 terminan la comparación, **cero actuaciones omitidas** que la fuente sí publicó, y **al menos 3 pagan** al final.
- El panel dio 30 % (6 de 20) como techo. Si los pilotos reales quedan muy por debajo, se les cree a ellos y se vuelve a `offer.md` o `pricing.md`.
- Con su permiso escrito, esos pilotos son los primeros colegas que pueden dar fe: lo único que resuelve la objeción de confianza.

## 2. La cuenta regresiva

Dueño de todas las tareas: el fundador, salvo donde se indica. **En negrita, la ruta crítica.**

| Semana | Tareas | Fecha límite |
| --- | --- | --- |
| 30 nov – 4 dic | **Corregir el calendario judicial (C1). Fallos visibles y señal de vida (C2).** Contar firmas con más de 5 casos | Vie 4 dic |
| 7 – 11 dic | **Corrida nocturna por lotes (C3).** Cerrar `setup.php` (A2). Corregir avisos de etapas (A3). Decidir si el 12 de diciembre se mantiene | Vie 11 dic |
| 12 dic | Entran los límites del plan Gratuito: **empieza la Prueba A** | Sáb 12 dic |
| 14 – 18 dic | Alinear la landing con lo real (A1, A4). Radicado testigo (A5). Cotizar VPS, correo, WhatsApp, abogado revisor, seguro | Vie 18 dic |
| 21 dic – 8 ene (vacancia judicial) | **Informe de los lunes, vista de comparación, plan Individual, WhatsApp.** Integración continua. Texto de la garantía con abogado. Reclutar los 10 pilotos | Vie 8 ene |
| Sáb 9 ene | Ensayo general: una semana de informes y avisos de fallo sobre los procesos propios | Sáb 9 ene |
| **Mar 12 ene 2027** | **Lanzamiento** | — |

Si se atrasa cualquier tarea en negrita, se mueve el lanzamiento, no se recorta la tarea.

## 3. El día del lanzamiento (martes 12 de enero de 2027)

| Hora | Qué |
| --- | --- |
| 3:00 | Corrida nocturna normal |
| 6:30 | Control de salud. **Si la tasa de éxito es menor de 95 %, se aplaza el anuncio un día** |
| 7:00 | Avisos del día a clientes actuales |
| 8:00 | Landing nueva publicada; verificar registro, carga de radicados y pago de punta a punta |
| 9:00 | Correo a usuarios actuales y registrados inactivos |
| 10:00 | Mensaje personal por WhatsApp a los 10 pilotos |
| 12:00 | Publicación del fundador: quién está detrás |
| 14:00 | Activar la búsqueda en Google |
| 17:00 | Revisar pruebas iniciadas, radicados cargados, errores |
| Todo el día | Responder cada mensaje el mismo día |

Si algo se rompe: la rutina de «fuente caída» de `ops.md`. Si falla el pago: se aceptan altas y se cobra al día siguiente; no se detiene la prueba de nadie.

## 4. Los primeros 30 días

| Número semanal | Fuente | Nivel que obliga a cambiar algo |
| --- | --- | --- |
| Suscriptores de pago contra el equilibrio | CFO: 12 sin sueldo, 61 con sueldo | Menos de 5 al día 30 |
| Tasa de éxito de consultas nocturnas | Control de salud | Menos de 95 % cualquier día |
| Actuaciones omitidas reportadas | Quejas | Una sola |
| Pruebas iniciadas y con 10 o más radicados cargados | Panel | Menos de la mitad cargan |
| Costo por cliente de pago | Mercadeo | Más de $144.000 dos semanas seguidas |

**Día 7 (19 ene):** ¿salió el primer informe de los lunes a todos? ¿Cuántos pilotos cargaron sus procesos?
**Día 14 (26 ene):** ¿hubo alguna omisión? ¿Qué canal trajo las pruebas?
**Día 30 (11 feb):** resultado de la Prueba B. ¿Cuántos pagaron, qué plan, qué dijeron los que no? Con ese dato se rehace `numbers.json`.
