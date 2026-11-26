# LegalWeb · Business plan

**Verdict: Not yet**

- ✓ Each suscripcion-mes earns $84880.00 before fixed costs (92% contribution).
- ✗ Year 1 operating LOSS: $21,632,080.
- ✓ 30 of 100 simulated buyers buy (30%, the bar is 25%).

| key number | |
| --- | ---: |
| Price | $91950.00 a suscripcion-mes |
| Profit margin at plan | 14% per suscripcion-mes |
| Break-even | 52 suscripcion-mess a day |
| Year 1 operating profit | $-21,632,080 |
| Startup spend | $4,500,000 |
| Cash needed before it pays for itself | $27,209,280 |
| Startup money earned back | not in year 1 |
| Buyer panel | 30 buy · 70 pass |

## The idea

- Qué es: un SaaS web (legalweb.com.co) que vigila automáticamente los procesos judiciales de un abogado colombiano en la Rama Judicial y en SAMAI y le avisa por correo cada vez que hay una actuación nueva; alrededor de eso ofrece expediente digital, términos en días hábiles, portal del cliente e IA jurídica.
- Para quién: abogados litigantes independientes y firmas pequeñas en Colombia (1 a 10 abogados) que llevan entre 10 y 200 procesos activos y hoy los revisan uno por uno en la consulta de procesos, o pagan a un dependiente judicial o a un servicio de vigilancia.
- Qué vende y a qué precio: suscripción mensual. Prueba de 3 meses con todo incluido; luego plan Gratuito (hasta 5 casos activos, 1 usuario, 10 consultas de IA al mes, sincronización semanal, sin vigilancia por cédula/NIT); Profesional $120.000 COP/mes o $600.000 por 6 meses (casos ilimitados, hasta 3 usuarios); Firma $250.000 COP/mes o $1.250.000 por 6 meses (hasta 10 usuarios). Pagos por Wompi.
- Dónde y cómo: 100% en línea, registro con Google en 60 segundos, sin tarjeta. Adquisición declarada: landing propia, correos de ciclo de vida y campañas de correo masivo. Entrega: panel web (adaptado a celular) y correos diarios.
- Presupuesto y restricciones: no declarados por el fundador. Lo que se observa en el repositorio: producto ya construido y en producción, desarrollado por un fundador con asistencia de IA, desplegado en hosting compartido (cPanel, sin SSH, tareas programadas por HTTP). Número de firmas registradas, clientes de pago, costos mensuales y presupuesto de mercadeo: desconocidos (preguntas abiertas).

## Summary

**Veredicto: todavía no («Not yet»).** Calculado por `compile.py`, no escrito a mano. El detalle completo está en `RESULTADO.md`.

**Qué tiene que cambiar, y qué lo cambia:**
1. **El año 1 pierde $21.632.080** si el negocio le paga al fundador medio tiempo: el equilibrio son 52 suscriptores de pago. Sin sueldo el equilibrio son 10 y el año deja $20.367.920. Lo cambia el volumen: la simulación muestra que con 20 registros al mes se cubre la caja pero nunca el sueldo, y que para vivir del proyecto hacen falta unos 80 registros al mes (`cfo.md`).
2. **La promesa central no está respaldada por el código**, comprobado ejecutándolo: el calendario judicial da mal el 11,1 % de los términos (6,2 % vence más tarde de lo correcto), un fallo del servicio de actuaciones se registra como «sin novedades», nada avisa si la tarea nocturna se cae y SAMAI no detecta actuaciones (`verify/`). Lo cambian unas tres semanas de trabajo, en el orden de `ops.md`.
3. **La oferta publicada vende poco**: 14 de 100 en el panel simulado. La oferta propuesta sube a 30 de 100, pero todavía no existe en el producto.

**Qué es y para quién.** LegalWeb consulta cada madrugada los procesos de un abogado colombiano en la Rama Judicial y le avisa cuando hay una actuación nueva. Es para el litigante independiente o la firma pequeña que lleva entre 20 y 150 procesos y hoy los revisa a mano.

**Los tres números del veredicto.** Contribución de $84.880 por suscripción al mes (92 %); equilibrio en 52 suscriptores con sueldo o 10 sin él; año 1 con pérdida de $21.632.080 en el escenario base.

**En qué coinciden la junta y el panel.** En que el problema es la confianza y el hábito, no el precio: con la oferta actual pasan 36 por hábito, 29 por confianza y 20 por precio. La junta, con la evidencia verificada, votó 3 «financiar si» con promedio 3,3 de 10 y puso fechas. **En qué difieren:** la junta quiere una garantía más fuerte; el panel dice que ninguna devolución compensa un término vencido y que solo paga si la prueba no omite nada.

**El mayor riesgo y qué se hará.** Que un término mal calculado o una actuación no avisada le cueste un proceso a un cliente que ya confía en el producto. Se corrige el calendario, se hace visible cada fallo y se retiran de la landing las promesas que el código no cumple.

**Qué se necesita para empezar.** Unas tres semanas de desarrollo, alrededor de $5.400.000 de caja si el fundador no cobra ($27.200.000 si cobra), y una prueba real: de 10 a 20 litigantes que revisan a mano, 30 días en paralelo con su método, desde el 12 de enero de 2027.

**Lo único que hay que hacer esta semana.** Corregir el calendario judicial y quitar de la landing «sin errores de conteo», «secreto profesional garantizado» y la vigilancia de actuaciones en SAMAI.

El panel son compradores simulados y los números son proyecciones sobre estimaciones; los confirman clientes reales y cotizaciones reales. No es asesoría financiera, legal ni tributaria.

## What the board said

**Voto: 3 «FUND IF», 0 «FUND», 0 «PASS». Puntaje promedio: 4,7 / 10** (Ofertas 5, Monopolio 4, Producto 5).

Los tres miembros aplican marcos publicados (resumidos en `lenses.md` de la skill); no hablan en nombre de sus autores.

### Riesgos que señaló más de un miembro (van primero)

1. **La promesa central descansa en una fuente ajena y frágil** (los tres). Las APIs de la Rama Judicial y SAMAI no son contractuales, ya cambiaron una vez, traen anotaciones vacías y no entregan los autos. Una actuación no detectada es un término vencido. Sin confiabilidad medida ni garantía, el abogado paga y sigue revisando a mano, o no paga.
2. **No hay distribución demostrada** (Ofertas y Monopolio). Hay landing, registro y plantillas de correo, pero ningún canal con costo de adquisición conocido.
3. **No se conocen los números propios** (los tres): registros, activación, conversión de prueba a pago, churn.
4. **Falta de foco** (Producto; Ofertas lo roza): siete o más módulos sostenidos por una persona; se lee como suite genérica y empuja a competir por precio.
5. **La oferta regala demasiado** (Ofertas; Producto lo ve desde la degradación): 3 meses completos sin tarjeta más un plan gratuito para siempre, sin urgencia; y al terminar la prueba el usuario cae a 5 casos con sincronización semanal, el momento de mayor fricción.
6. **Nada es propietario** (Monopolio): datos públicos, IA en niveles gratuitos; lo que fue barato de construir es barato de copiar.

### Condiciones, fusionadas (lista de verificación para el resto del pack)

- [ ] **Confiabilidad medida y publicada:** tasa de detección y retraso contra revisión manual en una muestra de procesos. → *auditoría de código / ops*
- [ ] **Aviso explícito cuando la vigilancia falla:** que el silencio nunca parezca «sin novedades». → *auditoría de código / ops*
- [ ] **Sacar la tarea nocturna del hosting compartido** a infraestructura con monitoreo y reintentos. → *ops / CFO (costo)*
- [ ] **Garantía honesta y asequible** (por ejemplo, devolución del periodo si se omite una actuación publicada en la fuente). → *offer / CFO*
- [ ] **Datos de conversión:** una cohorte completa prueba → pago, con churn. → *launch (prueba real)*
- [ ] **Un canal repetible:** primeros 100 clientes de pago con costo de adquisición medido e inferior a $600.000 (6 meses de Profesional). → *marketing / launch*
- [ ] **Reestructurar la oferta:** una promesa central con nombre, prueba más corta o con urgencia real, y un plan gratuito que no sustituya al pago. → *consumer / pricing / offer*
- [ ] **Elegir un subsegmento** y dominarlo (una ciudad, una especialidad, lo contencioso administrativo). → *competitors / marketing*
- [ ] **Congelar funciones nuevas** y ocultar los módulos sin uso demostrado. → *ops*
- [ ] **IA de pago o IA fuera:** no entregar borradores jurídicos sobre modelos gratuitos inestables. → *CFO (costo) / ops*
- [ ] **Un activo de datos propio** que mejore con cada cliente (tiempos por despacho agregados entre firmas, historial de actuaciones). → *plan*
- [ ] **Plan de contingencia probado** para la caída o el cambio de la fuente. → *ops*

### Dónde no están de acuerdo

- **Precio.** Ofertas cree que $120.000 con casos ilimitados deja margen para subir; Monopolio ve una mejora incremental frente al dependiente judicial, lo que limita cuánto se puede cobrar. El panel y pricing lo dirimen.
- **La suite.** Ofertas y Monopolio ven valor en los módulos contiguos (pila de la oferta, costo de cambio); Producto pide recortarlos porque compiten con lo único que debe ser infalible.
- **La IA.** Ofertas la cuenta como parte de la pila; Producto la quiere pagada o fuera.

### La versión más fuerte de este negocio que ve la junta

No es «la suite legal para abogados colombianos», que es lo que hoy presenta la landing. Es **un servicio de vigilancia judicial con confiabilidad demostrada y garantizada**: un nombre, una promesa («si la fuente lo publicó y no le avisamos, le devolvemos el mes»), un tablero público de detección y latencia, y aviso inmediato cuando una sincronización no se completó. Los vencimientos automáticos y el portal del cliente son la pila que lo vuelve difícil de dejar; el resto se oculta hasta que haya uso que lo justifique. Empieza en un subsegmento que se pueda dominar y acumula un dato que nadie más tiene: cuánto tarda de verdad cada despacho. Esto difiere de lo que se presentó, y la junta lo dice sin rodeos: el producto ya hace más de lo que necesita, y demuestra menos de lo que promete.

### Segunda sesión, con la evidencia verificada

**Voto: 3 «FUND IF». Puntaje promedio: 3,3 / 10** (Ofertas 4, Monopolio 3, Producto 3; antes 5, 4 y 5). Memos en `board2/`.

La nota bajó porque la evidencia confirmó lo que antes era sospecha: el producto registra fallos como «sin novedades», calcula mal el 11,1 % de los términos, no detecta actuaciones en SAMAI y va por detrás de la competencia en WhatsApp, entrega del auto y frecuencia. Lo que subió: la oferta de verificación garantizada es un diferencial que nadie más tiene y duplica la compra en el panel.

