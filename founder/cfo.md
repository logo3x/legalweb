# El CFO: LegalWeb

> Nota de lectura: la herramienta habla de «suscripcion-mes a day». Aquí se configuró un «día» por mes, así que **«a day» significa suscriptores de pago activos en ese mes**. Todo está en pesos colombianos (COP), antes de IVA.

## La nota del CFO

- **El margen.** Cada suscripción deja **$71.950 al mes** (90 %) después de sus propios costos, con un ingreso medio de $79.900 (la mezcla que dio el panel: 4 planes Individual de $59.900 y 2 Profesional de $120.000). El margen por unidad no es el problema; el problema es el volumen.
- **La línea que hay que mirar: el sueldo del fundador y la rampa.** Con un sueldo parcial de $3.500.000, el punto de equilibrio son **61 suscriptores de pago** y el año 1 pierde **$26.273.950** con la rampa supuesta (de 3 a 60 suscriptores). Sin sueldo (solo costos de caja, `numbers-caja.json`) el equilibrio baja a **12 suscriptores**, el año 1 deja **$15.726.050** y la inversión inicial se recupera en el mes 9. Es decir: como proyecto que el fundador sostiene con su tiempo, sobrevive con una docena de clientes; como negocio que paga a quien lo opera, necesita más de 60.
- **Caja.** Se necesitan **$30.773.950** antes de que se pague solo si el fundador cobra; **$5.658.950** si no cobra. Si además el volumen sale la mitad de lo supuesto, sin sueldo todavía cierra el año en positivo ($2.811.025).
- **Tres formas de mejorar el margen (cada una corrida con la herramienta):**
  1. Subir el ingreso medio a $120.000 (más clientes en Profesional): el equilibrio baja de 61 a **39** suscriptores y la pérdida del año 1 cae a $11.878.050.
  2. No bajar todo a $59.900: con ese ingreso medio el equilibrio sube a **84** suscriptores y el año 1 pierde $33.453.950. El plan Individual sirve para entrar, no para vivir de él.
  3. Recortar el costo fijo que más pesa después del sueldo, el mercadeo ($600.000 estimado), solo cuando haya un canal con costo de adquisición medido; hoy es el 71 % de los costos de caja.
- **Condiciones de dinero de la junta.** Garantía asequible: sí, reservada al 2 % del precio ($1.600 por suscripción), siempre que la tasa real de reclamos se mida. IA de pago e infraestructura propia: caben ($130.000 al mes estimados). Canal con costo de adquisición por debajo de $600.000: **sin dato todavía**.
- **Lo que este CFO no sabe y el fundador sí:** cuántas firmas pagan hoy, el costo real del hosting, la tarifa negociada con Wompi y si hay otros ingresos. Casi todas las cifras de `numbers.json` son estimaciones marcadas como tales; reemplácelas por las reales y vuelva a correr la herramienta.

Un contador debe revisar la estructura, el IVA sobre el servicio y la facturación electrónica antes de mover dinero. Esto no es asesoría financiera, tributaria ni legal.

## Salida de la herramienta (escenario base, con sueldo parcial)

## One suscripcion-mes

| line | per suscripcion-mes |
| --- | ---: |
| Price | $79,900.00 |
| Comision Wompi (2,65% + $700 + IVA, tarifa publica por confirmar) | -$3,350.00 |
| Alertas por WhatsApp y correo por suscriptor (estimado) | -$3,000.00 |
| Reserva para la garantia de devolucion (2% del precio, estimado) | -$1,600.00 |
| **Contribution** (what each suscripcion-mes leaves to pay the fixed costs) | **$71,950.00** (90%) |

## The margin that matters

Fixed costs: $4,342,000 a month (Hosting compartido actual (estimado) $45,000, Dominio y correo (estimado) $7,000, Servidor propio para la vigilancia nocturna con monitoreo (estimado) $50,000, IA de pago en vez de niveles gratuitos (estimado) $80,000, Envio de correo transaccional (estimado) $60,000, Mercadeo: pauta y contenido (estimado) $600,000, Sueldo parcial del fundador (estimado, medio tiempo) $3,500,000).

- **Break-even: 61 suscripcion-mess a day.** Below that you lose money every month.
- **Profit margin at your plan** (60 a day): **-1%** of every sale, after every cost.

## Year 1, month by month

| month | suscripcion-mess a day | revenue | profit | cumulative (after $4,500,000 startup) |
| ---: | ---: | ---: | ---: | ---: |
| 1 | 3 | $239,700 | -$4,126,150 | -$8,626,150 |
| 2 | 6 | $479,400 | -$3,910,300 | -$12,536,450 |
| 3 | 10 | $799,000 | -$3,622,500 | -$16,158,950 |
| 4 | 15 | $1,198,500 | -$3,262,750 | -$19,421,700 |
| 5 | 20 | $1,598,000 | -$2,903,000 | -$22,324,700 |
| 6 | 26 | $2,077,400 | -$2,471,300 | -$24,796,000 |
| 7 | 32 | $2,556,800 | -$2,039,600 | -$26,835,600 |
| 8 | 38 | $3,036,200 | -$1,607,900 | -$28,443,500 |
| 9 | 44 | $3,515,600 | -$1,176,200 | -$29,619,700 |
| 10 | 50 | $3,995,000 | -$744,500 | -$30,364,200 |
| 11 | 55 | $4,394,500 | -$384,750 | -$30,748,950 |
| 12 | 60 | $4,794,000 | -$25,000 | -$30,773,950 |

- **Year 1 operating profit: -$26,273,950** on $28,684,100 of revenue.
- After the $4,500,000 startup spend: -$30,773,950.
- Startup money earned back: not within year 1.
- Cash you need before it pays for itself: **$30,773,950**.

## What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | -1% | 61 | -$26,273,950 |
| Price -10% | -12% | 68 | -$29,142,360 |
| Volume -20% | -23% | 61 | -$31,439,960 |
| Unit costs +15% | -2% | 62 | -$26,702,058 |

## Red flags

- Year 1 loses money on operations (-$26,273,950).
- The startup spend is not earned back within year 1.
