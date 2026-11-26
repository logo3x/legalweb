# Auditoría técnica de LegalWeb (solo lectura, 26-nov-2026)

## Veredicto

- **Hoy sí sostiene un negocio pequeño**: la seguridad básica y los pagos están bien hechos. **La promesa central ("sin perder un término") no está respaldada**: el calendario judicial tiene festivos mal calculados y la vigilancia puede fallar sin que nadie se entere.
- **Techo de escala**: unos **1.000–1.500 procesos vigilados por noche** en la mejor configuración posible del hosting actual; con la configuración que documenta el propio repo, **decenas**.
- No pude ejecutar las pruebas: el PHP instalado es 8.3.6 y `vendor/composer/platform_check.php:7` exige 8.4. Todo lo de abajo está verificado leyendo código, salvo lo marcado "sin verificar".

## Inventario funcional

| Módulo | Qué hace | Dónde vive | Madurez |
|---|---|---|---|
| Vigilancia Rama Judicial | Importa radicado, detecta actuaciones nuevas, avisa por correo | `app/Services/{TybaService,CaseSyncService}.php`, `app/Jobs/SyncCaseActuaciones.php` | Frágil |
| Vigilancia SAMAI | Solo detecta cambios de datos generales, **no actuaciones** (captcha) | `app/Services/SamaiService.php` | Frágil |
| Vigilancia de clientes | Busca procesos nuevos de un cliente; hoy por nombre, 25 clientes por noche | `app/Console/Commands/MonitorClientProcesses.php` | Frágil |
| Términos y recordatorios | Días hábiles, recordatorios automáticos, tablero | `JudicialCalendarService.php`, `CheckDeadlines.php`, `routes/web.php:219-255` | Frágil |
| Asistente IA | Resumen, siguiente paso, borradores, con datos ocultos | `AIService`, `AIPseudonymizer`, `AIModelRegistry`, `AIModelHealth` | Funcional |
| Portal del cliente | Enlace con token, solicitud de documentos, registro de accesos | `PortalController.php` | Sólido |
| Pagos y planes | Checkout Wompi, límites por plan, gracia y suspensión | `WompiController`, `SubscriptionActivator`, `FirmPlan` | Sólido (pago único, sin renovación automática) |
| Equipo y permisos por caso | Aislamiento por firma y por caso | `Models/Scopes/FirmScope.php`, `ScopesToCasePermissions.php` | Sólido |
| Expediente y documentos | Lista de documentos y enlaces externos; **no hay subida de archivos del caso** | `DocumentsRelationManager.php` | Funcional |
| Reportes PDF, facturación, analítica | Reporte mensual al cliente, horas y gastos | `SendMonthlyReports.php`, `BillingRelationManager.php` | Funcional (sin pruebas) |
| WhatsApp (Twilio) | Avisos por plantilla | `WhatsAppService.php` | Apagado por defecto (`config/services.php:95`) |
| n8n, correos masivos, códigos de descuento, importación Excel | Automatización y mercadeo interno | `N8nWebhook`, `SendMassEmailCampaign`, `CaseSpreadsheetImporter` | Funcional |

**La landing promete y el código no respalda del todo**: actuaciones de SAMAI; vigilancia "cada noche por cédula o NIT" (la Rama retiró esa consulta, `TybaService.php:15-18`); "sin errores de conteo"; "te recuerda dos días antes" (es un día, `CaseSyncService.php:577`); "documentos centralizados" (solo hay subida de logos y de Excel: `FirmSettings.php:88`, `ListLegalCases.php:154`); "secreto profesional garantizado".
**Construido y no vendido**: n8n, WhatsApp, importación por Excel, correos masivos.

## Hallazgos

### Crítico

**C1. El calendario judicial calcula mal cinco festivos al año.**
- Evidencia: `JudicialCalendarService.php:47-49` suma una semana de más a Ascensión, Corpus y Sagrado Corazón. En 2026 el código marca 25-may, 15-jun y 22-jun; los reales son 18-may, 8-jun y 15-jun. Las líneas `:15-16` trasladan al lunes el 1 de mayo y el 20 de julio, que no se trasladan.
- Impacto: un plazo que cruce esos días sale **un día tarde**. No hay ninguna prueba del calendario.
- Arreglo: corregir y probar contra el calendario oficial 2026–2028. **1 día.**