**Condiciones, ahora con fecha:**
- [ ] Antes del 5 de diciembre de 2026: landing sin promesas no respaldadas; `setup.php` fuera de producción.
- [ ] Antes del 31 de diciembre de 2026: defectos del núcleo corregidos con pruebas; 0 % de discrepancia en los 1.840 cálculos de términos; aviso el mismo día ante cualquier fallo y alerta si la tarea nocturna no corre.
- [ ] Antes del 28 de febrero de 2027: 30 días en paralelo con 10 a 20 abogados reales de un solo nicho, cero omisiones documentadas y al menos 5 pagando.
- [ ] Antes del 31 de marzo de 2027: un canal repetible con 40 o más registros al mes, costo por registro medido y cobro recurrente.
- [ ] Ninguna función nueva fuera de la vigilancia hasta cumplir lo anterior.

Si las dos últimas no se cumplen, el lente de Monopolio vota no financiar.

**Dónde siguen sin estar de acuerdo:** Ofertas quiere una garantía más fuerte que devolver el mes; Producto pide congelar IA y facturación; Monopolio duda de que exista algo defendible si un competidor copia el informe de verificación.

## The competition

Fecha de consulta: 2026-11-26.

**Advertencia de método (léela primero).** En esta sesión no se pudo abrir directamente ningún sitio de proveedor: la red bloqueó los dominios (ver sección 8). Todo lo que sigue se leyó a través del buscador web, que devuelve el contenido de cada página con su URL. Consecuencias:

- Los precios y titulares son los que el buscador leyó en la página enlazada; no los vi con mis propios ojos en el sitio. Antes de usarlos en material comercial, abre cada enlace y confírmalos.
- El "titular propio" es el título de la página que devolvió el buscador, no necesariamente el H1 visible.
- No pude leer las reseñas de Google Play ni de Google Maps de nadie. La sección 4 es, por eso, casi vacía.
- Donde dice "cálculo propio" multipliqué la tarifa por proceso por 50; el proveedor no publica esa cifra así.

LegalWeb, para comparar: Gratuito hasta 5 casos; Profesional $120.000 COP/mes (casos ilimitados, 3 usuarios); Firma $250.000 COP/mes (10 usuarios); 3 meses gratis.

### 1. Tabla (ordenada de más a menos directo)

