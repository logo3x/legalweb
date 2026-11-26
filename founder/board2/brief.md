# Brief para la junta (segunda sesión): LegalWeb, con evidencia verificada

Este documento es todo lo que sabes del negocio. Precios en pesos colombianos (COP). Fecha: 26 de noviembre de 2026. Todo lo marcado «verificado» se comprobó ejecutando el código o con dos lecturas independientes de fuentes públicas; lo marcado «estimado» o «sin dato» no.

## La idea

SaaS web (legalweb.com.co) que cada madrugada consulta los procesos de un abogado colombiano en la Rama Judicial y avisa por correo cuando hay una actuación nueva. Alrededor: expediente, términos en días hábiles, portal del cliente, IA jurídica, facturación por caso. Cliente: litigante independiente o firma de 1 a 10 abogados. Oferta publicada hoy: 3 meses gratis sin tarjeta; luego plan Gratuito (5 casos, 1 usuario, sincronización semanal desde el 12 de diciembre de 2026), Profesional $120.000/mes (ilimitado, 3 usuarios) o Firma $250.000/mes (10 usuarios). Un fundador único construye con asistentes de IA. Sin dato: firmas registradas, cuántas pagan, churn, costos reales, costo de adquisición.

## Lo que se verificó en el código (ejecutando 235 pruebas y pruebas nuevas)

**Sólido (verificado):** pagos con Wompi (firma, monto, idempotencia); aislamiento entre firmas (9 pruebas de ataque, ninguna fuga); portal del cliente por token; `.env`, código y carpetas internas no quedan expuestos por web; 234 de 235 pruebas pasan.

**Defectos que tocan la promesa central (verificados):**
1. **Calendario judicial equivocado.** En 2026 omite tres festivos reales (1 y 18 de mayo, 8 de junio) y marca como festivos tres días hábiles. Sobre 1.840 cálculos de términos de 2026–2027, 11,1 % sale distinto: 6,2 % vence **más tarde** de lo correcto (hasta 4 días) y 4,9 % antes. Afecta la calculadora y los recordatorios automáticos.
2. **Fallos invisibles.** Si la consulta falla, el caso queda marcado como «recién sincronizado» y no hay aviso al abogado. Peor: si el servicio de actuaciones responde con error o cambia de formato, el sistema registra «Sin novedades. 0 actuaciones verificadas». No existe ninguna alerta de que la tarea nocturna dejó de correr.
3. **Posible revisión cada 48 horas en vez de 24**, por un filtro de «más de 24 horas» combinado con una tarea a hora fija (simulado, falta confirmarlo en producción).
4. **SAMAI (contencioso administrativo) no detecta actuaciones nuevas**, solo cambios de datos generales, aunque la landing lo promete.
5. **Los avisos de etapas a 3, 1 y 0 días no se envían en la práctica** (comparación de decimales con enteros).
6. **Dos actuaciones con el mismo título el mismo día se guardan como una.**
7. **Vigilancia de clientes «por cédula o NIT» busca por nombre** y acepta homónimos; 25 clientes por noche.
8. **IA:** oculta nombre, cédula y contacto del cliente y la contraparte, pero envía al proveedor el radicado (con el que cualquiera identifica a las partes), despacho, juez, nombres de terceros y variantes del nombre; si el proveedor preferido falla cae a uno que puede entrenar con los datos.
9. **Escala:** el cómputo no limita (23 ms por caso); la red sí. Con la configuración documentada en el repositorio, entre 6 y 74 procesos por noche; con cola y tarea cada minuto, entre 900 y 2.100.
10. **`setup.php`:** clave en la URL, sin límite de intentos; por GET permite nombrar superadmin y `git reset --hard`. El token del portal del cliente se envía a Google Analytics.
11. **Promesas de la landing no respaldadas:** «sin errores de conteo», «secreto profesional garantizado», «te recuerda dos días antes» (es uno), «documentos centralizados» (no hay carga de archivos al caso), «21 flujos» (no se cargan en producción).

Esfuerzo estimado para corregir 1 a 6 y 10: entre 15 y 23 días de trabajo.

## Competencia (dos lecturas independientes por buscador; nadie abrió los sitios directamente)

