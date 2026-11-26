# LegalWeb: resultado del análisis a fondo

Fecha: 26 de noviembre de 2026 · Código analizado: commit `b6c8ee75` (rama `main` al 16 de noviembre) · Método: pack `founder-skill` completo, repetido con verificación ejecutada.

## 1. Veredicto

**Todavía no («Not yet»).** Lo calcula `compile.py` a partir de los números, no es una opinión:

- ✓ Cada suscripción deja $84.880 al mes antes de costos fijos (92 %).
- ✗ El año 1 pierde $21.632.080 si el negocio le paga al fundador medio tiempo.
- ✓ 30 de 100 compradores simulados comprarían la oferta propuesta (la barra es 25 %).

Hay un segundo veredicto que la herramienta no calcula y que pesa más: **el producto hoy no cumple su promesa central.** Ejecutando el código se comprobó que calcula mal uno de cada nueve términos y que puede mostrar «sin novedades» cuando en realidad la consulta falló. Eso se arregla en unas tres semanas de trabajo y debe ir antes que cualquier venta.

La junta votó dos veces. Con la información inicial: 3 «financiar si», promedio 4,7 de 10. Con la evidencia verificada: 3 «financiar si», **promedio 3,3** (Ofertas 4, Monopolio 3, Producto 3). La evidencia empeoró la nota.

## 2. Qué se hizo en esta segunda pasada

| Frente | Primera pasada | Segunda pasada |
| --- | --- | --- |
| Código | Lectura, sin ejecutar | **Ejecutado:** 235 pruebas del proyecto (234 pasan; la otra falla solo por la versión de PHP del entorno) y cuatro verificaciones adversariales con pruebas nuevas, cada una encargada de refutar la auditoría |
| Panel de compradores | 20 por oferta, 2 ofertas | **100 por oferta, 3 ofertas** (300 compradores, 0 respuestas fallidas) |
| Competencia | Una lectura | Segunda lectura independiente; 6 competidores nuevos con datos y 7 más solo de nombre |
| Mercado, reglas y costos | Sin investigar | Investigado con fuentes públicas |
| Números | Un escenario | Escenario base, de caja y **simulación de 5.000 corridas** del embudo |
| Junta | Una sesión | Segunda sesión con toda la evidencia |

Límites que no se pudieron superar: la red de la sesión no dejó abrir los sitios de los competidores ni de la Rama Judicial, así que esos datos se leyeron dos veces por buscador, no en la página; y no hubo acceso a producción ni a sus cifras reales (registros, pagos, costos, configuración del servidor).

## 3. Lo que cambió respecto a la primera pasada

**Correcciones a favor:**
- **No hay fuga de datos entre firmas.** Nueve pruebas de ataque, ninguna lo logró.
- **`.env`, el código y la carpeta `founder/` no quedan expuestos por web** mientras el servidor tenga activa la reescritura de URL (probado con Apache real).
- `setup.php` y el cron sí comparan la clave de forma segura y exigen 20 caracteres; reescribir el `.env` exige confirmación.
- **La oferta actual vende más de lo que mostró el panel pequeño: 14 de 100, no 1 de 20.** El 5 % inicial era ruido de una muestra chica.
- LegalWeb es la suite completa más barata que se encontró.
- El costo de WhatsApp es menor al estimado (unos USD 0,006 por mensaje).

**Correcciones en contra:**
- El error del calendario es **medible y grande**: 11,1 % de los términos.
- Hay un fallo peor que el reportado: un error en el servicio de actuaciones se registra como «sin novedades».
- **LegalWeb va por detrás de la competencia** en SAMAI, entrega del auto, WhatsApp y frecuencia. La primera pasada no lo vio.
- Los términos de uso de la Rama Judicial dicen que no se permiten consultas automatizadas.
- Es posible que producción revise cada 48 horas y no cada 24.

## 4. El código: qué sostiene el negocio y qué no

**Sólido (verificado ejecutando):** pagos con Wompi (firma, monto, sin doble activación por reintento), aislamiento entre firmas y por caso, portal del cliente por token, descarga de documentos, correos con baja en un clic.

**Defectos verificados, por gravedad:**

