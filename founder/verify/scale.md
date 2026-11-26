# Verificación: escalabilidad de la sincronización nocturna

Método: copia de la app (PHP 8.3, SQLite en memoria), `Http::fake` sin red real, 10 pruebas en `tests/Feature/VerifyScaleTest.php` (solo en la copia). Rutas relativas a `/home/user/legalweb`.

## Afirmaciones

| # | Afirmación | Veredicto | Evidencia |
|---|---|---|---|
| 1 | Con `sync` todo corre en una sola petición, sin lotes | CONFIRMADO | `.env.example:37`; `routes/web.php:200`; consulta sin `limit` (`SyncTybaActuaciones.php:20-28`); `sync` ignora el `delay`. Medido: un solo `GET /cron/{token}/sync-tyba` sincronizó 200 casos (800 llamadas). |
| 2 | Sin orden por `last_tyba_sync`; al cortarse, siempre los mismos primeros | PARCIAL | SQL capturado sin ORDER BY. Pero con cupo de 2 casos/noche y 6 casos, medido: noches 1,2,3,4 → casos 1-2, 3-4, 1-2, 3-4; los 5 y 6 nunca. Alterna dos grupos y abandona el resto. |
| 3 | 4 a 8 llamadas HTTP por caso | CONFIRMADO | Medido: 4 con 1 página; 8 con 5; 8 con 9 (tope `TybaService.php:412`). |
| 4 | Hasta ~214 s por caso | CONFIRMADO (son 216 s) | `TybaService.php`: búsqueda 3×20 + 2×2 = 64 (`:300-327`); detalle 3×20 + 2×1 = 62 (`:366-381`); sujetos 15 (`:392`); actuaciones 5×15 = 75 (`:415`). |
| 5 | Un timeout no capturado aborta toda la corrida | PARCIAL | Excepción en el caso 2 de 3, modo sync: en sujetos o actuaciones → sincronizados SI,NO,NO, HTTP 500 y la vigilancia de clientes (`web.php:204`) no corre. En búsqueda o detalle se captura: SI,SI,SI. Con cola en BD: SI,NO,SI y el job se reintenta. |
| 6 | Cola en BD: tope ~12 casos/minuto | PARCIAL | `SyncTybaActuaciones.php:45` libera 1 job cada 5 s (medido: `available_at` 0, 5… 995 s para 200). Con cron cada minuto: 2, 14, 26 casos acumulados. La ventana de 50 s (`web.php:261`) no espera: sale al vaciarse la cola (`:263`). |
| 7 | `setup.php` documenta la cola una vez al día | CONFIRMADO | `public/setup.php:97`. Medido: a las 3:05 solo 61 de 200 jobs están disponibles; dos noches sin vaciar dejan 400 jobs para 200 casos. |
| 8 | Segunda programación con lógica vieja en `console.php:9-22` | CONFIRMADO, con otro alcance | Son los recordatorios (ventana de "última hora", sin `notified_at`) frente a `web.php:219-255`. Además `console.php` no programa la vigilancia de clientes ni procesa la cola. |
| 9 | Vigilancia: 25 clientes por noche | CONFIRMADO | `MonitorClientProcesses.php:16,31`; rota por los menos revisados (`:29-30`). Solo lectura de código. |

## Medición (200 casos, modo sync, sin red)

| Escenario | Total | Por caso | Consultas BD |
|---|---|---|---|
| Primera sync, 1 página (20 actuaciones) | 4,57 s | 23 ms | 11.804 |
| Re-sync sin novedades, 1 página | 3,5 s | 18 ms | 5.004 (25 por caso) |
| Primera sync, 5 páginas (100 actuaciones) | 14,4 s | 72 ms | — |
| Re-sync, 5 páginas | 12,8 s | 64 ms | — |

El cómputo no limita; la red sí. Estimado = llamadas × latencia + cómputo, con 4 a 8 llamadas por caso:

| Latencia | 100 | 500 | 1.000 | 5.000 |
|---|---|---|---|---|
| 0,5 s | 3,4–6,8 min | 17–34 min | 34–68 min | 2,8–5,7 h |
| 1 s | 6,7–13,5 min | 34–67 min | 1,1–2,2 h | 5,6–11,2 h |
| 2 s | 13–27 min | 1,1–2,2 h | 2,2–4,5 h | 11–22 h |

## Techo estimado por noche

Supuestos: el hosting corta la petición entre 30 y 300 s; la noche útil es de 03:00 a 08:00.

- **(a) Sync en una petición:** 3–7 casos con corte de 30 s y 37–74 con 300 s (latencia 1 s); extremos 1–148.
- **(b) Cola en BD, cron cada minuto:** máximo 720/h ≈ 3.600 por noche; con 1–2 s de latencia y 8 llamadas, 900–2.100. Como está documentado (una vez al día): 6–25 por noche.
- **(c) Worker dedicado:** mismo máximo de 720/h (≈3.600 por noche, 17.280 en 24 h) por el escalonado; con 2 s de latencia, 224–449/h.

## Lo que la auditoría original dijo mal o exageró

- "Siempre los mismos primeros casos": alterna dos grupos; el resto no se atiende.
- "Cualquier timeout aborta": solo sujetos y actuaciones, y solo en modo sync.
- "Tope de 12 por minuto": es un ritmo; el cuello real es procesar la cola una vez al día.
- 214 s son 216 s; `console.php:9-22` son recordatorios, no la sincronización.

## Hallazgo que la auditoría no vio

El filtro de 24 h (`SyncTybaActuaciones.php:26`) excluye lo sincronizado ayer segundos después de la misma hora. Medido: tras una corrida a las 03:00:00, al día siguiente a las 03:00:00 encola 0 de 5; a las 03:00:01, 5. Con cron a hora fija cada caso se revisa cada 48 h. Simulado con reloj congelado: falta confirmarlo en producción.

## Límites de esta verificación

SQLite en memoria (MySQL compartido será más lento); latencias supuestas; SAMAI no medido. En Linux `max_execution_time` no cuenta la espera de red: el corte real suele ponerlo el servidor web.