**C2. Si la vigilancia falla, el abogado no se entera.**
- Evidencia: al fallar la consulta se marca el caso como recién sincronizado (`CaseSyncService.php:72-81`). No se reintenta en 24 horas (7 días en el plan Gratuito, `SyncTybaActuaciones.php:38-41`) y el aviso "sin sincronizar hace más de 48 h" no lo detecta (`DeadlinesDashboard.php:83-91`).
- Solo se envía correo cuando hay novedades (`CaseSyncService.php:190`). El fallo se registra como advertencia y `.env.example:18` fija `LOG_LEVEL=error`, que la descarta.
- No existe señal de vida del cron: si deja de correr, nadie recibe nada.
- Arreglo: no tocar `last_tyba_sync` en fallo, reintentar, avisar tras 2 fallos, y alertar al superadmin si el cron no corre o la tasa de éxito cae. **2–3 días.**

**C3. La sincronización nocturna no escala y depende de una configuración que el repo contradice.**
- Con `QUEUE_CONNECTION=sync` (`.env.example:37`), todo corre dentro de una sola petición HTTP. Cada caso hace de 4 a 8 llamadas, con hasta ~214 s de esperas en el peor caso (`TybaService.php:300-327`, `:366-381`, `:412-419`). El hosting corta la petición y, como no hay orden (`SyncTybaActuaciones.php:20-28`), cada noche se revisan los mismos primeros casos.
- Además, un timeout sin capturar (`TybaService.php:390-396`) aborta toda la corrida.
- Con cola en base de datos el tope es 12 casos por minuto (`SyncTybaActuaciones.php:45`, ventana de 50 s en `routes/web.php:261`). Pero `public/setup.php:95-100` documenta procesar la cola **una vez al día**, y todas las notificaciones van por cola.

| Procesos por noche | Cola en base de datos, cron cada minuto | Modo `sync` |
|---|---|---|
| 100 | ~10 min | Incompleto |
| 1.000 | ~1,5–2 h | Inviable |
| 5.000 | 7–8 h o más: no termina antes de las 8 a.m.; 25–40 mil consultas desde una sola IP | Inviable |

- Sin verificar: el `.env` y los cron reales del servidor.
- Arreglo: lotes ordenados por `last_tyba_sync`, cola cada minuto, tabla de salud. **3–4 días.** Mover a un VPS con worker real: **1–2 semanas.**

### Alto

**A1. SAMAI no vigila actuaciones.** Solo compara ponente, partes y estado (`CaseSyncService.php:198-201`, `SamaiService.php:14-16`); la landing promete avisar cada actuación. Arreglo: corregir el texto hoy (1 h) o integrar el detalle (1–2 semanas, sin verificar viabilidad).

**A2. `public/setup.php` es una llave maestra en una URL.**
- Evidencia: la clave viaja en `?key=` (`:29`), así que queda en el historial del navegador y en los logs del servidor. No tiene límite de intentos.
- Por GET permite nombrar superadmin a cualquier correo (`:598-606`), `git reset --hard` (`:213`), `composer install` (`:105`), sembrar datos (`:236`) y reescribir el `.env` (`:174`). No expone `migrate:fresh`.
- Arreglo: restringir por IP o borrarlo tras cada despliegue, y pasar las acciones a POST. **1 día.**

**A3. Los plazos automáticos son poco fiables.**
- Se cuentan desde la fecha de la actuación con días fijos por palabra clave (`CaseSyncService.php:524-529`, `:682-746`), no desde la notificación.
- Dos actuaciones con el mismo título el mismo día se funden en una (`:107-110`).
- `CheckDeadlines.php:34-38` compara decimales con enteros (Carbon 3.15.1): los avisos de etapas a 3, 1 y 0 días **no se envían nunca**. No tiene prueba y no lo ejecuté.
- Arreglo: **2–3 días** más revisión de un abogado.

