# Operaciones: LegalWeb

Para un SaaS, operar es mantener la promesa cada madrugada. Esta sección convierte la auditoría (`code-audit.md`) en rutina.

## 1. El ciclo diario

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

## 2. Proveedores

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

## 3. Personas

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

## 4. Rutinas

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

## 5. Herramientas

Lo mínimo: el panel propio (ya existe: CRM de firmas, planes, pagos), un servicio de señal de vida, un servicio de envío de correo con registro, integración continua en GitHub Actions (gratis para repositorios públicos; verificar el cupo para privados) y facturación electrónica (la exige la DIAN según el régimen; lo define el contador).

## 6. Licencias, permisos y seguros: qué verificar

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

## 7. Registro de riesgos

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

## 8. Lo que hay que construir, en orden (solo esto; lo demás se congela)

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