| # | Defecto | Evidencia | Qué significa para un abogado |
| --- | --- | --- | --- |
| 1 | **Calendario judicial equivocado.** Omite festivos reales y marca hábiles como festivos | `JudicialCalendarService.php:15-16, 47-49`. De 1.840 cálculos de 2026–2027, 205 difieren: **115 vencen más tarde de lo correcto** (hasta 4 días) y 90 antes | La calculadora y los recordatorios pueden decirle que tiene un día más del que tiene |
| 2 | **Un fallo se muestra como «sin novedades».** Si el servicio de actuaciones responde con error o cambia de formato, el caso queda «Sin novedades. 0 actuaciones verificadas» | `TybaService.php:421-426, 476`; reproducido | El abogado ve el caso al día y deja de mirar |
| 3 | **Consulta fallida marcada como sincronizada**, sin aviso y sin reintento hasta el siguiente ciclo | `CaseSyncService.php:72-81, 207-216`; reproducido | Igual que el anterior, por otra vía |
| 4 | **Nada avisa si la tarea nocturna deja de correr** | `routes/console.php:24`; ninguna alerta la vigila | Puede pasar una semana sin vigilancia y nadie se entera |
| 5 | **Posible revisión cada 48 horas**: el filtro pide «más de 24 horas» y la tarea corre a hora fija | `SyncTybaActuaciones.php:26`; simulado, **falta confirmarlo en producción** | La promesa de aviso diario podría ser cada dos días |
| 6 | **SAMAI no detecta actuaciones**, solo cambios de datos generales | `CaseSyncService.php:198-201`; reproducido | En lo contencioso administrativo no hay vigilancia real |
| 7 | **Los avisos de etapa a 3, 1 y 0 días no se envían** | `CheckDeadlines.php:34-38`; reproducido con 15 horarios | Una función entera no funciona |
| 8 | **Dos actuaciones con el mismo título el mismo día se guardan como una** | `CaseSyncService.php:107-137`; la fuente entregó 4, se guardaron 2 | Puede perderse una actuación |
| 9 | **La vigilancia «por cédula o NIT» busca por nombre** y acepta homónimos; 25 clientes por noche | `TybaService.php:31-67`; 4 de 4 homónimos aceptados | Falsas alarmas y cobertura parcial |
| 10 | **La IA recibe el radicado, el despacho, el juez y nombres de terceros**, y si falla el proveedor preferido usa uno que puede entrenar con los datos | `AIService.php:213-260`; cuerpo capturado | Con el radicado se identifica a las partes: el secreto profesional no está garantizado |
| 11 | **`setup.php` con la clave en la URL y sin límite de intentos**; por un enlace permite nombrar superadmin y descartar cambios del servidor | `public/setup.php:28-32`; 33 acciones listadas | Si la clave se filtra, se pierde el control del sitio |
| 12 | **El enlace del portal del cliente se envía a Google Analytics** | `layouts/portal.blade.php:10-11` | Un tercero recibe la llave de acceso al caso |
| 13 | **Un código de descuento de un solo uso se puede canjear dos veces** | `WompiController.php:91-99`; reproducido | Pérdida menor de ingresos |
| 14 | **Con la configuración documentada, la corrida nocturna cubre entre 6 y 74 procesos**; con cola y tarea cada minuto, entre 900 y 2.100 | Medido: 23 ms por caso sin red; el límite es la red | El techo depende de cómo esté configurado producción, que no se pudo ver |

Además, **cinco promesas de la landing no están respaldadas**: «sin errores de conteo», «secreto profesional garantizado», «te recuerda dos días antes» (es uno), «documentos centralizados» (no se pueden cargar archivos al caso) y «21 flujos procesales» (el cargador se niega a correr en producción).

Dos datos externos por confirmar que afectan el calendario: la Semana Santa completa sería inhábil para los despachos (el código solo descuenta jueves y viernes) y se reporta un festivo nuevo desde 2026 (Ley 2578 de 2026, 9 de julio, trasladable). Ninguno se pudo leer en la fuente.

## 5. Los compradores: 300 simulados

Mismas 100 tarjetas para cada oferta. Son simulados y los modelos tienden a decir que sí: léalo como un techo.

| | Oferta publicada hoy | «Vigilancia en Paralelo» | Igual, Individual a $69.900 y 80 procesos |
| --- | :-: | :-: | :-: |
| **Compran** | **14 %** (entre 9 y 22) | **30 %** (entre 22 y 40) | **30 %** |
| Litigante independiente | 10 % | 30 % | 32 % |
| Socio de firma pequeña | 30 % | 50 % | 47 % |
| Menos de 15 procesos | 0 % | 0 % | 0 % |
| Revisa a mano | 8 % | 48 % | 52 % |
| Desordenado | 47 % | 67 % | 60 % |
| Adopta tecnología pronto | 50 % | 80 % | 80 % |
| Tiene dependiente · ya paga vigilancia · desconfía · busca lo gratis | 0 % | 0 % | 0 % |
| Pasan por hábito / confianza / precio | 36 / 29 / 20 | 45 / 15 / 8 | 47 / 14 / 7 |

