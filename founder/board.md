# Junta directiva: LegalWeb

**Voto: 3 «FUND IF», 0 «FUND», 0 «PASS». Puntaje promedio: 4,7 / 10** (Ofertas 5, Monopolio 4, Producto 5).

Los tres miembros aplican marcos publicados (resumidos en `lenses.md` de la skill); no hablan en nombre de sus autores.

## Riesgos que señaló más de un miembro (van primero)

1. **La promesa central descansa en una fuente ajena y frágil** (los tres). Las APIs de la Rama Judicial y SAMAI no son contractuales, ya cambiaron una vez, traen anotaciones vacías y no entregan los autos. Una actuación no detectada es un término vencido. Sin confiabilidad medida ni garantía, el abogado paga y sigue revisando a mano, o no paga.
2. **No hay distribución demostrada** (Ofertas y Monopolio). Hay landing, registro y plantillas de correo, pero ningún canal con costo de adquisición conocido.
3. **No se conocen los números propios** (los tres): registros, activación, conversión de prueba a pago, churn.
4. **Falta de foco** (Producto; Ofertas lo roza): siete o más módulos sostenidos por una persona; se lee como suite genérica y empuja a competir por precio.
5. **La oferta regala demasiado** (Ofertas; Producto lo ve desde la degradación): 3 meses completos sin tarjeta más un plan gratuito para siempre, sin urgencia; y al terminar la prueba el usuario cae a 5 casos con sincronización semanal, el momento de mayor fricción.
6. **Nada es propietario** (Monopolio): datos públicos, IA en niveles gratuitos; lo que fue barato de construir es barato de copiar.

## Condiciones, fusionadas (lista de verificación para el resto del pack)

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

## Dónde no están de acuerdo

- **Precio.** Ofertas cree que $120.000 con casos ilimitados deja margen para subir; Monopolio ve una mejora incremental frente al dependiente judicial, lo que limita cuánto se puede cobrar. El panel y pricing lo dirimen.
- **La suite.** Ofertas y Monopolio ven valor en los módulos contiguos (pila de la oferta, costo de cambio); Producto pide recortarlos porque compiten con lo único que debe ser infalible.
- **La IA.** Ofertas la cuenta como parte de la pila; Producto la quiere pagada o fuera.

## La versión más fuerte de este negocio que ve la junta

No es «la suite legal para abogados colombianos», que es lo que hoy presenta la landing. Es **un servicio de vigilancia judicial con confiabilidad demostrada y garantizada**: un nombre, una promesa («si la fuente lo publicó y no le avisamos, le devolvemos el mes»), un tablero público de detección y latencia, y aviso inmediato cuando una sincronización no se completó. Los vencimientos automáticos y el portal del cliente son la pila que lo vuelve difícil de dejar; el resto se oculta hasta que haya uso que lo justifique. Empieza en un subsegmento que se pueda dominar y acumula un dato que nadie más tiene: cuánto tarda de verdad cada despacho. Esto difiere de lo que se presentó, y la junta lo dice sin rodeos: el producto ya hace más de lo que necesita, y demuestra menos de lo que promete.

## Segunda sesión, con la evidencia verificada

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