| # | Nombre | Tipo | Precio del ítem comparable | Qué vende | Titular propio | Calificación (n) | Fuente |
|---|---|---|---|---|---|---|---|
| 1 | Monolegal | Direct | ≈$70.200 COP/mes para 50 procesos (cálculo propio: $1.404 por proceso/mes con consulta diaria en todas las fuentes; otra página de ayuda dice $1.335; prueba gratis 15 días; pago por uso) | Consulta automática diaria de procesos en Rama Judicial, CPNU, SAMAI, SIUGJ y publicaciones procesales, con notificación de actuaciones; app de expediente digital | "Consulta de procesos automática en la Rama Judicial de Colombia" | App Store: sin calificaciones suficientes; Google Play: no verificado (0 (App Store)) | [enlace](https://monolegal.co/precios-y-descuentos-en-la-consulta-de-tus-procesos-judiciales) |
| 2 | Legal Procesos | Direct | ≈$50.000 COP/mes para 50 procesos (cálculo propio: $1.000 por proceso/mes; el sitio se contradice sobre si SAMAI/TYBA/SIUGJ cuestan $200 extra por fuente; prueba 15 o 30 días según la página) | Monitoreo de procesos por proceso en CPNU, Rama Judicial, SAMAI, TYBA y SIUGJ; IA; cálculo de términos procesales | "Monitoreo de procesos judiciales con IA para abogados" | no verificado (no verificado) | [enlace](https://legalprocesos.com/pricing) |
| 3 | NeoJurídico | Direct | $49.900 COP/mes plan Litigante: 50 procesos, alertas por correo y WhatsApp, IA y reportes (Capterra lista 30 procesos para el mismo plan); gratis 3 procesos; Bufete $119.900 = 160 procesos y 5 usuarios | Vigilancia de procesos de la Rama Judicial con 7 revisiones al día, alertas por correo y WhatsApp, informes con IA | "Vigila Procesos Judiciales y Recibe Alertas" | no verificado (no verificado) | [enlace](https://neojuridico.com/blog/cuanto-cuesta-la-vigilancia-judicial-en-colombia) |
| 4 | LitigIA | Direct | $69.000 COP/mes plan Básico: 50 procesos activos y 5 usuarios; gratis 1 abogado con 5 procesos; $1.000 por proceso adicional | Vigilancia de procesos vía CPNU, alertas de términos de tutela, verificación de citas jurisprudenciales con IA | "Vigila tus procesos judiciales en Colombia" | no verificado (no verificado) | [enlace](https://www.litigia.co/) |
| 5 | Estados Judiciales | Direct | $59.000 COP/mes plan Profesional: hasta 50 procesos (el propio sitio lo llama precio tentativo); gratis 3 procesos | Avisos por WhatsApp de actuaciones y términos (CPNU, 1 revisión diaria), cálculo de términos con festivos y vacancia, redacción y asistente de IA | "Seguimiento inteligente de procesos judiciales" | no verificado (no verificado) | [enlace](https://www.estadosjudiciales.ai/) |
| 6 | JuridAI | Direct | $79.900 COP/mes + IVA plan Plus: 50.000 créditos = vigilancia hasta 100 procesos, incluye IA; gratis Lite hasta 10 procesos | Vigilancia judicial + inteligencia artificial jurídica, cobrada por créditos (500 créditos por proceso al mes) | "Plataforma de IA para Procesos Judiciales Colombianos" | no verificado (no verificado) | [enlace](https://www.juridai.co/pricing) |
| 7 | Litigiovirtual | Direct | Desde $93.250 COP/mes: estados electrónicos, providencias, calendario, usuarios ilimitados, vigilancia en Rama Judicial, sin permanencia (número de procesos incluidos: no verificado); prueba 20 días | Revisión de estados electrónicos y providencias de los despachos con alertas, combinada con consulta de la Rama Judicial | "Revisión de procesos judiciales en Colombia" | 3,8 (App Store Colombia); 94% recomienda (Facebook) (10 calificaciones (App Store); 11 reseñas (Facebook)) | [enlace](https://litigiovirtual.com/) |
| 8 | Vigilancia Judicial (Landacom) | Direct | $130.000 COP/mes Dependiente virtual Pack 100: hasta 100 radicados y 1 correo, IVA incluido (la vigilancia profesional con personas cuesta $300.000/mes por hasta 50 procesos y 2 correos) | Dependiente virtual automatizado por packs de radicados y servicio de vigilancia judicial profesional | "Vigilancia judicial profesional" | no verificado (no verificado) | [enlace](https://vigilanciajudicial.com/dependiente-virtual/) |
| 9 | Redjudicial | Direct | no publicado / no verificado | Vigilancia personalizada de procesos con notificación de estados, traslados, edictos y fijaciones en lista; dependencia judicial presencial; software de gestión judicial | "pionera a nivel nacional en el uso de la tecnología de Internet para la Vigilancia y Control de Procesos Judiciales (ficha en Connect Americas, no su sitio)" | no verificado (no verificado) | [enlace](https://www.redjudicial.com/nuevo/) |
| 10 | Redelex | Direct | $240.000 COP/mes plan Básico + $1.500.000 de activación única, pago anual anticipado: 3 usuarios, hasta 2.000 procesos, 50 GB; el dependiente virtual (alertas de movimientos) se vende aparte en packs de vínculos | Software de gestión de procesos judiciales para oficinas de abogados; portal privado de clientes solo en el plan Empresarial ($800.000/mes) | "software para la gestión de procesos judiciales amable y fácil de implementar" | Sin reseñas (ComparaSoftware, TrustRadius) (0) | [enlace](https://redelex.com/precios/) |
| 11 | Lemontech (CaseTracking / LemonSuite) | Indirect | no publicado / no verificado (se cotiza; su página dirige CaseTracking a firmas con 50 o más juicios) | CaseTracking: gestión automatizada de juicios; LemonSuite: tiempos, cobranza y facturación para estudios | "Software legal con IA para estudios y empresas" | no verificado (no verificado) | [enlace](https://www.lemontech.com/productos/casetracking) |
| 12 | Clio | Indirect | Desde USD 49 por usuario/mes (EasyStart); sin precio en COP; no vigila Rama Judicial ni SAMAI (integración colombiana: no verificada) | Gestión de despachos: casos, tiempos, facturación, portal de clientes | "no verificado" | no verificado (no verificado) | [enlace](https://www.clio.com/pricing/?period=monthly) |
| 13 | Consulta de Procesos Nacional Unificada (Rama Judicial) | Substitute | $0 (revisión manual, proceso por proceso; sin alertas) | Consulta pública por radicado, nombre o razón social, juez y clase de proceso | "Puede realizar la consulta del estado actual de sus procesos" | no verificado (no verificado) | [enlace](https://consultaprocesos.ramajudicial.gov.co) |
| 14 | SAMAI (Consejo de Estado) | Substitute | $0 (revisión manual; jurisdicción contencioso administrativa) | Consulta de procesos administrativos por radicado, clase de proceso o parte | "este servicio se puede realizar 24 horas, 7 días a la semana" | no verificado (no verificado) | [enlace](https://www.ramajudicial.gov.co/web/consejo-superior-de-la-judicatura/ventanilla-de-servicios/-/asset_publisher/TIpGpe2ENeIh/content/id/194520921) |
| 15 | Dependiente judicial contratado | Substitute | $1.500.000 a $2.500.000 COP/mes de salario según ofertas de empleo en Bogotá (oct-nov 2026), sin prestaciones | Una persona revisa los despachos y portales y reporta las actuaciones | "no aplica" | no aplica (no aplica) | [enlace](https://www.elempleo.com/co/ofertas-empleo/trabajo-auxiliar-judicial-dependiente-judicial) |
| 16 | Excel + revisión manual / recordatorios | Substitute | $0 en dinero; costo en tiempo no verificado | Lista de radicados en una hoja de cálculo y revisión manual periódica en los portales | "no aplica" | no aplica (no aplica) | no aplica |

**Otros que aparecieron y no entraron a la tabla** (vistos solo de pasada, precios según el buscador):

- AltaCore Jurídico: $47.500/mes por 1–20 procesos, IVA incluido, más $250.000 de configuración única; "gestión completa" desde $149.000/mes con facturación electrónica, calendario y honorarios. Vigila Rama Judicial, SIUGJ, SAMAI, SPOA. https://www.altacore.co/juridico — es el más parecido a LegalWeb en amplitud; merece una revisión manual.
- misprocesos: $15.000 (1 proceso) a $95.000/mes (hasta 40). https://misprocesos.co/
- JustiBot: $19.900 a $99.900/mes según nivel de juzgado; prueba de un mes con 5 radicados. https://www.justibot.com.co/
- Lupa Jurídica: vende acceso a cartelera virtual por packs ($35.900 a $2.200.000/mes), no un plan de vigilancia por proceso. https://lupajuridica.co/product-category/cartelera-virtual/
- Jurídica Pro Max y RuleX: existen como marcas; el único precio visto ($45.000 y $40.000/mes) lo afirma LitigIA en su propia página, así que queda **no verificado**. https://www.litigia.co/

**Candidatos de la lista inicial que se descartan o no se encontraron:**

- Juzto: existe, pero vende documentos, trámites y asesoría legal al público, no vigilancia para abogados. https://www.asuntoslegales.com.co/actualidad/plataforma-juzto-una-idea-legal-que-ofrece-soluciones-sustentadas-en-datos-estandar-3119033
- Legis: no encontré ningún producto de vigilancia de procesos. No verificado.
- ICARUS: existe ICARUS.COM.CO S.A.S., que licitó en un proceso de "Vigilancia Judicial" en 2021 (aparece en una sentencia de tutela); producto y precio no verificados. https://www.ramajudicial.gov.co/documents/7812070/79791032/22Sentencia.pdf/2e5d7f9c-cf50-4995-a1d5-928e1f0d1be4
- "Dependiente Virtual": no es una empresa; es el nombre del módulo de Redelex y del producto de vigilanciajudicial.com (ambos en la tabla).
- Lawyers Enterprise / Ticket Legal, Abogapp, "Mi Rama", ProcesosJudiciales, LitiSoft (como producto distinto de Litigiovirtual), Vijud: no encontrados. No verificados.

### 2. Rango de precios (ítem comparable: abogado independiente con ~50 procesos, por mes)

Nueve competidores publican un precio utilizable en COP:

| Proveedor | COP/mes | Nota |
|---|---|---|
| NeoJurídico | 49.900 | 50 procesos |
| Legal Procesos | 50.000 | cálculo propio, 50 × $1.000 |
| Estados Judiciales | 59.000 | precio "tentativo" |
| LitigIA | 69.000 | 50 procesos, 5 usuarios |
| Monolegal | 70.200 | cálculo propio, 50 × $1.404 |
| JuridAI | 79.900 | + IVA; cubre hasta 100 procesos |
| Litigiovirtual | 93.250 | "desde"; procesos incluidos no verificados |
| Vigilancia Judicial (Landacom) | 130.000 | pack de 100 radicados |
| Redelex | 240.000 | + $1.500.000 de activación |

- **Más bajo:** $49.900 (NeoJurídico).
- **Mediana:** $70.200 (Monolegal).
- **Más alto:** $240.000 (Redelex), sin contar la activación.
- **Sin precio en COP:** 3 de los 12 competidores directos e indirectos. Redjudicial y Lemontech no publican precio; Clio publica solo en dólares (desde USD 49 por usuario/mes).
- No son ítems idénticos: unos venden solo alertas, otros incluyen IA o usuarios, y Redelex es un gestor completo. La mediana describe "vigilancia de 50 procesos", no una suite.

**Lo que esto le dice a LegalWeb:** el plan Profesional ($120.000) cuesta 1,7 veces la mediana y más del doble que el más barato. Para un abogado con 50 procesos que solo quiere alertas, LegalWeb es caro. Solo se defiende como suite, frente a Redelex ($240.000 + activación) o AltaCore gestión completa (desde $149.000). El plan Bufete de NeoJurídico ($119.900, 160 procesos, 5 usuarios) queda al mismo precio que LegalWeb Profesional.

En el plan gratuito, LegalWeb (5 casos) empata con LitigIA (5), supera a NeoJurídico y Estados Judiciales (3) y pierde con JuridAI (10).

### 3. Mapa de posicionamiento

Ejes: **precio mensual para ~50 procesos** (vertical) y **amplitud** (horizontal: solo vigilancia y alertas ↔ suite completa con expediente, términos, portal del cliente, facturación).

```
PRECIO
alto   |                                              Redelex ($240.000 + activación)
       |
       |  Landacom ($130.000)                         AltaCore gestión (desde $149.000)*
       |                                              LEGALWEB Profesional ($120.000)
       |  Litigiovirtual (desde $93.250)
       |                    JuridAI ($79.900 + IVA)
       |  Monolegal ($70.200)   LitigIA ($69.000)
       |                    Estados Judiciales ($59.000)
bajo   |  NeoJurídico ($49.900)   Legal Procesos ($50.000)
  $0   |  CPNU / SAMAI manual, Excel
       +--------------------------------------------------------------------
          solo vigilancia      vigilancia + IA / términos      suite completa
```
\* AltaCore no está en la tabla principal; ubicación tentativa.

Sin precio, no ubicables en el eje vertical: Redjudicial (vigilancia con personas, lado izquierdo), Lemontech y Clio (suite, lado derecho; Clio no vigila portales colombianos según lo encontrado).

Lectura:

- La esquina inferior izquierda está llena: al menos seis proveedores venden alertas por $50.000–$80.000 o cerca de $1.000 por proceso.
- El centro (vigilancia + IA + términos) ya lo ocupan recién llegados baratos: JuridAI, LitigIA, Estados Judiciales, Legal Procesos.
- La derecha tiene poca gente a precio de firma pequeña. LegalWeb queda ahí, entre los baratos y Redelex.

### 4. Quejas de los clientes

**No hay casi reseñas públicas legibles.** Es el hallazgo honesto de esta sección: no pude abrir Google Play ni Google Maps, y lo que el buscador devolvió es mínimo.

1. **La app deja de funcionar o no permite registrarse — tema delgado (2 reseñas, un solo proveedor).** Litigiovirtual, App Store Colombia (3,8 con 10 calificaciones). El buscador las devolvió parafraseadas, no textuales: una reseña del 25 de agosto dice que la app funcionó rápido un tiempo, pero ya no funciona y no deja ingresar procesos; otra del 2 de septiembre de 2025 dice que no permite crear la cuenta. https://apps.apple.com/co/app/litigiovirtual/id1587026148
2. **Sin más temas.** Para los demás no encontré ninguna queja de cliente:
   - Monolegal: App Store sin calificaciones suficientes (https://apps.apple.com/us/app/monolegal-consulta-procesos/id1535064774); Google Play muestra "1K+" descargas, reseñas no verificadas (https://play.google.com/store/apps/details?id=co.monolegal.nuevoexpedientedigital).
   - Redelex: sin reseñas en ComparaSoftware (https://www.comparasoftware.com/redelex) ni TrustRadius (https://www.trustradius.com/products/redelex/reviews?qs=pros-and-cons).
   - NeoJurídico, Legal Procesos, LitigIA, JuridAI, Estados Judiciales, Landacom, Redjudicial: sin reseñas públicas encontradas.

**Fricciones que los propios proveedores admiten** (no son quejas de clientes; son límites publicados por ellos):

- Monolegal: factura variable por uso. Su ayuda dice que es "un servicio de pago por uso" y cobra "un 10% adicional por cada fuente". https://helpcenter.monolegal.co/Facturacion/
- Monolegal: no avisa un auto si "no está disponible en el portal de publicaciones procesales". https://helpcenter.monolegal.co/preguntasFrecuentes/
- LitigIA: "Hoy vigilamos con CPNU"; otras fuentes solo caso por caso en el plan Business. https://www.litigia.co/
- Estados Judiciales: revisa la fuente una vez al día y llama "tentativo" a su precio. https://www.estadosjudiciales.ai/
- Legal Procesos: sus páginas se contradicen sobre si SAMAI cuesta $200 extra por proceso y sobre la duración de la prueba (15 o 30 días). https://legalprocesos.com/pricing y https://legalprocesos.com/consulta-samai
- Redelex: $1.500.000 de activación y pago anual anticipado. https://redelex.com/precios/
- Landacom: "No hay descuentos o devoluciones por retiro anticipado". https://vigilanciajudicial.com/terminos-y-condiciones/

### 5. El hueco

**En vigilancia y alertas no hay hueco.** Es un producto básico, con al menos nueve proveedores, precio de referencia cercano a $1.000 por proceso al mes y planes gratuitos. Varios ya suman IA, cálculo de términos y WhatsApp. Si LegalWeb se vende como "te avisamos cuando hay actuación nueva", compite al doble del precio contra gente más barata.

**Sí hay un hueco estrecho en la suite para firma pequeña**, y la evidencia es esta:

- Entre $80.000 y $240.000 casi nadie ofrece gestión completa. Los de $50.000–$80.000 venden alertas (algunos con IA). Redelex, el gestor tradicional, arranca en $240.000 más $1.500.000 de activación y reserva el portal de clientes para el plan de $800.000.
- En lo que pude leer, ningún competidor directo anuncia portal del cliente ni facturación por caso en un plan de menos de $150.000, y ninguno menciona seudonimización de datos en su IA. Ojo: es ausencia en los resultados del buscador, no ausencia confirmada en cada sitio.
- Precio fijo con casos ilimitados: casi todos cobran por proceso, por créditos o por tramos. Un abogado con 200 procesos pagaría unos $280.000 en Monolegal (cálculo propio, sin descuento por volumen, que empieza en 300) y $120.000 en LegalWeb. La ventaja de LegalWeb está en el abogado con muchos procesos, no en el de 50.

**Dos puntos en contra que hay que mirar de frente:**

- LegalWeb avisa solo por correo. NeoJurídico y Estados Judiciales anuncian alertas por WhatsApp.
- AltaCore (gestión completa desde $149.000, con facturación electrónica) puede estar ya en el mismo hueco. No lo investigué a fondo.

No hay evidencia de clientes que pidan esta suite: el hueco se deduce de los precios y funciones publicados, no de quejas.

### 6. La amenaza

**Quien puede copiar más rápido: Monolegal.** Ya tiene la parte difícil (conexión diaria a Rama Judicial, CPNU, SAMAI y SIUGJ), una app de expediente digital y la base instalada: un artículo de Asuntos Legales le atribuye más de 130.000 procesos revisados a diario y más de 1.000 clientes en 66 ciudades (cifra del proveedor citada por la prensa). Le bastaría añadir portal del cliente y facturación sobre esa base y empaquetarlo en un plan fijo. https://www.asuntoslegales.com.co/actualidad/monolegal-usa-inteligencia-artificial-para-facilitar-la-gestion-legal-3077695

**Quien ya choca en precio: NeoJurídico.** Su plan Bufete cuesta $119.900 (160 procesos, 5 usuarios, IA, WhatsApp, 7 revisiones al día), contra los $120.000 de LegalWeb Profesional con 3 usuarios. Si añade expediente y portal, el argumento de suite se acaba. https://neojuridico.com/blog/cuanto-cuesta-la-vigilancia-judicial-en-colombia

**Amenaza de fondo:** los recién llegados con IA (JuridAI, LitigIA, Estados Judiciales, Legal Procesos) lanzan funciones rápido y regalan entre 3 y 10 procesos. La mayoría de las funciones de LegalWeb se pueden replicar en meses; la seudonimización y el portal no son una barrera técnica.

### 7. Tamaño de mercado

- **425.016 abogados vigentes en Colombia en 2025**, según la Corporación Excelencia en la Justicia, citada por la prensa; 410.491 en 2024 y 244.336 en 2015. Fuentes: https://www.elcolombiano.com/negocios/colombia-pais-con-mas-abogados-mundo-AI35687502 y https://www.asuntoslegales.com.co/analisis/daniel-acevedo-4432158/el-pais-con-mas-abogados-debe-reevaluar-como-los-forma-4467412 (consultadas el 2026-11-26).
- Es un dato secundario. La cifra primaria de la Unidad de Registro Nacional de Abogados (Gaceta del Registro, portal SIRNA) quedó **no verificada**.
- Cuántos de esos abogados litigan y cuántos trabajan solos o en firmas de 1 a 10: **no verificado**. No uses 425.016 como mercado objetivo; el universo de litigantes es una fracción desconocida.
- Costo del sustituto humano: ofertas de empleo de dependiente o auxiliar de vigilancia judicial en Bogotá entre $1.500.000 y $2.500.000 al mes (octubre–noviembre de 2026). https://www.elempleo.com/co/ofertas-empleo/trabajo-auxiliar-judicial-dependiente-judicial y https://co.computrabajo.com/trabajo-de-dependiente-judicial-en-bogota-dc

### 8. Fuentes que no pude abrir

La red de la sesión rechazó la apertura directa de estos dominios (habría que permitirlos en la configuración de red para leerlos): www.monolegal.co, www.litigiovirtual.com, www.redjudicial.com, www.neojuridico.com, www.redelex.com, www.lupajuridica.com. Tras ese bloqueo no intenté abrir ninguno más; todo se leyó mediante el buscador.

No leídos o sin resultado:

- Reseñas de Google Play de Monolegal y Litigiovirtual (solo se vio el número de descargas).
- Reseñas de Google Maps, grupos de Facebook y X de cualquier proveedor.
- Texto literal de las reseñas de App Store de Litigiovirtual (solo paráfrasis del buscador).
- Página de precios de Lemontech CaseTracking y precios de Clio para Colombia.
- Sitio de Redjudicial con tarifas.
- Gaceta del Registro Nacional de Abogados (SIRNA).
- Número de procesos del plan de $93.250 de Litigiovirtual.
- Sitios de Legis, ICARUS, Abogapp, "Mi Rama", ProcesosJudiciales, Ticket Legal / Lawyers Enterprise: no encontrados.

## The buyer panel

> **Cómo leer esto.** Tres ofertas, las mismas 100 tarjetas (semilla 7). **Oferta publicada hoy: 14 de 100** (`sim100/v1/results.md`). **Oferta propuesta: 30 de 100** (este archivo). **Igual, con Individual a $69.900 y 80 procesos: 30 de 100** (`sim100/v3/results.md`). La oferta propuesta aún no existe en el producto. Son compradores simulados y los modelos tienden a decir que sí: es un techo. Los paneles iniciales de 20 están en `panel-quick-v1/` y `panel-quick-v2/`.

**30 buy · 70 pass** (30% buy) out of 100 simulated buyers. Seed 7, so the same cards can be dealt again.

These are simulated buyers, not customers. Use this to find objections and weak spots, then confirm the big ones with real people before you spend.

### By segment

| group | buyers | buy rate |
| --- | ---: | ---: |
| Socio de firma pequena, 2 a 10 abogados (80 a 300 procesos) | 30 | 50% |
| Abogado litigante independiente (20 a 80 procesos activos) | 50 | 30% |
| Abogado joven o que litiga poco (menos de 15 procesos) | 20 | 0% |

### By buying behaviour

| group | buyers | buy rate |
| --- | ---: | ---: |
| Adopta tecnologia pronto | 10 | 80% |
| Desordenado | 15 | 67% |
| Revisa a mano | 25 | 48% |
| Tiene dependiente judicial | 15 | 0% |
| Ya paga un servicio de vigilancia | 15 | 0% |
| Desconfia de la tecnologia | 12 | 0% |
| Busca lo gratis | 8 | 0% |

### By income

| group | buyers | buy rate |
| --- | ---: | ---: |
| $115,028,000 and up | 34 | 47% |
| $61,067,000 to $115,028,000 | 33 | 30% |
| under $61,067,000 | 33 | 12% |

### Why they pass

| reason | buyers | in their words |
| --- | ---: | --- |
| habit | 45 | "Ya tengo dependiente que me revisa estados y va a los juzgados, y me funciona; una pagina no me saca copias ni me habla con el secretario. Pagar esto seria pagar dos veces por lo mismo." (P001) · "La consulta de la Rama es gratis y yo ya la hago cada manana con mi Excel; haria la prueba gratis, pero al final seguiria revisando a mano porque la responsabilidad ante el cliente es mia y no dejaria de verificar, asi que estaria pagando por algo que igual repito." (P002) |
| trust | 15 | "Ya casi se me vence un termino y eso me duele, pero no le entrego la vigilancia de mis procesos a una pagina que nunca he oido nombrar y que ningun colega me ha recomendado. Sigo revisando yo mismo con mi libreta y mi dependiente, que es lo que conozco." (P015) · "Hago la prueba gratis porque no me cuesta nada, pero al terminar no pago: con unos 40 procesos igual voy a seguir entrando a la Rama cada manana, porque si a ustedes se les cae la consulta el termino se me vence a mi, no a ustedes. Pagar por algo que no me deja soltar el Excel es pagar doble trabajo." (P016) |
| price | 8 | "Consultar la Rama Judicial es gratis y mi dependiente ya lo hace. Usaria los 30 dias de prueba y despues me quedaria con el plan gratuito de 3 procesos para los casos mas delicados; con mas de 100 procesos me toca el plan de $120.000 y hoy no veo por que pagarlo." (P023) · "Llevo menos de 15 procesos y los reviso en veinte minutos cada manana con mi Excel; con lo que gano, $59.900 al mes son casi $720.000 al ano por algo que hoy hago gratis. Haria la prueba y me quedaria con el plan gratuito de 3 procesos para los mas delicados." (P038) |
| need | 2 | "Llevo menos de 15 procesos y los reviso yo misma cada manana en la Rama; con unos $2 millones al mes de ingreso, $59.900 es plata que no necesito gastar. Haria la prueba y despues me quedaria con el plan gratuito para los 3 procesos mas delicados." (P050) · "Haria la prueba de 30 dias sin dudarlo, pero al terminar me quedo con el plan gratuito para los tres procesos mas delicados. SAMAI ya lo reviso yo varias veces por semana y con unos 30 procesos me alcanza el tiempo." (P095) |

### Why they buy

| reason | buyers | in their words |
| --- | ---: | --- |
| need | 17 | "Ya se me paso un termino por poco y no tengo dependiente ni quien revise por mi; si en los 30 dias de prueba me avisa por WhatsApp lo mismo que yo encuentro a mano, $59.900 es menos de lo que me cuesta un susto. Tomaria el Individual, no el Profesional." (P010) · "Consultar la Rama es gratis, pero yo no lo hago juicioso y ya casi se me pasa un termino; con mas de cien procesos y clientes empresa llamando a preguntar, $120.000 al mes es menos que una hora mia. Si en los 30 dias en paralelo me avisa lo mismo que encuentro a mano, pago el Profesional." (P014) |
| convenience | 13 | "Con mas de cien procesos en la firma y SAMAI revisado a mano varias veces por semana, $120.000 al mes es menos de lo que me cuesta una hora de dependiente; si en los 30 dias en paralelo no se le escapa ninguna actuacion frente a mi revision, pago el Profesional." (P005) · "Reviso radicado por radicado cada manana y eso me come casi una hora; si en los 30 dias en paralelo no se le escapa ni una actuacion frente a mi Excel, pago el Individual de $59.900 porque es menos de lo que vale una hora mia. Si se le pasa una sola, no pago nada." (P011) |

### What would flip a no

- Que en los 30 dias en paralelo el sistema agarre algo que a mi dependiente se le paso, o que mi dependiente se me vaya.
- Que en los 30 dias de prueba el servicio me avise de una actuacion que a mi se me paso en la revision manual, y que el plan de $59.900 cubra todos mis procesos.
- Que en la prueba en paralelo mi servicio actual se le pase una actuacion que LegalWeb si me aviso, y que el plan para mis 15 procesos cueste claramente menos de lo que pago hoy.
- Que en la prueba en paralelo LegalWeb me avise de una actuacion que mi servicio actual no detecto, y que ademas me salga mas barato que lo que pago hoy.
- Que en los 30 dias en paralelo LegalWeb detecte una actuacion que mi revision actual dejo pasar, o un plan de unos $25.000 para hasta 15 procesos.
- Que ustedes me armen la lista de mis procesos a partir de mi nombre o cedula, sin que yo tenga que hacer el Excel, y que durante la prueba me avisen de una actuacion que a mi se me hubiera pasado.
- Que en los 30 dias en paralelo la plataforma detecte algo que a mi dependiente se le paso, o que exista un plan de unos $25.000 para hasta 15 procesos que use como segundo control.
- Que en la prueba en paralelo me pesquen una actuacion que a mi se me paso, o un plan de unos $25.000 para hasta 15 procesos.
- Que un colega litigante de mi confianza me diga que lo usa hace mas de un ano y que nunca le ha fallado un aviso; ahi haria la prueba y probablemente tomaria el plan de $59.900.
- Ver en los 30 dias de paralelo que no se les escapa ni una sola actuacion frente a mi Excel, incluidas las semanas en que la Rama se cae, y que me avisan de verdad cada fallo el mismo dia; con eso y varios meses de historial pagaria el Individual.
- Que durante la prueba el servicio me avise de una actuacion que a mi se me paso en mi revision manual; con un susto real de terminos si pagaria el Individual.
- Que mi servicio actual me deje pasar una actuacion o me suba el precio de forma notable; ahi si haria la prueba en paralelo.

Buyers say they would buy **1.0 times** in the first month on average.

100 buyers gave all four price answers. Run founder-pricing's van_westendorp.py on the answers folder.

## Pricing

> **Actualización con el panel de 100 compradores.** Rango aceptable de la oferta propuesta: de $29.549 a $90.534, con indiferencia en $59.822; con el Individual a $69.900 y tope de 80 procesos: de $29.363 a $99.899. Esa variante compró igual (30 de 100) y sube el ingreso medio de unos $92.000 a $93.300, así que **la recomendación pasa a Individual $69.900 con hasta 80 procesos**. El tope lo mencionaron 29 de 100 con 50 procesos y 17 con 80. El plan Gratuito lo nombraron como refugio 42 de los 86 que pasaron con la oferta actual. Las cifras de abajo son del panel inicial de 20.

### 1. El precio, y por qué

**Mantener Profesional en $120.000 y abrir un plan Individual en $59.900.** No bajar el Profesional.

- **Lo que dijeron los compradores (simulados).** Con la oferta original, el rango aceptable fue de $29.732 a $120.268: los $120.000 quedan en el borde mismo donde «demasiados dicen que es muy caro». Con la oferta nueva el rango fue de $29.437 a $109.936, con punto de indiferencia en $61.040. $59.900 cae en ese punto; $120.000 queda por encima del rango para el comprador medio, y aun así lo eligieron 2 de 6 compradores, los dos con más de 80 procesos.
- **Lo que cobran los competidores** (`competitors.md`; precios leídos a través del buscador, por confirmar): para unos 50 procesos, de $49.900 (NeoJurídico) a $240.000 (Redelex), con mediana de $70.200 (Monolegal). $59.900 queda por debajo de la mediana; $120.000 es 1,7 veces la mediana y solo se defiende con procesos ilimitados y la suite.
- **Lo que necesita el negocio** (herramienta del CFO): a $59.900 de ingreso medio el equilibrio son 84 suscriptores; a $79.900, 61; a $120.000, 39. Por eso el Individual es puerta de entrada y no el plan único.

### 2. La escalera

| Plan | Precio | Para quién | Razón para subir |
| --- | ---: | --- | --- |
| Gratuito | $0 | Probar con 3 procesos | Más de 3 procesos o consulta diaria |
| **Individual** | **$59.900/mes** | 1 abogado, hasta 50 procesos, consulta diaria, alertas por WhatsApp y correo, informe semanal | Más de 50 procesos, portal del cliente, segundo usuario |
| **Profesional** | **$120.000/mes** | Procesos ilimitados, 3 usuarios, portal del cliente, términos, reportes | Más de 3 abogados |
| Firma | $250.000/mes | Hasta 10 usuarios con permisos por caso | — |

El plan Gratuito actual (5 casos para siempre) lo nombraron como refugio 11 de los 19 que pasaron en la primera prueba: «me quedo con mis 5 procesos más delicados». Bajarlo a 3 solo para registros nuevos; a los usuarios actuales se les prometió 5 y hay que cumplirlo.

### 3. La oferta de apertura

**Precio de fundador:** los primeros 50 suscriptores conservan su precio durante 12 meses. Termina cuando se llenan los 50 cupos o el 26 de febrero de 2027, lo que ocurra primero. Sin descuento sobre el precio de lista, para no enseñar a esperar rebajas.

### 4. Qué probar con compradores reales

1. **Tope del Individual: 50 contra 80 procesos.** Siete de los 20 mencionaron el tope de 50 procesos; varios dicen que los empuja al plan de $120.000 y que ahí dudan. Dos versiones de la página de planes.
2. **Individual a $59.900 contra $69.900.** Ambos están dentro del rango; el segundo acerca el equilibrio.
3. **El dato real que ya viene en camino:** el código aplica los límites del plan Gratuito desde el **12 de diciembre de 2026** (`FirmPlan::FREE_LIMITS_FROM`). Ese día empieza la primera cohorte real de prueba → pago. Medirla vale más que cualquier panel.

### 5. Objeciones de precio, textuales, para mercadeo

- «Gano unos $2.170.000 al mes y llevo menos de 15 procesos; $120.000 es más del 5 % de mi ingreso por algo que hoy resuelvo revisando la Rama y SAMAI a mano en un rato.» (P007, primera prueba)
- «El plan de 50 procesos no me cubre y el de $120.000 es un gasto fijo más que tendría que justificarles a mis socios.» (P002, segunda prueba)
- «En los meses que paso de 50 procesos el plan que me toca es el de $120.000, y eso ya lo pienso dos veces.» (P016, segunda prueba)

Son compradores simulados: sirven para escoger qué probar, no como prueba. Nunca se usan como testimonios. No se anuncia ningún precio «antes» que no se haya cobrado.

## The offer

> **Actualización con el panel de 100 compradores.** Oferta publicada: 14 de 100. «Vigilancia en Paralelo»: **30 de 100**; 16 personas pasaron de no a sí y ninguna al revés. Quienes pasan por confianza bajan de 29 a 15 y por precio de 20 a 8; por hábito suben de 36 a 45. Quien revisa a mano pasa de 8 % a 48 %. Quien tiene dependiente, ya paga vigilancia, desconfía de la tecnología o busca lo gratis: 0 % con cualquier oferta. La segunda lectura de la competencia mostró además que varios rivales ya avisan por WhatsApp y entregan el auto en PDF: esos dos elementos de la pila igualan, no diferencian. Lo que nadie ofrece es el informe de verificación y el aviso de fallo. Las cifras de abajo son del panel inicial de 20.

Método: el lente de Ofertas de `lenses.md` (resumen de un marco publicado; no son palabras de su autor).

### 1. Lo que ya se sabía

- **Por qué pasaban** (primera prueba, 1 de 20 compra): confianza (8) y hábito (8); precio solo 2 y necesidad 1. La frase que más se repitió: «si la plataforma no ve una actuación, la que responde soy yo».
- **Qué los haría cambiar:** que durante la prueba no se le escape nada frente a su método actual (14 de 19), que un colega la recomiende (9), un plan más barato o individual (9), saber quién responde o tenerlo por escrito (3), aviso por WhatsApp (1). Conteo por palabras clave sobre `panel-v1/answers`; una respuesta puede contar en varios temas.
- **La junta** pidió una promesa con nombre, garantía, prueba más corta y un plan gratuito que no reemplace al pago.

### 2. La lista de problemas del comprador

**Antes de comprar:** no sé quién está detrás · ningún colega la usa · no quiero subir datos de mis clientes · me toca armar un Excel de radicados · ya pago dependiente o servicio y cambiarme da pereza · consultar la Rama es gratis · $120.000 es mucho para mis procesos · el plan está pensado para una firma y yo trabajo solo · no sé si cubre mi despacho o SAMAI.
**Durante:** ¿y si se le pasa una actuación? · ¿y si la página de la Rama se cae y no me entero? · el correo se me pierde, yo vivo en WhatsApp · igual tengo que revisar a mano, entonces no me ahorra nada · el aviso no trae el auto.
**Después:** si se vence un término respondo yo ante el cliente y el Consejo de Disciplina · ¿existirá la empresa en un año? · al terminar la prueba caigo a 5 casos y revisión semanal · si paso de 50 procesos me duplican el precio.

### 3. Soluciones, recortadas y apiladas

| Elemento | Problema que mata | Valor (1-5) | Costo (1-5) | Estado en el código |
| --- | --- | :-: | :-: | --- |
| **Núcleo:** aviso cada mañana de toda actuación nueva de sus procesos | revisar uno por uno | 5 | 2 | Existe para Rama Judicial. **Para SAMAI no detecta actuaciones** (`code-audit.md`, A1) |
| **Prueba en paralelo de 30 días**, al lado de su método actual | «no me fío» | 5 | 1 | Existe la prueba; falta la vista de comparación |
| **Informe de verificación de los lunes** y aviso el mismo día si una consulta falla | «el silencio parece sin novedades» | 5 | 2 | **No existe.** Hoy un fallo se marca como sincronizado (C2). 2 a 3 días de trabajo |
| **Garantía:** si la fuente lo publicó y no le avisamos en 24 horas, devolvemos el mes | «¿quién responde?» | 3 | 1 | No existe; reservada al 2 % del precio en el CFO |
| **Alertas por WhatsApp** además del correo | «vivo en WhatsApp» | 4 | 2 | Construido y apagado (`config/services.php:95`) |
| **Nosotros cargamos sus radicados** desde su Excel o una foto de su lista | «no tengo Excel» | 4 | 2 | Existe el importador; el servicio es manual |
| **Solo se necesita el radicado**, que es público | «no subo datos de clientes» | 4 | 1 | Cierto para vigilar; hay que decirlo |
| **Quién responde:** empresa, NIT y nombre del responsable en la landing | «¿quién está detrás?» | 4 | 1 | Falta en la landing |
| **Plan Individual** $59.900, 1 usuario | «pago por una firma que no soy» | 5 | 1 | No existe; es configuración de planes |

**Urgencia real:** precio de fundador congelado 12 meses para los primeros 50 suscriptores. Nada de contadores falsos.
**Lo que se deja fuera de la promesa:** IA, facturación, analítica, n8n. Siguen en el producto; no se venden en la primera pantalla.

### 4. La ecuación de valor, antes y después (1 a 10)

| | Antes | Después | Qué lo mueve |
| --- | :-: | :-: | --- |
| Resultado soñado | 7 | 8 | La promesa pasa de «suite» a «no se me pasa nada, y si falla me entero» |
| Probabilidad percibida | 3 | 6 | Prueba en paralelo, informe de los lunes, garantía, quién responde |
| Tiempo al resultado | 7 | 8 | Carga de radicados hecha por LegalWeb |
| Esfuerzo y sacrificio | 5 | 7 | WhatsApp, plan Individual, solo radicados |

### 5. La re-prueba

Mismas 20 tarjetas (semilla 7), pitch nuevo (`pitch.md`; el anterior quedó en `pitch-v1.md`).

| | Oferta original | «Vigilancia en Paralelo» |
| --- | :-: | :-: |
| Compran | 1 de 20 (5 %) | **6 de 20 (30 %)** |
| Pasan por confianza | 8 | 2 |
| Pasan por hábito | 8 | 9 |
| Pasan por precio | 2 | 3 |
| Litigante independiente | 10 % | 40 % |
| Socio de firma pequeña | 0 % | 33 % |
| Abogado con menos de 15 procesos | 0 % | 0 % |

Compraron los desordenados (3 de 3), quienes ya tuvieron un susto con un término y quienes revisan a mano en varias ciudades. Cuatro tomaron el Individual y dos el Profesional.

**Lo que no se movió, dicho sin rodeos:**
- **Quien ya tiene dependiente (0 de 3) o un servicio de vigilancia (0 de 3) no cambia.** Solo cambiaría si la prueba detecta algo que su método actual no vio. Ese es el argumento de venta para ellos, no el precio.
- **Quien lleva menos de 15 procesos no paga** (0 de 4). No es el cliente; es el usuario del plan Gratuito.
- **La garantía no convence por sí sola:** 15 de 20 mencionan la garantía o la devolución en su objeción, casi siempre con alguna versión de «me devuelven $59.900, pero el término vencido lo pago yo». Vale como señal de seriedad, no como cobertura. No prometer más que la devolución: una cobertura por el daño no es pagable (ver `cfo.md`).
- **Todos los «sí» son condicionales:** pagan solo si en 30 días no se escapa una sola actuación. La oferta traslada toda la carga a la confiabilidad real del producto, que hoy tiene tres hallazgos críticos abiertos.

### Reglas cumplidas y pendientes

- La oferta **no puede publicarse todavía**: promete el informe de verificación, el aviso de fallos, WhatsApp y cobertura de SAMAI que el código aún no entrega. Primero `ops.md`, luego la landing.
- Sin reseñas, testimonios, escasez ni descuentos inventados. Las citas del panel son investigación.
- Una garantía de devolución es una condición contractual: redactarla con un abogado y revisar el Estatuto del Consumidor (Ley 1480 de 2011). No es asesoría legal.

## The numbers

> Nota de lectura: la herramienta habla de «suscripcion-mes a day». Aquí se configuró un «día» por mes, así que **«a day» significa suscriptores de pago activos en ese mes**. Todo está en pesos colombianos (COP), antes de IVA.

### La nota del CFO

- **El margen.** Cada suscripción deja **$84.880 al mes** (92 %) después de sus propios costos, con un ingreso medio de $91.950: la mezcla del panel de 100 (16 planes Profesional de $120.000 y 14 Individual de $59.900). El margen por unidad no es el problema; el volumen sí.
- **La línea que hay que mirar: los registros al mes.** Con sueldo parcial del fundador ($3.500.000 estimado) el equilibrio son **52 suscriptores de pago** y el año 1 pierde **$21.632.080** con una rampa de 3 a 60 suscriptores. Sin sueldo (`numbers-caja.json`) el equilibrio son **10 suscriptores**, el año 1 deja **$20.367.920** y la inversión se recupera en el mes 8. La simulación (`sim/funnel_mc.md`) dice qué exige esa rampa: con 20 registros al mes la mediana es 16 clientes de pago al mes 12 (cubre la caja el 77 % de las veces y nunca el sueldo); con 40, cubre el sueldo el 9 % de las veces al mes 12 y el 45 % al mes 24; con 80, el 62 % y el 89 %.
- **Caja.** Se necesitan **$27.209.280** antes de que se pague solo si el fundador cobra; **$5.420.080** si no.
- **Tres formas de mejorar el margen (corridas con la herramienta):**
  1. Individual a $69.900 con 80 procesos: el panel compró igual y el ingreso medio sube a unos $93.300.
  2. Cobro recurrente: hoy cada periodo es un pago manual. No cambia el margen por unidad, pero cada punto menos de baja mensual vale más que cualquier ajuste de precio (la simulación usa 3–7 %).
  3. Un precio 10 % menor sube el equilibrio de 52 a 58 suscriptores; un volumen 20 % menor deja el margen en −6 %. El volumen pesa más que el precio.
- **Condiciones de dinero de la junta.** Garantía asequible: sí, reservada al 2 % del precio ($1.840 por suscripción), midiendo la tasa real de reclamos. IA de pago e infraestructura propia: caben ($130.000 al mes estimados). Canal con costo de adquisición medido: **sin dato**.
- **Costos confirmados con fuentes públicas** (`verify/market-costs.md`): Wompi 2,65 % + $700 + IVA sobre la comisión; WhatsApp por Twilio cerca de USD 0,006 por mensaje; un servidor de 2 núcleos entre USD 6 y 24 al mes; correo transaccional entre USD 1 y 20 por 10.000 envíos. El servicio en la nube podría estar excluido de IVA (Estatuto Tributario, artículo 476); lo define un contador.
- **Lo que este CFO no sabe y el fundador sí:** registros al mes, clientes de pago, bajas y costos reales. Reemplace las estimaciones de `numbers.json` y vuelva a correr la herramienta.

Un contador debe revisar la estructura, el IVA y la facturación electrónica antes de mover dinero. No es asesoría financiera, tributaria ni legal.

### Simulación del embudo (5.000 corridas por fila)

| Registros al mes | Pagos mes 12 (p10 / mediana / p90) | Pagos mes 24 | Ingreso mensual mes 12 | Cubre caja mes 12 | Cubre sueldo mes 12 | Cubre sueldo mes 24 | Caja acumulada mes 12 (sin sueldo) | Acumulado mes 12 (con sueldo) |
| ---: | :-: | ---: | ---: | :-: | :-: | :-: | ---: | ---: |
| 10 | 3 / 8 / 14 | 13 | $689.000 | 27 % | 0 % | 0 % | $-6.090.000 | $-48.090.000 |
| 20 | 8 / 16 / 26 | 26 | $1.400.000 | 77 % | 0 % | 3 % | $-1.860.000 | $-43.860.000 |
| 40 | 17 / 32 / 50 | 52 | $2.811.000 | 98 % | 9 % | 45 % | $6.330.000 | $-35.670.000 |
| 80 | 35 / 65 / 97 | 105 | $5.617.000 | 100 % | 62 % | 89 % | $22.730.000 | $-19.270.000 |
| 150 | 65 / 120 / 181 | 194 | $10.450.000 | 100 % | 94 % | 100 % | $51.060.000 | $9.060.000 |

Supuestos: conversión de prueba a pago entre 4 % y 15 %, baja mensual entre 3 % y 7 %, ingreso medio entre $59.900 y $120.000, un mes de prueba. Código en `sim/funnel_mc.py`.

### Salida de la herramienta (escenario base, con sueldo parcial)

### One suscripcion-mes

| line | per suscripcion-mes |
| --- | ---: |
| Price | $91,950.00 |
| Comision Wompi (2,65% + $700 + IVA sobre la comision) | -$3,730.00 |
| Alertas por WhatsApp y correo por suscriptor (estimado con tarifas publicas) | -$1,500.00 |
| Reserva para la garantia de devolucion (2% del precio, estimado) | -$1,840.00 |
| **Contribution** (what each suscripcion-mes leaves to pay the fixed costs) | **$84,880.00** (92%) |

### The margin that matters

Fixed costs: $4,342,000 a month (Hosting compartido actual (estimado) $45,000, Dominio y correo (estimado) $7,000, Servidor propio para la vigilancia nocturna con monitoreo (estimado) $50,000, IA de pago en vez de niveles gratuitos (estimado) $80,000, Envio de correo transaccional (estimado) $60,000, Mercadeo: pauta y contenido (estimado) $600,000, Sueldo parcial del fundador (estimado, medio tiempo) $3,500,000).

- **Break-even: 52 suscripcion-mess a day.** Below that you lose money every month.
- **Profit margin at your plan** (60 a day): **14%** of every sale, after every cost.

### Year 1, month by month

| month | suscripcion-mess a day | revenue | profit | cumulative (after $4,500,000 startup) |
| ---: | ---: | ---: | ---: | ---: |
| 1 | 3 | $275,850 | -$4,087,360 | -$8,587,360 |
| 2 | 6 | $551,700 | -$3,832,720 | -$12,420,080 |
| 3 | 10 | $919,500 | -$3,493,200 | -$15,913,280 |
| 4 | 15 | $1,379,250 | -$3,068,800 | -$18,982,080 |
| 5 | 20 | $1,839,000 | -$2,644,400 | -$21,626,480 |
| 6 | 26 | $2,390,700 | -$2,135,120 | -$23,761,600 |
| 7 | 32 | $2,942,400 | -$1,625,840 | -$25,387,440 |
| 8 | 38 | $3,494,100 | -$1,116,560 | -$26,504,000 |
| 9 | 44 | $4,045,800 | -$607,280 | -$27,111,280 |
| 10 | 50 | $4,597,500 | -$98,000 | -$27,209,280 |
| 11 | 55 | $5,057,250 | $326,400 | -$26,882,880 |
| 12 | 60 | $5,517,000 | $750,800 | -$26,132,080 |

- **Year 1 operating profit: -$21,632,080** on $33,010,050 of revenue.
- After the $4,500,000 startup spend: -$26,132,080.
- Startup money earned back: not within year 1.
- Cash you need before it pays for itself: **$27,209,280**.

### What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | 14% | 52 | -$21,632,080 |
| Price -10% | 4% | 58 | -$24,933,085 |
| Volume -20% | -6% | 52 | -$27,726,464 |
| Unit costs +15% | 12% | 52 | -$22,012,800 |

### Red flags

- Year 1 loses money on operations (-$21,632,080).
- The startup spend is not earned back within year 1.

## Marketing

### 1. La evidencia

- **Quién compra** (segunda prueba): el litigante independiente (40 %) y el socio de firma pequeña (33 %); dentro de ellos, el desordenado que ya tuvo un susto con un término (3 de 3) y el que revisa a mano en varias ciudades. **Quién no:** el que lleva menos de 15 procesos (0 de 4), el que ya tiene dependiente (0 de 3) o servicio de vigilancia (0 de 3).
- **La objeción que manda:** confianza. Nueve de los 19 que pasaron en la primera prueba pedían que un colega conocido la recomendara. Ningún anuncio resuelve eso; lo resuelven colegas y una prueba que se puede verificar.
- **Competidores** (`competitors.md`): al menos nueve venden alertas entre $49.900 y $93.250; varios ya ofrecen WhatsApp e IA. No sonar como ellos («vigila tus procesos y recibe alertas»).
- **Lo que vale un cliente:** $71.950 de contribución al mes (`cfo.md`).

### 2. Posicionamiento

Recomendada:

> Para el abogado litigante que revisa sus procesos a mano y ya vivió el susto de un término, **LegalWeb es la vigilancia judicial que se deja comprobar**: 30 días corriendo al lado de su propia revisión, un informe cada lunes de lo que se consultó, y aviso el mismo día si una consulta falla. A diferencia de los servicios de alertas, aquí el silencio nunca significa «sin novedades».

Alternativas descartadas:
- «La suite completa para la firma pequeña a precio fijo» — apoya el hueco de precios que halló el scout, pero ningún comprador del panel pidió una suite.
- «La más barata con procesos ilimitados» — cierta solo para quien lleva más de 150 procesos, y obliga a competir por precio.

Evidencia: 14 de 19 dijeron que cambiarían de opinión si durante la prueba no se le escapa nada; la confianza cayó de 8 objeciones a 2 con esta promesa.

### 3. Canales

| Canal | Por qué le sirve a este comprador | Costo aproximado | Cómo se sabe que funcionó |
| --- | --- | --- | --- |
| **Referidos entre colegas** (un mes gratis para quien recomienda y para el recomendado) | Ataca la objeción número uno: «ningún colega la usa» | Un mes de servicio por alta | Altas con código de referido / altas totales |
| **Charla gratuita «Cómputo de términos y vacancia judicial 2027»** con colegios de abogados, consultorios jurídicos y grupos de WhatsApp de litigantes | El abogado desconfiado adopta lo que ve usar a un par, y el tema toca el miedo real | Tiempo del fundador; $0 a $300.000 por sesión (estimado) | Asistentes que inician la prueba en paralelo |
| **Búsqueda en Google** («vigilancia judicial», «consulta de procesos rama judicial alertas») | Captura a quien ya está buscando la solución | $300.000 al mes (estimado, dentro del presupuesto del CFO) | Costo por prueba iniciada y por pago |

**Lo que no se hace todavía y por qué:** pauta en redes (no resuelve confianza y no hay costo de adquisición medido), correos masivos en frío (riesgo legal bajo la Ley 1581 y daño a la reputación del dominio que envía las alertas), contenido de IA jurídica (no es la promesa).

**Subsegmento para empezar:** litigantes independientes en civil, familia, laboral y ejecutivos ante la Rama Judicial, con 20 a 150 procesos, en la ciudad donde el fundador tenga colegas que puedan dar fe. **No lo contencioso administrativo:** hoy SAMAI no detecta actuaciones (`code-audit.md`, A1) y sería prometer lo que no se entrega.

### 4. La campaña de 30 días

Lanzamiento: **martes 12 de enero de 2027**, primer día hábil después de la vacancia judicial (20 de diciembre a 10 de enero; el lunes 11 es festivo). El mensaje se escribe solo: vuelven los términos.

| Fecha | Canal | Qué sale |
| --- | --- | --- |
| Mar 29 dic | Correo a usuarios actuales | «En enero cambia la forma en que le avisamos»: informe de los lunes y aviso de fallos |
| Mié 30 dic | WhatsApp a 10 colegas piloto | Invitación personal a la prueba en paralelo |
| Lun 4 ene | LinkedIn del fundador | Quién está detrás de LegalWeb: nombre, NIT, por qué lo construyó |
| Mié 6 ene | Colegios y grupos | Anuncio de la charla del jueves 14 |
| Vie 8 ene | Correo a registrados inactivos | «El martes vuelven los términos. ¿Cuántos procesos va a revisar a mano?» |
| **Mar 12 ene** | Landing nueva, correo, WhatsApp, Google | **Lanzamiento de «Vigilancia en Paralelo»** y precio de fundador (50 cupos) |
| Jue 14 ene | Charla en vivo | Cómputo de términos 2027; al final, cómo comparar en paralelo |
| Lun 18 ene | Correo a quienes están en prueba | Primer informe de verificación de los lunes, explicado |
| Mié 20 ene | LinkedIn / grupos | Tablero público: consultas hechas, fallos avisados (cifras reales) |
| Lun 25 ene | Correo | Segundo informe; recordatorio del programa de referidos |
| Jue 28 ene | Correo | Cupos de fundador restantes (cifra real) y revisión de la quincena |

**Diez ganchos**, cada uno contra una objeción del panel:
1. «Póngala a prueba al lado de su dependiente. Si él ve algo que nosotros no, no nos pague.» (ya tengo quien revise)
2. «Cada lunes le decimos qué consultamos. Y si un día no pudimos, se lo decimos ese día.» (¿y si falla y no me entero?)
3. «Solo necesitamos el radicado. Los datos de su cliente se quedan con usted.» (no subo datos)
4. «Mándenos una foto de su lista de procesos. Nosotros los cargamos.» (no tengo Excel)
5. «El aviso le llega donde usted sí mira: WhatsApp.» (el correo se me pierde)
6. «$59.900 al mes si trabaja solo. Sin permanencia.» (pago por una firma que no soy)
7. «Esto es lo que somos: [nombre], [NIT], [teléfono].» (¿quién está detrás?)
8. «El 12 de enero vuelven los términos. ¿Cuántos radicados va a abrir uno por uno?» (hábito)
9. «Si la fuente lo publicó y no le avisamos en 24 horas, le devolvemos el mes.» (¿quién responde?)
10. «No reemplace su revisión: compruébela durante 30 días.» (igual reviso a mano)

Oferta de la semana de lanzamiento: precio de fundador (`pricing.md`), hasta llenar 50 cupos o el 26 de febrero de 2027. Cuando no haya nada nuevo que decir: el tablero de consultas de la semana y una regla de cómputo de términos explicada con un ejemplo.

### 5. Presupuesto y números

- **Reparto** de los $600.000 mensuales estimados: $300.000 búsqueda en Google, $200.000 charlas, $100.000 referidos (costo del mes regalado).
- **Lo máximo que se puede pagar por un cliente:** contribución de $71.950 al mes × los meses que se quede. La permanencia real **no se conoce**. Si se quedara 6 meses dejaría $431.700; gastar un tercio de eso da un tope de **$144.000 por cliente de pago**. La junta puso el techo absoluto en $600.000.
- **Tres números para mirar cada semana**, y cuándo cambiar algo: pruebas iniciadas (menos de 5 a la semana: cambiar el canal); pruebas que cargan al menos 10 radicados en 7 días (menos de la mitad: el problema es la carga, no el mensaje); costo por cliente de pago (más de $144.000 dos semanas seguidas: pausar la pauta).

### Reglas

Sin reseñas, cifras de usuarios ni testimonios inventados. Las citas del panel son investigación y no se usan en anuncios. Publicar solo cifras reales en el tablero. Las afirmaciones sobre secreto profesional y protección de datos deben coincidir con lo que el código hace (`code-audit.md`, A4). No es asesoría legal.

## Brand

### 1. El encargo

El negocio ya tiene nombre, dominio y logo. Esta sección no propone rebautizarlo: revisa el riesgo del nombre actual, nombra **la oferta** y fija voz y promesa según el posicionamiento de `marketing.md`.

### 2. El nombre

**«LegalWeb» es descriptivo y genérico.** Se entiende a la primera, pero dos palabras comunes son difíciles de registrar como marca y fáciles de confundir. Verificaciones que el fundador debe hacer (yo solo puedo sugerir búsquedas públicas; un abogado de marcas lo confirma y nada queda registrado hasta que se registre):

- **Marca:** buscar «LegalWeb» y «Legal Web» en el sistema de la Superintendencia de Industria y Comercio (SIC, sipi.sic.gov.co), clases 9 (software), 42 (software como servicio) y 45 (servicios jurídicos).
- **Dominio:** legalweb.com.co está en uso por el negocio; comprobar quién tiene legalweb.co y legalweb.com.
- **Usuarios:** Instagram, LinkedIn, X, YouTube, TikTok.
- **Confusión:** en `competitors.md` aparece **Legal Procesos**; ningún competidor listado se llama LegalWeb.

**Nombre para la oferta** (diez candidatos; el nombre de la empresa no cambia):

| Candidato | Qué dice | Riesgo |
| --- | --- | --- |
| **Vigilancia en Paralelo** | Se prueba al lado de lo que ya hace | Largo para un botón |
| **Doble Revisión** | Usted revisa, nosotros también | Puede sonar a trabajo doble |
| **Informe de los Lunes** | El entregable concreto | Nombra una parte, no el todo |
| Cero Silencio | Si falla, se entera | Promesa absoluta, difícil de sostener |
| Término Seguro | El resultado soñado | Promete un resultado legal: evitar |
| Vigía | Corto, evocador | Probablemente registrado; verificar |
| Radicado al Día | Descriptivo | Genérico |
| Centinela Judicial | Evocador | Suena a servicio con personas |
| Prueba de 30 | La mecánica | No dice de qué |
| Aviso Verificado | El diferencial | Frío |

Recomendado: **Vigilancia en Paralelo**, con «el informe de los lunes» como nombre del entregable.

### 3. Voz y promesa

- **La promesa:** *si su proceso se mueve, usted se entera; y si no pudimos consultarlo, también.*
- **Lema, tres opciones:** «Vigilancia judicial que se deja comprobar» (recomendado) · «El silencio nunca es sin novedades» · «Compruébelo 30 días al lado de su revisión».
- **La voz:**
  - *Precisa.* Sí: «Consultamos 42 de sus 43 procesos; el radicado 2024-00180 falló a las 3:14 a.m.» No: «Vigilancia inteligente 24/7».
  - *De colega.* Sí: trato de usted, vocabulario del litigio. No: jerga de tecnología ni signos de admiración.
  - *Franca sobre sus límites.* Sí: «No reemplace su revisión: compruébela.» No: «Nunca más pierda un término».
- **Antes y después** de una línea del pitch original:
  - Antes: «LegalWeb vigila tus procesos y te avisa por correo. Sin perder un término.»
  - Después: «Cada madrugada consultamos sus radicados. Si hay actuación nueva le avisamos; si una consulta falla, también.»

Nota: la landing tutea y el panel se dirige de «Dr(a).». Unificar en **usted**.

### 4. El aspecto, como encargo

- **Color** (valores tomados del código):
  - `#1E3A5F` azul profundo: fondos de encabezado y texto. Blanco encima: contraste 11,5 a 1, pasa AA.
  - `#3A86FF` azul vivo: acentos e iconos. **Blanco encima: 3,48 a 1, no pasa AA para texto normal.** Hoy se usa en botones con texto blanco; cambiar el fondo del botón a `#1D4ED8` (6,7 a 1) o usar texto grande y en negrita.
  - `#F5F7FA` gris claro: fondos. `#1E3A5F` encima: 10,7 a 1.
- **Tipografía:** Inter para texto y Poppins para títulos, ambas ya en uso y con licencia abierta (SIL Open Font License, Google Fonts).
- **Logo:** el escudo con balanza y línea de circuito ya existe en tres versiones. Debe seguir funcionando en 32 px (icono) y sobre el azul del encabezado de los correos.
- **Los cinco primeros puntos de contacto:** la primera pantalla de la landing (promesa nueva y quién responde) · el correo de «nueva actuación» · **el informe de los lunes** (el punto de contacto más importante y hoy no existe) · el aviso de «consulta fallida» · la respuesta a la primera queja.

### Reglas

No copiar nombre, logo, colores ni lema de un competidor. No usar fuentes ni imágenes sin derecho. No es asesoría legal sobre marcas.

## Operations

Para un SaaS, operar es mantener la promesa cada madrugada. Esta sección convierte la auditoría (`code-audit.md`) en rutina.

### 1. El ciclo diario

| Hora | Paso | ¿Lo ve el cliente? |
| --- | --- | :-: |
| 3:00 a.m. | Consulta de todos los procesos vigilados en la Rama Judicial y SAMAI | No |
| 3:00–6:00 | Reintento de las consultas fallidas | No |
| 6:00 | Verificación de modelos de IA | No |
| 6:30 | **Control de salud (no existe hoy):** ¿corrió la tarea?, ¿qué porcentaje de consultas tuvo éxito?, ¿el radicado testigo devolvió lo esperado? | No |
| 7:00 | Correo y WhatsApp de actuaciones nuevas; **aviso de consultas fallidas** | **Sí** |
| 8:00 | Recordatorios de vencimientos | **Sí** |
| 9:00–10:00 | Avisos de límites del plan y correos de ciclo de vida | Sí |
| Durante el día | Soporte, carga de radicados para pruebas nuevas, pagos | **Sí** |
| Lunes 7:00 | **Informe de verificación (no existe hoy)** | **Sí** |

### 2. Proveedores

No se inventan precios: donde dice «cotizar» falta un dato que solo el fundador puede conseguir.

| Insumo | Hoy | Alternativas para cotizar | Nota |
| --- | --- | --- | --- |
| Servidor para la tarea nocturna | Hosting compartido cPanel, tareas por HTTP | Un VPS con programador de tareas real y proceso de cola (dos o tres proveedores) | El hosting actual limita a 1.000–1.500 procesos por noche en el mejor caso (C3) |
| Fuente de datos | APIs públicas no contractuales de la Rama Judicial y SAMAI | No hay proveedor alterno | Riesgo número uno; se mitiga con detección, no con compra |
| Correo transaccional | Correo del hosting (supuesto) | Un servicio de envío con registro de entregas | El aviso es el producto: hay que poder probar que salió |
| WhatsApp | Twilio, integrado y apagado | Twilio; plantillas aprobadas por Meta | Cobro por mensaje: cotizar para 50 suscriptores |
| IA | Niveles gratuitos de Gemini, OpenRouter y NVIDIA | Un plan de pago que por contrato no entrene con los datos | La junta lo puso como condición |
| Pagos | Wompi | — | Pago único por periodo; **no hay renovación automática**, así que cada mes se depende de que el cliente vuelva a pagar |
| Monitoreo | Ninguno | Un servicio de «señal de vida» que avise si la tarea no reporta | Barato y urgente (C2) |

Si un precio real difiere de `numbers.json`, actualizarlo y volver a correr el CFO.

### 3. Personas

Una sola persona hoy. Reparto semanal propuesto con el plan actual (hasta ~60 suscriptores):

| Rol | Horas a la semana | Quién |
| --- | :-: | --- |
| Revisar el control de salud y atender fallos | 3 | Fundador (cada mañana, 20 minutos) |
| Soporte y carga de radicados | 5 | Fundador |
| Desarrollo (solo la lista de la sección 8) | 8 | Fundador |
| Ventas: charlas, colegas, seguimiento de pruebas | 6 | Fundador |
| Revisión jurídica de reglas de plazos y textos legales | 2 al mes | **Un abogado litigante externo** (cotizar) |

El supuesto de sueldo ($3.500.000 por medio tiempo) es una estimación de `numbers.json`. No hay nómina; si se contrata a alguien, las cargas laborales las calcula un contador.

**Punto único de falla:** si el fundador se enferma una semana, nadie mira el control de salud. Mitigación mínima: que las alertas de salud lleguen también a una segunda persona de confianza.

### 4. Rutinas

**Cada mañana (20 minutos)**
1. Abrir el control de salud: ¿corrió la tarea de las 3:00?
2. Tasa de éxito de consultas. Menos de 95 %: investigar antes de las 8:00.
3. Radicado testigo: ¿coincide con lo esperado? Si no, la fuente cambió.
4. Confirmar que salieron los avisos de fallo a los abogados afectados.
5. Revisar la cola de correos y WhatsApp.

**Cuando la fuente falla o cambia**
1. Banner en el panel y correo a todos: «Hoy no pudimos consultar la Rama Judicial. Revise a mano sus procesos con términos corriendo.»
2. No marcar ningún proceso como sincronizado.
3. Reintentar cada hora.
4. Al recuperarse: correo de «servicio restablecido» con el detalle de lo que se encontró.
5. Anotar el incidente en el tablero público.

**Una queja por una actuación no avisada**
1. Responder el mismo día, por el canal en que llegó.
2. Verificar en la fuente: fecha de publicación contra fecha de aviso.
3. Si la fuente la publicó y no se avisó en 24 horas: devolver el mes, sin discusión.
4. Explicar la causa y qué se cambió.
5. Registrar el caso: de ahí sale la tasa de reclamos que el CFO necesita.

**Alta de una prueba en paralelo**
1. Recibir la lista (Excel, foto o texto) y cargar solo los radicados.
2. Confirmar por WhatsApp cuántos entraron y cuáles no se encontraron.
3. Día 7: ¿recibió el primer informe?
4. Día 25: enviar la comparación del mes y la invitación a pagar.

**Cada lunes:** informe de verificación a clientes; números de la semana (`marketing.md`, sección 5).

### 5. Herramientas

Lo mínimo: el panel propio (ya existe: CRM de firmas, planes, pagos), un servicio de señal de vida, un servicio de envío de correo con registro, integración continua en GitHub Actions (gratis para repositorios públicos; verificar el cupo para privados) y facturación electrónica (la exige la DIAN según el régimen; lo define el contador).

### 6. Licencias, permisos y seguros: qué verificar

Las reglas cambian y dependen del caso; cada punto se confirma con la autoridad o con un profesional. No es asesoría legal.

- [ ] Matrícula mercantil y forma jurídica (Cámara de Comercio).
- [ ] RUT, responsabilidad de IVA sobre el servicio y facturación electrónica (DIAN).
- [ ] **Política de tratamiento de datos y Registro Nacional de Bases de Datos** (Ley 1581 de 2012, SIC). La auditoría halló que no hay retención ni borrado definidos y que Google Analytics carga dentro del panel.
- [ ] Contrato de encargo de tratamiento con cada proveedor que reciba datos (IA, correo, WhatsApp).
- [ ] Términos del servicio y **texto de la garantía** revisados frente al Estatuto del Consumidor (Ley 1480 de 2011).
- [ ] Registro de marca ante la SIC (`brand.md`).
- [ ] Términos de uso de las páginas de la Rama Judicial y SAMAI: confirmar que la consulta automatizada está permitida y con qué frecuencia.
- [ ] Las afirmaciones «secreto profesional garantizado» y «cumplimiento Ley 1581» de la landing deben poder demostrarse o retirarse.
- [ ] Seguro de responsabilidad civil profesional o de errores tecnológicos: cotizar; el panel entero preguntó «¿quién responde?».

### 7. Registro de riesgos

| # | Riesgo | Probabilidad | Impacto | Plan |
| --- | --- | :-: | :-: | --- |
| 1 | La Rama Judicial cambia o bloquea la consulta | Alta (ya pasó una vez) | Muy alto | Radicado testigo, aviso a clientes el mismo día, rutina de fuente caída |
| 2 | Una consulta falla y parece «sin novedades» | Alta (ocurre hoy) | Muy alto | Arreglo C2: no marcar como sincronizado, reintentar, avisar |
| 3 | Un plazo se calcula un día tarde por un festivo mal puesto | Cierta para los que crucen esos días | Muy alto | Arreglo C1 y pruebas contra el calendario oficial |
| 4 | La tarea nocturna no termina al crecer | Media | Alto | Lotes ordenados y cola cada minuto; luego VPS |
| 5 | El fundador no está disponible | Media | Alto | Alertas a una segunda persona; rutinas escritas |
| 6 | Alguien obtiene la clave de `setup.php` | Baja | Muy alto | Arreglo A2: restringir por IP, acciones por POST, o retirarlo |
| 7 | Un competidor iguala la oferta | Alta | Medio | La ventaja es el historial público de confiabilidad, no la función |
| 8 | Queja ante la SIC por datos personales o publicidad | Baja | Alto | Lista de la sección 6; alinear la landing con el código |
| 9 | El cliente no renueva porque el pago es manual | Alta | Medio | Recordatorios ya existen; evaluar cobro recurrente con Wompi |
| 10 | Los modelos gratuitos de IA dejan de funcionar o entrenan con los datos | Media | Medio | IA de pago o IA fuera de la promesa |

### 8. Lo que hay que construir, en orden (solo esto; lo demás se congela)

| # | Qué | Hallazgo | Esfuerzo estimado |
| --- | --- | --- | --- |
| 1 | Corregir el calendario judicial y probarlo contra 2026–2028 | C1 | 1 día |
| 2 | Fallos visibles: no marcar como sincronizado, reintentar, avisar al abogado y al administrador; señal de vida | C2 | 2–3 días |
| 3 | Corrida nocturna por lotes ordenados y cola cada minuto | C3 | 3–4 días |
| 4 | Cerrar `setup.php`; corregir los avisos de etapas que nunca se envían | A2, A3 | 1–2 días |
| 5 | Alinear la landing: SAMAI, cédula o NIT, «dos días antes», documentos, secreto profesional; ocultar el radicado a la IA | A1, A4 | 1–2 días |
| 6 | Radicado testigo y alerta de cambio de formato | A5 | 1–2 días |
| 7 | Informe de los lunes, vista de comparación de la prueba, plan Individual, WhatsApp encendido | Oferta | 4–6 días |
| 8 | Integración continua y pruebas de calendario, plazos y cola | A6 | 2–3 días |

Total: entre 15 y 23 días de trabajo. Los puntos 1 a 6 son deuda con los clientes actuales; el 7 es la oferta nueva.

## Launch plan

LegalWeb ya está en producción. Lo que se lanza es **la oferta «Vigilancia en Paralelo»**, y solo después de saldar la deuda de confiabilidad.

### 1. Probar antes de gastar

Dos pruebas reales, baratas, antes de invertir en mercadeo:

**Prueba A: la cohorte que ya existe.** El código aplica los límites del plan Gratuito desde el **12 de diciembre de 2026** (`FirmPlan::FREE_LIMITS_FROM`). Ese día las firmas cuya prueba terminó caen a 5 casos y revisión semanal. Es la primera medición real de prueba → pago y no cuesta nada.
- *Antes del 12:* contar cuántas firmas tienen más de 5 casos activos (son las que sentirán el límite).
- *Línea de éxito:* que pague al menos el **10 %** de esas firmas antes del 15 de enero de 2027.
- *Precaución:* el límite llega ocho días antes de la vacancia judicial. Si los arreglos 1 y 2 de `ops.md` no están listos, considere mover la fecha: degradar el servicio a clientes mientras los fallos son invisibles es el peor orden posible.

**Prueba B: diez pilotos en paralelo.** Diez litigantes conocidos, con 20 a 150 procesos en la Rama Judicial, corren LegalWeb 30 días al lado de su método actual, del 12 de enero al 11 de febrero de 2027.
- *Línea de éxito:* al menos 8 de 10 terminan la comparación, **cero actuaciones omitidas** que la fuente sí publicó, y **al menos 3 pagan** al final.
- El panel dio 30 % (6 de 20) como techo. Si los pilotos reales quedan muy por debajo, se les cree a ellos y se vuelve a `offer.md` o `pricing.md`.
- Con su permiso escrito, esos pilotos son los primeros colegas que pueden dar fe: lo único que resuelve la objeción de confianza.

### 2. La cuenta regresiva

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

### 3. El día del lanzamiento (martes 12 de enero de 2027)

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

### 4. Los primeros 30 días

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

_The panel is simulated buyers and the numbers are projections from your inputs. Confirm demand with real customers and costs with real quotes before you spend. Not financial, legal or tax advice._