Lo que dice con claridad:

1. **La oferta nueva duplica la compra** y la diferencia no es azar: 16 personas pasaron de no a sí y ninguna al revés.
2. **Gana donde había desconfianza y precio; no mueve el hábito.** Quien ya tiene dependiente o paga otro servicio no cambia con ninguna oferta. Son la mitad del mercado y hoy no son su cliente.
3. **El cliente es el que revisa a mano**: ahí la compra pasa de 8 % a 48 %.
4. **Subir el Individual a $69.900 con 80 procesos no pierde compradores** y sube el ingreso medio (de unos $92.000 a $93.300). Es la mejor variante probada.
5. **Todos los que compran lo condicionan** a que la prueba de 30 días no omita una sola actuación. La venta depende de la confiabilidad real.
6. **La garantía no convence sola:** 63 de 100 la mencionan en su objeción, casi siempre para decir que devolver el mes no compensa un término vencido (conteo por palabras clave).
7. **Precio:** rango aceptable de unos $30.000 a $90.000–$120.000; punto de indiferencia entre $60.000 y $70.000. Los $120.000 actuales están en el borde superior.

## 6. La competencia

Dos lecturas independientes coinciden: para unos 50 procesos, mínimo $49.900, **mediana $70.200**, máximo $240.000 (11 datos). Se identificaron al menos 17 proveedores.

| Dónde va LegalWeb por detrás | Dónde va por delante |
| --- | --- |
| Actuaciones de SAMAI: al menos seis dicen detectarlas | Precio de suite: $120.000 frente a unos $229.000 más $250.000 iniciales, o $240.000 más $1.500.000 de activación |
| Entrega del auto en PDF: cuatro lo hacen | Procesos ilimitados a precio fijo: casi todos cobran por proceso |
| WhatsApp: cinco avisan por ahí | Portal del cliente y facturación por caso en un plan económico |
| Frecuencia: uno revisa 7 veces al día | |
| Plan gratuito: uno regala 10 procesos | |

**El hueco que sí existe:** nadie garantiza no omitir una actuación ni envía un informe de lo que consultó. Solo dos se acercan (uno publica el estado de los portales, otro anuncia 99,8 % sin compensación). Es el diferencial posible, y es justo lo que el código hoy no puede sostener.

Casi no hay reseñas públicas de ningún proveedor: la confianza en este mercado se gana de colega a colega.

## 7. Mercado y reglas

- 425.016 abogados con tarjeta vigente en 2025 (prensa citando a la Corporación Excelencia en la Justicia). **No hay dato de cuántos litigan.** Con supuestos explícitos, el mercado que pagaría está entre 3.800 y 26.000 abogados.
- **Riesgo legal de la fuente:** los términos de uso de la Rama Judicial indican que no se pueden ejecutar consultas automatizadas (extracto; el texto completo no se pudo leer). Lo comparten todos los competidores, pero debe revisarlo un abogado.
- No se encontró ningún servicio oficial de alertas. La Rama está migrando despachos a un sistema nuevo (SIUGJ, la mitad ya lo usa); su efecto sobre la consulta pública no se pudo confirmar.
- Referencias de la industria (publicadas por proveedores): de prueba gratis sin tarjeta a pago, 4–6 % es bueno y 10–15 % excelente; de plan gratuito a pago, 3–5 %; baja mensual en pymes, 3–7 %.

## 8. Los números

Casi todos los costos son estimaciones; reemplácelos por los reales en `numbers.json`.

| | Con sueldo parcial del fundador | Solo costos de caja |
| --- | ---: | ---: |
| Ingreso medio por suscripción | $91.950 | $91.950 |
| Contribución por suscripción | $84.880 (92 %) | $84.880 |
| Costos fijos al mes | $4.342.000 | $842.000 |
| **Punto de equilibrio** | **52 suscriptores** | **10 suscriptores** |
| Resultado del año 1 | −$21.632.080 | +$20.367.920 |
| Caja necesaria | $27.209.280 | $5.420.080 |

Esas cifras suponen llegar a 60 suscriptores en 12 meses. La simulación dice qué hace falta para lograrlo (5.000 corridas; conversión 4–15 %, baja 3–7 %, ingreso medio $59.900–$120.000):

