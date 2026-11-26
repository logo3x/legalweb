# El CFO: LegalWeb

> Nota de lectura: la herramienta habla de «suscripcion-mes a day». Aquí se configuró un «día» por mes, así que **«a day» significa suscriptores de pago activos en ese mes**. Todo está en pesos colombianos (COP), antes de IVA.

## La nota del CFO

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

## Simulación del embudo (5.000 corridas por fila)

| Registros al mes | Pagos mes 12 (p10 / mediana / p90) | Pagos mes 24 | Ingreso mensual mes 12 | Cubre caja mes 12 | Cubre sueldo mes 12 | Cubre sueldo mes 24 | Caja acumulada mes 12 (sin sueldo) | Acumulado mes 12 (con sueldo) |
| ---: | :-: | ---: | ---: | :-: | :-: | :-: | ---: | ---: |
| 10 | 3 / 8 / 14 | 13 | $689.000 | 27 % | 0 % | 0 % | $-6.090.000 | $-48.090.000 |
| 20 | 8 / 16 / 26 | 26 | $1.400.000 | 77 % | 0 % | 3 % | $-1.860.000 | $-43.860.000 |
| 40 | 17 / 32 / 50 | 52 | $2.811.000 | 98 % | 9 % | 45 % | $6.330.000 | $-35.670.000 |
| 80 | 35 / 65 / 97 | 105 | $5.617.000 | 100 % | 62 % | 89 % | $22.730.000 | $-19.270.000 |
| 150 | 65 / 120 / 181 | 194 | $10.450.000 | 100 % | 94 % | 100 % | $51.060.000 | $9.060.000 |

Supuestos: conversión de prueba a pago entre 4 % y 15 %, baja mensual entre 3 % y 7 %, ingreso medio entre $59.900 y $120.000, un mes de prueba. Código en `sim/funnel_mc.py`.

## Salida de la herramienta (escenario base, con sueldo parcial)

## One suscripcion-mes

| line | per suscripcion-mes |
| --- | ---: |
| Price | $91,950.00 |
| Comision Wompi (2,65% + $700 + IVA sobre la comision) | -$3,730.00 |
| Alertas por WhatsApp y correo por suscriptor (estimado con tarifas publicas) | -$1,500.00 |
| Reserva para la garantia de devolucion (2% del precio, estimado) | -$1,840.00 |
| **Contribution** (what each suscripcion-mes leaves to pay the fixed costs) | **$84,880.00** (92%) |

## The margin that matters

Fixed costs: $4,342,000 a month (Hosting compartido actual (estimado) $45,000, Dominio y correo (estimado) $7,000, Servidor propio para la vigilancia nocturna con monitoreo (estimado) $50,000, IA de pago en vez de niveles gratuitos (estimado) $80,000, Envio de correo transaccional (estimado) $60,000, Mercadeo: pauta y contenido (estimado) $600,000, Sueldo parcial del fundador (estimado, medio tiempo) $3,500,000).

- **Break-even: 52 suscripcion-mess a day.** Below that you lose money every month.
- **Profit margin at your plan** (60 a day): **14%** of every sale, after every cost.

## Year 1, month by month

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

## What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | 14% | 52 | -$21,632,080 |
| Price -10% | 4% | 58 | -$24,933,085 |
| Volume -20% | -6% | 52 | -$27,726,464 |
| Unit costs +15% | 12% | 52 | -$22,012,800 |

## Red flags

- Year 1 loses money on operations (-$21,632,080).
- The startup spend is not earned back within year 1.
