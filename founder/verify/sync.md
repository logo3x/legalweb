# Verificación adversarial: sincronización judicial (C2, A5)

Prueba: `tests/Feature/VerifySyncAuditTest.php` en la copia ejecutable (no en el repo). `app/` y `.env.example` de la copia son idénticos al repo (`diff -rq` sin diferencias). Resultado: **10 passed (45 assertions)**. Las rutas son de `/home/user/legalweb`.

| # | Afirmación | Veredicto | Evidencia |
|---|---|---|---|
| 1 | C2a: al fallar la consulta se escribe `last_tyba_sync = now()` | **CONFIRMADO** | `app/Services/CaseSyncService.php:72-81` (Rama) y `:207-216` (SAMAI). Prueba con HTTP 503: `status=error` y `last_tyba_sync->equalTo(now())` pasa. |
| 2 | C2b: el comando no reintenta hasta el siguiente ciclo | **CONFIRMADO, con matiz** | Selección: `app/Console/Commands/SyncTybaActuaciones.php:20-28` (`null` o `< now()-24h`); filtro semanal `:38-42`; `sync_every_days=7` en `database/migrations/2026_11_12_105304_add_feature_limits_to_plans.php:40`. Prueba: a las 23 h no se encola nada; a las 25 h solo el caso normal; a los 6 días el Gratuito sigue sin encolarse; a los 8 sí. Matiz: los 7 días solo rigen desde `FREE_LIMITS_FROM = 2026-12-12` (`app/Services/FirmPlan.php:28,163`); antes el Gratuito cuenta como prueba y reintenta a diario. |
| 3 | C2c: el abogado no recibe aviso del fallo | **CONFIRMADO** (sincronización automática) | Único `notify` en `CaseSyncService.php:190-192` y `:287-289`, condicionado a `$hasChanges`. Prueba: `Notification::assertNothingSent()` y 0 filas en `notifications`, también para un superadmin. Excepción: en la sincronización manual sí aparece un aviso rojo "No se pudo consultar" (`app/Filament/Resources/LegalCases/Pages/ViewLegalCase.php:237-245`). |
| 4 | C2d: el aviso de 48 h no detecta estos fallos | **CONFIRMADO** | `app/Filament/Pages/DeadlinesDashboard.php:83-91` (misma consulta en `Reminders/Pages/ListReminders.php:116-123`). Prueba: el conteo pasa de 1 a 0 justo después del fallo. |
| 5 | C2e: no hay señal de vida del cron | **CONFIRMADO** | `routes/console.php:24` sin `onFailure`/ping. `SystemNews::notifySuperadmins` solo se usa en `TybaService.php:242` (búsqueda por cédula 401/404), `SamaiService.php:222` (lista de corporaciones) y `AIChangeNotifier.php:87` (IA). `N8nController::status` (`:18-23`) devuelve `ok` fijo. Ninguno mira la sincronización nocturna. |
| 6 | C2f: `Log::warning` + `LOG_LEVEL=error` descarta el aviso | **PARCIAL** | `.env.example:18`; `config/logging.php:64,71` (por defecto `debug` si falta la variable; no se revisó el `.env` real de producción). El warning de `CaseSyncService.php:73` sí se descarta, pero con 503/timeout `TybaService.php:331` emite `Log::error` y queda registrado (ver salida). Con cambio de formato o SAMAI no queda nada. |
| 7 | A5: cambio de formato = "sin novedades" o fallo silencioso | **CONFIRMADO** (es peor) | Ver salida abajo: tres comportamientos distintos según el endpoint. |
| 8 | `TybaSyncLog` con `error` visible para el abogado | **PARCIAL** | Solo en la pestaña "Historial de sincronización" de cada caso, etiqueta roja "Error" (`RelationManagers/TybaSyncLogsRelationManager.php:48-62`). Ningún tablero, widget ni listado lo agrega; `resources/views` no lo usa. Además la ficha muestra "Última sincronización: hace X" (`LegalCaseResource.php:227-230`) con la fecha del intento fallido. |

## Salida de la prueba

```
[503] log con LOG_LEVEL=error:
testing.ERROR: Tyba API: error en busqueda despues de 3 intentos {"status":503,...}
[JSON sin clave] status=error mensaje=No se pudo consultar el radicado en la Rama Judicial
log(error+): <fin>
[HTML] status=error mensaje=No se pudo consultar el radicado en la Rama Judicial
[actuaciones: clave renombrada] status=sin_cambios mensaje=Sin novedades. 0 actuaciones verificadas
[actuaciones: campos renombrados] status=sin_cambios mensaje=Sin novedades. 0 actuaciones verificadas
[actuaciones: HTTP 500] status=sin_cambios mensaje=Sin novedades. 0 actuaciones verificadas
[sin idProceso] EXCEPCION ErrorException: Undefined array key "idProceso"
[lote] excepcion=Illuminate\Http\Client\ConnectionException: cURL error 28: timeout logs=0
[SAMAI HTML] status=error mensaje=No se pudo consultar el radicado en SAMAI
[SAMAI clave renombrada] status=error mensaje=No se pudo consultar el radicado en SAMAI
Tests: 10 passed (45 assertions)
```

## Lo que la auditoría original dijo mal o exageró

- **C2f**: no todo fallo se pierde del log. Las caídas HTTP y los timeouts de la búsqueda dejan un `Log::error` (`TybaService.php:331`, y `:106` si falla el detalle). Lo que desaparece es el cambio de formato y todo SAMAI.
- **C2b**: "7 días en Gratuito" todavía no aplica; empieza el 2026-12-12.
- **C2c / 8**: el fallo no es invisible del todo: se ve en la sincronización manual y en el historial del caso. Lo que falta es un aviso proactivo y agregado.
- **A5**: "sin novedades o fallo silencioso" se queda corto; hay un tercer resultado (excepción) y la auditoría no distinguió qué endpoint produce cuál.

## Hallazgos nuevos (verificados)

1. **El caso más peligroso de A5 es el endpoint de actuaciones.** Si búsqueda y detalle responden bien pero `Proceso/Actuaciones` devuelve 500, otra clave o campos renombrados, `getActuaciones`/`formatActuaciones` (`TybaService.php:421-426`, `:476`) devuelven lista vacía y el caso queda `sin_cambios` con "Sin novedades. 0 actuaciones verificadas". No hay registro de error, ni log, ni aviso: el abogado ve un caso al día.
2. **Una excepción en un caso aborta el lote nocturno completo con cola `sync`.** `getSujetos` y `getActuaciones` no capturan excepciones (`TybaService.php:392`, `:415`) y `searchByRadicado` accede a `idProceso` sin valor por defecto (`:351`). Con `QUEUE_CONNECTION=sync` (`.env.example:37`) la excepción sale del comando: 0 `TybaSyncLog`, el segundo caso nunca se consulta. La ruta `/cron/{token}` (`routes/web.php:198-201`) respondería 500. Con cola `database` el trabajo acabaría en `failed_jobs` tras 2 intentos, igualmente sin aviso. No se comprobó qué cola usa producción.
3. **SAMAI no avisa al superadmin cuando falla la búsqueda.** `markUnreachable()` solo se llama desde `corporations()` (`SamaiService.php:47-56`), no desde `search()` (`:159-176`).
