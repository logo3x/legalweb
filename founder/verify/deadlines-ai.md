# Verificación adversarial: términos, recordatorios e IA

Todo se ejecutó en la copia de trabajo (PHP 8.3, Carbon 3.15.1, `Http::fake`, sin llamadas externas). Rutas relativas a `/home/user/legalweb`. Pruebas: `tests/Feature/VerifyDeadlinesAiTest.php` (11 pasan) y `VerifyAiFallbackTest.php`, solo en la copia.

| # | Afirmación | Veredicto | Evidencia |
|---|---|---|---|
| 1 | Calendario de festivos erróneo | CONFIRMADO | `app/Services/JudicialCalendarService.php:15-16` (1-may y 20-jul marcados trasladables) y `:48-50` (`->next(MONDAY)` sobre fechas que ya son lunes). 11,1 % de los cálculos difieren (cifras abajo). |
| 2 | Avisos de etapa a 3/1/0 días nunca se envían | CONFIRMADO (con un caso teórico) | `app/Console/Commands/CheckDeadlines.php:34-38`. Reloj real: `diffInDays=7.000009…` → restante `2.99999…` → 0 avisos. Con 15 desfases de hora: 0 avisos. |
| 3 | Recordatorios desde la fecha de la actuación, días fijos por palabra clave | CONFIRMADO | `app/Services/CaseSyncService.php:524-529`, `:577`, `:678-799`. "Auto inadmite" del 05/11 → vence 12/11, `remind_at` 11/11 08:00. |
| 4 | Dos actuaciones con igual título el mismo día se funden | CONFIRMADO | `CaseSyncService.php:107-118` y `:132-137`. La Rama entrega 4, se guardan 2; log: "2 nueva(s) actuacion(es) de 4 totales". |
| 5 | SAMAI no detecta actuaciones nuevas | CONFIRMADO | `CaseSyncService.php:198-201`, `:52-59`; `app/Services/SamaiService.php:14-16`, `:191-214`. Resultado: `sin_cambios`, "Sin novedades en SAMAI", 0 eventos, 0 avisos. |
| 6 | La IA recibe datos sin ocultar | PARCIAL | `app/Services/AIPseudonymizer.php:13-15`, `:125`, `:137`, `:189`; `app/Services/AIService.php:213-260`. Lista abajo. |
| 7 | Respaldo a proveedor que puede entrenar | CONFIRMADO | `app/Services/AIModelRegistry.php:82-97`, `:145-148`, `:500-512`; `AIProviderClient.php:57-63`, `:159-161`. |
| 8 | Vigilancia "por cédula o NIT" busca por nombre | CONFIRMADO | `app/Services/TybaService.php:15-18`, `:31-67`, `:239-241`; `MonitorClientProcesses.php:17`, `:46-47`. 4 de 4 homónimos aceptados. |

## 1. Cifras del calendario

Referencia: festivos de la Ley 51 tal como se pidió; misma vacancia y Semana Santa que el código (aísla el error de festivos). 460 días hábiles de inicio (2026-2027) × términos de 3, 5, 10 y 20 = **1.840 cálculos**.

- Distintos: **205 (11,1 %)**. TARDE: **115 (6,2 %)**. TEMPRANO: **90 (4,9 %)**. Desviación máxima: 4 días calendario en ambos sentidos.
- 2026: 37 tarde, 45 temprano. 2027: 78 tarde, 45 temprano.
- 7 días que el código trata como festivos son hábiles (04-may, 25-may, 22-jun de 2026; 03-may, 17-may, 14-jun, 26-jul de 2027).

TARDE:
- Inicio 20-may-2026, 3 días: código 26-may, correcto 25-may (+1).
- Inicio 11-jun-2026, 10 días: código 30-jun, correcto 26-jun (+4).

TEMPRANO:
- Inicio 17-abr-2026, 10 días: código 1-may (festivo real), correcto 4-may (−3).
- Inicio 5-jun-2026, 5 días: código 12-jun, correcto 16-jun (−4).

Lo que hace el código, sin opinar de derecho:
- Vacancia: 20-dic a **11-ene inclusive** (`:80-85`), no al 10. No cambia nada en 2026-2027 (11-ene cae en domingo y en festivo); sí el martes 11-ene-2028: con vacancia hasta el 10, las diferencias suben a 243 (13,2 %) y las tardías a 153.
- Semana Santa: solo jueves a domingo (`:87-94`); lunes, martes y miércoles santos cuentan como hábiles (medido 30-mar a 1-abr-2026).
- Sábados y domingos: no hábiles. El conteo empieza el día siguiente al inicio sin validar si el inicio es hábil.
- Afecta la calculadora (`app/Filament/Pages/TermCalculator.php:46`) y los recordatorios automáticos (`CaseSyncService.php:525`).

## 2. ¿Algún caso en que sí avise?

