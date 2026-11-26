# Verificación adversarial: seguridad y promesas de la landing

Fecha: 2026-11-26. Método: código ejecutado en una copia (PHP 8.3, Apache 2.4 real para el punto 3, pruebas `tests/Feature/Verify*Test.php` en la copia, no en el repositorio). Ningún secreto se imprime aquí.

## A. Seguridad

| # | Afirmación | Veredicto | Evidencia |
|---|---|---|---|
| 1 | `setup.php`: clave en `?key=`, sin límite de intentos, acciones graves por GET | **CONFIRMADO, con matices** | `public/setup.php:28-32`. Sí hay `hash_equals` y mínimo de 20 caracteres; no hay límite de intentos, IP ni caducidad. Sin `SETUP_KEY` (o con menos de 20) responde 403 siempre (ejecutado). |
| 2 | `/cron/{token}/{task?}` | **PARCIAL** | `routes/web.php:189-399`: `hash_equals`, mínimo 20, `throttle:6,1`. El token va en la URL y `task=setup` ejecuta `migrate --force` por GET (`:343`). |
| 3 | La raíz del repo es la raíz web: se sirve `.env`, logs, `vendor/`, `.git/`, `founder/` | **REFUTADO mientras mod_rewrite esté activo** | Probado con Apache real; matriz abajo. |
| 4 | Fuga entre firmas | **REFUTADO (ninguna fuga demostrada)** | 9 pruebas, 38 aserciones, con controles positivos. |
| 5 | Wompi: doble canje sin transacción | **CONFIRMADO por intercalado simulado** (no con concurrencia real) | `app/Services/SubscriptionActivator.php:28,62-71`. Sin cobro recurrente: cada periodo es un pago manual. |
| 6 | Portal: datos de otros casos / token filtrado | **REFUTADO lo primero, CONFIRMADO lo segundo** | `PortalController.php:187-203` limita al caso del token. Google Analytics carga en el portal (`resources/views/layouts/portal.blade.php:10-11`) y envía la URL completa, que contiene el token. También carga en el panel (`AdminPanelProvider.php:100`) con URL y título de página. La app no escribe el token en sus logs. |
| 7 | Secretos en el historial de git | **CONFIRMADO (2, ya inservibles)** | Clave de setup literal en `public/setup.php`, commits `fd44c905` a `a0a93fe4`; token de cron por defecto en `config/app.php`, `48c14a5c` a `a0a93fe4`. Ambos miden menos de 20 y hoy se rechazan. Sin claves de API, `.env` ni credenciales versionadas. |

### Steps de `setup.php` (33)

- **Lectura:** `info` (muestra el token de cron completo), `migrate_status`, `users` (correos y roles), `logs`, `tyba_raw`, `generate_secrets`.
- **Cambia estado:** `composer` (`shell_exec`), `key` (solo si no hay APP_KEY), `migrate`, `seed` (el seeder se niega en producción), `seed_email_templates`, `seed_mass_email_demo`, `storage`, `trim_logos`, `cache`, `clear`, `superadmin` (`?email=`), `demo_reminders`, `test_tyba`, `sync_tyba`, `deadlines`, `verify_ai_models`, `mail_test` (envía correo a `?to=`).
- **Destructivo:** `git_pull` (`git reset --hard origin/main`), `rotate_keys` (reescribe `.env`; único que exige POST y escribir ROTAR), `cleanup_duplicate_events`, `cleanup_duplicate_reminders` (borran sin confirmar), `cleanup_users`, `dedup_actuaciones`, `purge_public_generated`, `migrate_documents` (estos cuatro con `&confirm=yes`).

### Matriz del punto 3 (Apache 2.4, `AllowOverride All`)

| Ruta | Con mod_rewrite | Sin mod_rewrite |
|---|---|---|
| `/.env`, `/.env.example`, `/.env.backup-*` | 403 | 403 |
| `/storage/logs/laravel.log`, `*.sql`, `*.zip` | 403 | 403 |
| `/composer.json`, `/composer.lock`, `/artisan`, `/phpunit.xml` | 403 | 403 |
| `/.git/config`, `/.git/HEAD` | 403 | **expuesto** |
| `/database/database.sqlite`, `/vendor/*`, `/app/*`, `/config/*`, `/routes/*` | va a `index.php` (404 de Laravel) | **expuesto** |
| `/storage/app/private/*`, `/storage/framework/sessions/*` | va a `index.php` | **expuesto** |
| `/founder/*` (114 archivos versionados), `README.md`, `CLAUDE.md`, `.mcp.json`, `.claude/*` | va a `index.php` | **expuesto** |
| `/setup.php`, `/public/setup.php`, `/robots.txt`, `/storage/*` (disco público) | servido | servido |

