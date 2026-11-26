# La oferta: «Vigilancia en Paralelo»

Método: el lente de Ofertas de `lenses.md` (resumen de un marco publicado; no son palabras de su autor).

## 1. Lo que ya se sabía

- **Por qué pasaban** (primera prueba, 1 de 20 compra): confianza (8) y hábito (8); precio solo 2 y necesidad 1. La frase que más se repitió: «si la plataforma no ve una actuación, la que responde soy yo».
- **Qué los haría cambiar:** que durante la prueba no se le escape nada frente a su método actual (14 de 19), que un colega la recomiende (9), un plan más barato o individual (9), saber quién responde o tenerlo por escrito (3), aviso por WhatsApp (1). Conteo por palabras clave sobre `panel-v1/answers`; una respuesta puede contar en varios temas.
- **La junta** pidió una promesa con nombre, garantía, prueba más corta y un plan gratuito que no reemplace al pago.

## 2. La lista de problemas del comprador

**Antes de comprar:** no sé quién está detrás · ningún colega la usa · no quiero subir datos de mis clientes · me toca armar un Excel de radicados · ya pago dependiente o servicio y cambiarme da pereza · consultar la Rama es gratis · $120.000 es mucho para mis procesos · el plan está pensado para una firma y yo trabajo solo · no sé si cubre mi despacho o SAMAI.
**Durante:** ¿y si se le pasa una actuación? · ¿y si la página de la Rama se cae y no me entero? · el correo se me pierde, yo vivo en WhatsApp · igual tengo que revisar a mano, entonces no me ahorra nada · el aviso no trae el auto.
**Después:** si se vence un término respondo yo ante el cliente y el Consejo de Disciplina · ¿existirá la empresa en un año? · al terminar la prueba caigo a 5 casos y revisión semanal · si paso de 50 procesos me duplican el precio.

## 3. Soluciones, recortadas y apiladas

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

## 4. La ecuación de valor, antes y después (1 a 10)

| | Antes | Después | Qué lo mueve |
| --- | :-: | :-: | --- |
| Resultado soñado | 7 | 8 | La promesa pasa de «suite» a «no se me pasa nada, y si falla me entero» |
| Probabilidad percibida | 3 | 6 | Prueba en paralelo, informe de los lunes, garantía, quién responde |
| Tiempo al resultado | 7 | 8 | Carga de radicados hecha por LegalWeb |
| Esfuerzo y sacrificio | 5 | 7 | WhatsApp, plan Individual, solo radicados |

## 5. La re-prueba

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

## Reglas cumplidas y pendientes

- La oferta **no puede publicarse todavía**: promete el informe de verificación, el aviso de fallos, WhatsApp y cobertura de SAMAI que el código aún no entrega. Primero `ops.md`, luego la landing.
- Sin reseñas, testimonios, escasez ni descuentos inventados. Las citas del panel son investigación.
- Una garantía de devolución es una condición contractual: redactarla con un abogado y revisar el Estatuto del Consumidor (Ley 1480 de 2011). No es asesoría legal.
