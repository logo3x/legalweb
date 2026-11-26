# Resumen

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