Lo que protege es una sola regla: `.htaccess:11-12` reescribe todo a `public/`. El `FilesMatch` de respaldo (`.htaccess:4`) solo cubre nombres de archivo, no carpetas. Si el sitio funciona, mod_rewrite está activo (sin él la portada tampoco carga). Ningún subdirectorio trae un `.htaccess` que anule la regla. Residual: los documentos antiguos que sigan en el disco público se bajan sin sesión hasta ejecutar `migrate_documents`.

## B. Promesas de la landing (`resources/views/welcome.blade.php`)

| | Promesa (línea) | Veredicto | Evidencia |
|---|---|---|---|
| a | Vigila Rama Judicial y SAMAI (737, 814) | RESPALDADA | `CaseSyncService.php:65,203`; `SamaiService.php`. Plan Gratuito: semanal. |
| b | Vigilancia por cédula o NIT cada noche (827) | PARCIAL | Busca por nombre porque la Rama ya no admite documento (`TybaService.php:31-50`); 25 clientes por noche en total (`MonitorClientProcesses.php:16,31`); solo corre por el cron HTTP. |
| c | "te recuerda dos días antes" (821) | PARCIAL | Recordatorio un día antes a las 8:00 (`CaseSyncService.php:577`); flujos a 3, 1 y 0 días (`CheckDeadlines.php:38`). |
| d | "Sin errores de conteo" (955) | **NO RESPALDADA** | Ver hallazgo nuevo 1. |
| e | Expediente digital: documentos y evidencias (941) | PARCIAL | No hay carga de archivos al caso: el documento es una lista con enlace externo (`DocumentForm.php:99`); ningún código escribe `file_path`. |
| f | Art. 74: secreto garantizado (1138) | NO RESPALDADA | Hay aislamiento por firma y permisos por caso, pero sin segundo factor ni registro de accesos del panel, y Analytics en panel y portal. |
| g | Ley 1581: cumplimiento (1133) | PARCIAL | Registro de aceptación (`FirmSettings.php:252`, `PortalController.php:73`) y baja de correos; sin retención, borrado ni atención de solicitudes de titulares. |
| h | Hasta 20 radicados (921) | RESPALDADA | `ListLegalCases.php:187,213`. |
| i | 21 flujos (917) | PARCIAL | 21 definidos en `DatabaseSeeder.php`, que se niega a correr en producción (`:42`); ninguna migración los carga. |
| j | La IA solo ve `[CLIENTE]` (842) | RESPALDADA con salvedad | `AIPseudonymizer.php`, `AIService.php:65-69`. Radicado, despacho y juez no se ocultan; nombres de terceros en texto libre pueden pasar. |
| k | Primero proveedores que no entrenan (841) | RESPALDADA | `AIModelRegistry.php:82-86`. Solo OpenRouter con `deny` cuenta; si falla, pasa a Gemini o NVIDIA. |
| l | Cancela cuando quieras; datos se conservan siempre (1072, 1035) | RESPALDADA | No hay cobro recurrente ni código de purga. Choca con (g): conservar siempre no es una política de retención. |
| m | 3 meses gratis (1034) | RESPALDADA | `GoogleController.php:145`, `FirmPlan.php:88-90`. |

## Lo que la auditoría original dijo mal o exageró

- Reescribir el `.env` **no** es por GET: exige POST y confirmación escrita.
- "Sembrar datos": el seeder principal se niega en producción.
- "Sin comparación en tiempo constante": setup y cron usan `hash_equals` y mínimo de 20.
- `FirmScope` sin usuario no produjo ninguna fuga: las rutas públicas filtran por token o firma, y los recursos sin scope filtran por el caso.
- La exposición de `.env`, `vendor/` y `.git/` no ocurre con la configuración actual.

## Hallazgos nuevos verificados

1. **Calendario de festivos equivocado** (`JudicialCalendarService.php:15-16,47-49`). En 2026 faltan 1 de mayo, 18 de mayo y 8 de junio; sobran 4 y 25 de mayo y 22 de junio. Ascensión, Corpus y Sagrado Corazón quedan una semana tarde; 1 de mayo y 20 de julio se trasladan a lunes sin serlo. Cinco días hábiles desde el 20 de mayo de 2026 dan 28 de mayo; lo correcto es 27: un día tarde.
2. **Código de descuento de un solo uso canjeado dos veces sin concurrencia**: el límite se valida al abrir el checkout y se cuenta al aprobar (`WompiController.php:91-99`); dos checkouts abiertos antes de pagar dejan `current_uses = 2`.
3. **Token del portal enviado a Google Analytics** (punto 6).
4. `setup.php`: `cleanup_users` y `dedup_actuaciones` fallan sin `confirm=yes` por variable no definida (`:301`, `:903`); con `confirm=yes` ejecutan directo.
5. `founder/` está versionado dentro de la raíz web: hoy bloqueado, pero depende de la única regla del punto 3.