**A4. La IA no garantiza el secreto profesional.**
- Radicado, despacho, juez y nombre y NIT de la firma salen sin ocultar (`AIService.php:216-221`, `:257-259`). Con el radicado cualquiera identifica a las partes en la consulta pública.
- Los nombres de terceros en las anotaciones no se ocultan, ni los valores de menos de 5 caracteres (`AIPseudonymizer.php:125`).
- Si falla el proveedor preferido, se usa Gemini gratuito, que puede entrenar con los datos (`AIProviderClient.php:57-63`).
- Arreglo: ocultar el radicado, no usar proveedores que entrenan, ajustar la landing. **1–2 días.**

**A5. Dependencia total de APIs no oficiales, sin detección general.** La URL y el puerto de la Rama están fijos en el código (`TybaService.php:12`). Solo se detectan dos casos concretos (`TybaService.php:239-253`, `SamaiService.php:218-232`). Un cambio de formato devuelve "sin novedades". Arreglo: radicado testigo diario y alerta. **1–2 días.**

**A6. Sin CI y con huecos de pruebas en lo crítico.** No hay pruebas de calendario, `CheckDeadlines`, el bucle de cola del cron, la gracia de suscripciones, los reportes mensuales ni la lectura real de la Rama. `composer.json:9` pide PHP ^8.3, `:90` fija 8.4.0 y `CLAUDE.md` dice 8.5. Arreglo: GitHub Actions y esas pruebas. **2–3 días.**

### Medio

- **Token del cron en la URL**, con una tarea que ejecuta migraciones por GET (`routes/web.php:189`, `:345-347`). Hay una segunda programación con lógica vieja en `routes/console.php:9-22`. 0,5 día.
- **Modelos sin filtro de firma** (`Document`, `CaseEvent`, `BillingEntry`, `Subscription`): el aislamiento depende de que cada pantalla filtre (`ScopesToCasePermissions.php:26-37`). Hoy lo hacen; es una trampa futura. 1 día.
- **Sin retención ni borrado** (Ley 1581): no hay purga de PDFs, de logs del portal con IP ni de registros de IA. Google Analytics carga dentro del panel (`AdminPanelProvider.php:100`). 2 días.
- **Vigilancia de clientes**: 25 por noche (`MonitorClientProcesses.php:16`) y 3 páginas por nombre (`TybaService.php:159`).
- **Wompi**: la activación no usa transacción (`SubscriptionActivator.php:57-66`); posible doble canje de descuento, sin verificar. El webhook principal llega a otra aplicación (`WompiController.php:197-199`).
- **Archivos enormes**: `setup.php` 1.340 líneas, `CaseSyncService.php` 818, 14 closures en `routes/web.php`.

### Bajo

- Consecutivo de casos global entre firmas y con carrera (`LegalCase.php:133-143`).
- `vendor/` versionado (`.gitignore:22`), `dompdf-vendor.zip`, archivo basura en la raíz, README de Laravel.
- Clave antigua de setup en el historial de git (commit `fd44c905`, retirada en `a0a93fe4`). No encontré claves reales de terceros en archivos versionados.

## Lo que está bien hecho

- **Wompi**: firma verificada, transacción reconsultada en la API, validación de monto, moneda y referencia, idempotencia (`WompiController.php:206-259`, `SubscriptionActivator.php:26-55`); 13 pruebas.
- **Aislamiento y archivos**: `FirmScope`, permisos por caso, descargas verificadas (`routes/web.php:97-139`), carpeta privada por firma (`GeneratedFileStore.php`).
- **Portal**: token aleatorio de 64 caracteres revocable (`LegalCase.php:162-174`), límite de peticiones, registro de accesos (`PortalController.php:49-55`).
- **Secretos** solo en `.env`, comparados con `hash_equals` y longitud mínima (`routes/web.php:192`).
- **Correos** con escape de HTML (`SafeMailMarkdown.php`), baja en un clic, y actuaciones sin duplicados por índice único (`CaseSyncService.php:120-140`).

## Las 5 cosas a arreglar primero

1. Corregir el calendario judicial y cubrirlo con pruebas (C1).
2. Hacer visibles los fallos de vigilancia y añadir señal de vida del cron (C2).
3. Rediseñar la corrida nocturna: lotes ordenados, cola cada minuto, reintentos (C3).
4. Cerrar `setup.php` y corregir `CheckDeadlines` (A2, A3).
5. Alinear la landing con lo real (SAMAI, cédula o NIT, IA, documentos) y ocultar el radicado a la IA (A1, A4).