- Al menos 17 proveedores. Precio para unos 50 procesos: mínimo $49.900 (NeoJurídico), mediana $70.200 (Monolegal), máximo $240.000 (Redelex, más $1.500.000 de activación). 11 datos.
- **LegalWeb va por detrás** en: actuaciones de SAMAI (al menos seis competidores dicen detectarlas), entrega del auto en PDF (cuatro), WhatsApp (cinco; LegalWeb solo correo), frecuencia (uno revisa 7 veces al día) y plan gratuito (uno regala 10 procesos).
- **LegalWeb va por delante** en precio de suite: es la suite completa más barata encontrada ($120.000 contra unos $229.000 más $250.000 iniciales, o $240.000 más activación).
- **Nadie garantiza no omitir una actuación** ni envía un informe de verificación. Un competidor anuncia 99,8 % con auditorías diarias sin compensación; otro publica el estado de los portales a 90 días.
- Casi no hay reseñas públicas de nadie.

## Mercado y reglas (fuentes públicas por buscador)

- 425.016 abogados con tarjeta vigente en 2025 (prensa citando a la Corporación Excelencia en la Justicia). **Sin dato** de cuántos litigan. Mercado pagador estimado con supuestos: entre 3.800 y 26.000 abogados.
- **Los términos de uso de la Rama Judicial dicen que no se pueden ejecutar consultas automatizadas** en la consulta de procesos (extracto parcial, texto completo no leído). Todos los competidores comparten ese riesgo.
- No se encontró ningún servicio oficial de alertas al abogado. La Rama está migrando despachos a un sistema nuevo (SIUGJ, 50 % de los despachos).
- Benchmarks de SaaS (de proveedores): prueba gratis sin tarjeta → pago, 4–6 % bueno y 10–15 % excelente; plan gratuito → pago, 3–5 % bueno; baja mensual en pymes 3–7 %.

## Panel de compradores simulados (100 por oferta, mismas tarjetas; modelos de lenguaje tienden a ser complacientes: es un techo)

| | Oferta publicada hoy | Oferta propuesta «Vigilancia en Paralelo» | Igual, Individual a $69.900 y 80 procesos |
| --- | :-: | :-: | :-: |
| Compran | 14 % | 30 % | 30 % |
| Litigante independiente (50) | 10 % | 30 % | 32 % |
| Socio de firma pequeña (30) | 30 % | 50 % | 47 % |
| Menos de 15 procesos (20) | 0 % | 0 % | 0 % |
| Ya tiene dependiente (15) / ya paga vigilancia (15) / desconfía (12) / busca lo gratis (8) | 0 % | 0 % | 0 % |
| Revisa a mano (25) | 8 % | 48 % | 52 % |
| Desordenado (15) | 47 % | 67 % | 60 % |
| Adopta tecnología pronto (10) | 50 % | 80 % | 80 % |
| Pasan por hábito / confianza / precio | 36 / 29 / 20 | 45 / 15 / 8 | 47 / 14 / 7 |

La oferta propuesta: 30 días gratis corriendo al lado del método actual del abogado; aviso por WhatsApp y correo; informe de verificación cada lunes; aviso el mismo día si una consulta falla; devolución del mes si la fuente publicó algo y no se avisó en 24 horas; plan Individual $59.900 (1 usuario, 50 procesos) y Profesional $120.000; plan gratuito de 3 procesos; se declara quién responde. **Todavía no existe en el producto.** Datos del panel: todos los que compran condicionan el pago a que la prueba no omita nada; 63 de 100 dicen que devolver el mes no compensa un término vencido; 29 mencionan el tope de 50 procesos. Rango de precio aceptable: de unos $30.000 a $90.000–$120.000; punto de indiferencia $60.000–$70.000.

## Números (casi todo estimado)

Contribución por suscripción cercana al 90 %. Costos fijos de caja estimados $842.000 al mes; $4.342.000 con sueldo parcial del fundador. Equilibrio: unos 10–12 suscriptores sin sueldo, 50–60 con sueldo. Simulación de 5.000 corridas (conversión 4–15 %, baja 3–7 %, ingreso medio $59.900–$120.000): con 20 registros al mes, mediana de 16 clientes de pago al mes 12 y 0 % de probabilidad de cubrir el sueldo; con 40, 9 % al mes 12 y 45 % al mes 24; con 80, 62 % y 89 %; con 150, 94 % y 100 %. No hay cobro recurrente: cada periodo exige un pago manual.

## Lo que NO se sabe (no lo inventes)

Registros mensuales reales, clientes de pago, churn, costo de adquisición, costos reales, cuántos abogados litigan, configuración real del servidor de producción.