Solo si `updated_at` tiene la misma hora:minuto:segundo que `now()` y `now()` no trae microsegundos: con el reloj congelado a 08:00:00 envió 3 avisos; con 08:00:00.000350, 0. En producción es prácticamente imposible. Además: los días son calendario (el correo dice "días hábiles"), `updated_at` se reinicia con cualquier edición y un término ya vencido (restante −1) tampoco avisa.

## 3. Reglas reales (palabra clave → días hábiles desde la fecha de la actuación)

- 20: `auto admite`
- 10: `sentencia`
- 5: `auto inadmite`, `auto rechaza`, `auto requiere`, `auto ordena`, `edicto`, `aviso`
- 3: `auto niega`, `auto decide`, `auto decreta`, `auto resuelve`, `traslado`, `fijacion estado`
- 0 (sin recordatorio, salvo fecha en la anotación): `auto reconoce`, `auto aprueba`, `auto concede`, `auto continua`, `fija fecha`/`auto fija`, `audiencia`, memoriales, `notificacion`, `pasa al despacho`, `sustanciacion`, `pasa a fallo`, `constancia`, `embargo`, cualquier otro `auto`
- Sin regla: todo lo demás.

Orden de preferencia: `fechaFinal` de la Rama → fecha escrita en la anotación → regla. Medido: "Fijación estado" y "NOTIFICACIÓN POR ESTADO" (con tilde) → sin regla, porque se usa `strtolower` (`:495`); "Auto que admite demanda" → 0 días (cae en `auto`).

Landing (`resources/views/welcome.blade.php:821`): "te recuerda dos días antes". Código: **un día calendario antes, 8:00** (`:577`); el de la sentencia quedó para el domingo 22-nov. La landing dice además que cuenta desde la notificación por estado; el código cuenta desde la fecha del auto.

## 6. Lo que salió al proveedor (cuerpo capturado, resumen y borrador)

Oculto: nombre del cliente (ficha y "APELLIDOS NOMBRES"), cédula con el formato de la ficha, teléfono, correo y dirección de la ficha, abogado y su correo, contraparte "Banco Andino S.A.", y de terceros la cédula tras "C.C.", el celular y el correo.

Salió sin ocultar:
- Radicado `11001310300520240012300`, número interno, despacho y juez (ficha y línea "Ponente:").
- Nombre, NIT, dirección y ciudad de la firma; ciudad del cliente.
- Terceros por nombre: "Luis Pérez", "Jorge Salazar", "Andrés Gómez" (menor) y su T.I. `1011222333`.
- Variantes del cliente: "Gómez Ríos", "María Fernanda", "Mafe", cédula sin puntos `52345678`, dirección con otra grafía.
- Teléfono fijo `601 7654321`.
- Partes de menos de 5 caracteres: "Ana", "DIAN".
- "Defensor Privado: PEDRO PABLO NIETO" (rol fuera de la lista).

## 7. Orden real de respaldo

1. Modelo elegido por el abogado, si está en la lista verificada (puede ser Gemini).
2. Modelos verificados de proveedores que "no entrenan": solo OpenRouter con `data_collection=deny` (son variantes `:free`).
3. Gemini y NVIDIA (el propio código los marca como "puede entrenar").
4. Hasta 3 candidatos no verificados, intercalados por proveedor.

Medido: OpenRouter con 429 → respondió `gemini:gemini-flash-latest` con el mismo texto. Con `OPENROUTER_DATA_COLLECTION` distinto de `deny`, Gemini va primero. Sin lista verificada ni `OPENROUTER_MODEL`, solo se intenta Gemini y NVIDIA. No hay aviso al usuario cuando cambia de proveedor.

## 8. Homónimos

Con 404/401 en la consulta por documento se busca `nombre=CARLOS PEREZ` (máx. 3 páginas) y se acepta todo proceso donde cada palabra aparezca en cualquier parte: entraron dos "CARLOS PEREZ" de otras ciudades, "CARLOS ALBERTO PEREZ GOMEZ" y uno con "JUAN CARLOS RUIZ" contra "MARIA PEREZ LOPEZ". La cédula no se compara. Con HTTP 200 vacío, 403 o 500 no hay búsqueda por nombre ni aviso.

## Lo que la auditoría original dijo mal o exageró

- "No se envían nunca" (2): existe un caso de coincidencia exacta al microsegundo; en la práctica es nunca.
- IA (6): la contraparte **sí** se oculta, y el nombre sin tildes también cuando figura como parte en la descripción importada. Dejar visibles radicado, despacho y juez es una decisión documentada en el código (`AIPseudonymizer.php:14-15`), no un descuido.
- IA (7): el respaldo no es inmediato; antes se agotan todos los modelos de OpenRouter con `deny`, que es el valor por defecto. En el `.env` del repositorio no hay ninguna clave de IA configurada, así que no pude comprobar qué proveedores usa producción.
- SAMAI (5): la limitación está documentada en el código (captcha); al abogado solo le llega aviso si cambian despacho, ponente, clase, estado o partes.
- Calendario (1): el error es de festivos; el 89 % de los cálculos coincide, y casi la mitad de los errores son por vencer antes, no después.
