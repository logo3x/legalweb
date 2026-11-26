#!/usr/bin/env python3
"""Monte Carlo del embudo de LegalWeb. Solo libreria estandar, semilla fija.
Cada supuesto esta declarado abajo; ninguno es un dato del negocio."""
import random, statistics, json
SEED=7; RUNS=5000; MONTHS=24
CONTRIB_FRAC=0.90            # contribucion por suscripcion (cfo.md)
FIXED_CASH=842000            # costos fijos de caja al mes (numbers-caja.json, estimado)
FIXED_PAID=4342000           # con sueldo parcial del fundador (numbers.json, estimado)
# Rangos (uniformes) por corrida:
CONV=(0.04,0.15)             # prueba sin tarjeta -> pago (ChartMogul/OpenView: 4-6% bueno, 10-15% excelente)
CHURN=(0.03,0.07)            # baja mensual PYMES (blogs; sin estudio primario)
ARPU=(59900,120000)          # entre todo Individual y todo Profesional
TRIAL_LAG=1                  # la prueba dura un mes en la oferta propuesta
def run(signups, rng):
    conv=rng.uniform(*CONV); churn=rng.uniform(*CHURN); arpu=rng.uniform(*ARPU)
    payers=0.0; hist=[]; cum_cash=0.0; cum_paid=0.0
    for m in range(1,MONTHS+1):
        new = 0 if m<=TRIAL_LAG else sum(1 for _ in range(signups) if rng.random()<conv)
        lost = sum(1 for _ in range(int(payers)) if rng.random()<churn)
        payers = payers - lost + new
        rev = payers*arpu
        cum_cash += rev*CONTRIB_FRAC - FIXED_CASH
        cum_paid += rev*CONTRIB_FRAC - FIXED_PAID
        hist.append((payers,rev,cum_cash,cum_paid))
    return hist, arpu
def be(fixed, arpu): return fixed/(arpu*CONTRIB_FRAC)
rows=[]
for signups in (10,20,40,80,150):
    rng=random.Random(SEED+signups)
    p12=[];p24=[];mrr12=[];c12=[];pd12=[];hit_cash=0;hit_paid=0;hit_paid24=0
    for _ in range(RUNS):
        h,arpu=run(signups,rng)
        p12.append(h[11][0]); p24.append(h[23][0]); mrr12.append(h[11][1]); c12.append(h[11][2]); pd12.append(h[11][3])
        hit_cash += h[11][0] >= be(FIXED_CASH,arpu)
        hit_paid += h[11][0] >= be(FIXED_PAID,arpu)
        hit_paid24 += h[23][0] >= be(FIXED_PAID,arpu)
    q=lambda v,p: sorted(v)[int(p*len(v))]
    rows.append(dict(registros_mes=signups, pagos_m12_p10=round(q(p12,.1)), pagos_m12_mediana=round(statistics.median(p12)), pagos_m12_p90=round(q(p12,.9)),
        pagos_m24_mediana=round(statistics.median(p24)), mrr_m12_mediana=round(statistics.median(mrr12),-3),
        prob_cubre_caja_m12=round(hit_cash/RUNS,2), prob_cubre_sueldo_m12=round(hit_paid/RUNS,2), prob_cubre_sueldo_m24=round(hit_paid24/RUNS,2),
        caja_acum_m12_mediana=round(statistics.median(c12),-4), acum_con_sueldo_m12_mediana=round(statistics.median(pd12),-4)))
json.dump(rows,open('funnel_mc.json','w'),indent=1)
f=lambda n:"{:,.0f}".format(n).replace(",",".")
print("| Registros al mes | Pagos mes 12 (p10 / mediana / p90) | Pagos mes 24 | Ingreso mensual mes 12 | Cubre caja mes 12 | Cubre sueldo mes 12 | Cubre sueldo mes 24 | Caja acumulada mes 12 (sin sueldo) | Acumulado mes 12 (con sueldo) |")
print("| ---: | :-: | ---: | ---: | :-: | :-: | :-: | ---: | ---: |")
for r in rows:
    print(f"| {r['registros_mes']} | {r['pagos_m12_p10']} / {r['pagos_m12_mediana']} / {r['pagos_m12_p90']} | {r['pagos_m24_mediana']} | ${f(r['mrr_m12_mediana'])} | {int(r['prob_cubre_caja_m12']*100)} % | {int(r['prob_cubre_sueldo_m12']*100)} % | {int(r['prob_cubre_sueldo_m24']*100)} % | ${f(r['caja_acum_m12_mediana'])} | ${f(r['acum_con_sueldo_m12_mediana'])} |")