| Registros nuevos al mes | Clientes de pago al mes 12 (mediana) | Probabilidad de cubrir los costos de caja al mes 12 | Probabilidad de cubrir el sueldo al mes 12 | Al mes 24 |
| ---: | ---: | :-: | :-: | :-: |
| 10 | 8 | 27 % | 0 % | 0 % |
| 20 | 16 | 77 % | 0 % | 3 % |
| 40 | 32 | 98 % | 9 % | 45 % |
| 80 | 65 | 100 % | 62 % | 89 % |
| 150 | 120 | 100 % | 94 % | 100 % |

**La lectura:** con 20 registros al mes el proyecto se paga a sí mismo pero no paga a quien lo opera. Para vivir de él hacen falta unos 80 registros al mes de forma sostenida. Cuántos tiene hoy es el dato que más falta.

Otros dos puntos: no hay cobro recurrente (cada periodo es un pago manual, lo que sube la baja) y el servicio en la nube podría estar excluido de IVA, cosa que define un contador.

## 9. Qué hacer, en orden

**Esta semana (antes del 5 de diciembre)**
1. Retirar de la landing las cinco promesas no respaldadas y la mención de SAMAI como vigilancia de actuaciones.
2. Corregir el calendario judicial y cubrirlo con pruebas (un día de trabajo). Avisar a los usuarios actuales que revisen los términos calculados que crucen festivos de mayo a julio.
3. Sacar Google Analytics del portal del cliente y cerrar `setup.php`.

**Antes del 12 de diciembre**
4. Decidir si se mantiene la fecha en que entran los límites del plan Gratuito (`FirmPlan::FREE_LIMITS_FROM`). Ese día los usuarios gratuitos pasan a revisión semanal: hacerlo mientras los fallos son invisibles es el peor orden. Si se mantiene, es también la primera medición real de prueba a pago.
5. Confirmar en producción si la revisión es cada 24 o cada 48 horas y cómo está configurada la cola.

**Antes del 31 de diciembre**
6. Que ningún fallo se muestre como «sin novedades»: estado real por caso, reintento, aviso al abogado el mismo día y alerta si la tarea nocturna no corre.
7. Corregir duplicados del mismo día, avisos de etapa y el doble canje de descuentos.
8. Ocultar el radicado a la IA y no usar proveedores que entrenan con los datos.

**Durante la vacancia judicial (20 de diciembre a 10 de enero)**
9. Construir lo que la oferta promete: informe de verificación de los lunes, WhatsApp, plan Individual, cobro recurrente.

**Desde el 12 de enero de 2027**
10. Prueba real con 10 a 20 litigantes que revisan a mano, de un solo nicho, 30 días en paralelo con su método. Meta de la junta al 28 de febrero: cero omisiones documentadas y al menos 5 pagando.
11. Al 31 de marzo: un canal repetible con 40 o más registros al mes y costo por registro medido. Si esto y lo anterior no se cumplen, la junta vota no financiar.

**Lo que no se hace:** funciones nuevas fuera de la vigilancia, pauta antes de tener confiabilidad, ni vender lo contencioso administrativo mientras SAMAI no detecte actuaciones.

## 10. Lo que este análisis no sabe

- Registros mensuales, clientes de pago, bajas y costos reales. Con ellos cambia el veredicto.
- La configuración real del servidor (cola, frecuencia, registro de errores).
- Cuántos abogados litigan en Colombia.
- El texto completo de los términos de uso de la Rama Judicial.
- Los precios de la competencia vistos directamente en sus páginas.
- Si la oferta nueva convence a abogados reales: el panel es una simulación.

No es asesoría financiera, legal ni tributaria.

## 11. Dónde está cada cosa

| Archivo | Contenido |
| --- | --- |
| `business-plan.md`, `one-pager.md`, `summary.md` | El plan compilado y sus resúmenes |
| `verify/` | Las seis verificaciones: sincronización, escala, términos e IA, seguridad y landing, competidores, mercado y costos |
| `code-audit.md` | La auditoría original (corregida por `verify/`) |
| `panel/`, `sim100/v1`, `sim100/v3` | Los tres paneles de 100: tarjetas, respuestas y resultados |
| `panel-quick-v1/`, `panel-quick-v2/` | Los paneles iniciales de 20 |
| `sim/funnel_mc.py` | La simulación del embudo, reproducible |
| `board.md`, `board2/` | Las dos sesiones de la junta |
| `numbers.json`, `numbers-caja.json`, `cfo.md`, `cfo-sources.md` | Los números y de dónde sale cada uno |
| `competitors.md`, `pricing.md`, `offer.md`, `marketing.md`, `brand.md`, `ops.md`, `launch.md` | El resto del pack |
