# De dónde sale cada número

| Número | Valor | Fuente | Tipo |
| --- | ---: | --- | --- |
| Precio plan Profesional | $120.000/mes | `database/migrations/2026_06_11_200000_unify_all_paid_plans_to_120k.php` y la landing | Dato del producto |
| Precio plan Firma | $250.000/mes | `database/migrations/2026_11_12_105304_add_feature_limits_to_plans.php` | Dato del producto |
| Precio plan Individual | $59.900/mes | Propuesta de `pricing.md` (no existe en el producto) | Propuesta |
| Ingreso medio por suscripción | $79.900 | Mezcla del panel re-probado: 4 Individual + 2 Profesional (`panel/answers`) | Derivado de compradores simulados |
| Comisión Wompi | 2,65 % + $700 + IVA | Tarifa pública recordada, **sin verificar en esta sesión**; confirmar en wompi.com/es/co/planes-tarifas | Estimación |
| Alertas WhatsApp/correo | $3.000 por suscriptor | Sin cotización; Twilio cobra por mensaje | Estimación |
| Reserva de garantía | 2 % del precio | Supuesto de tasa de reclamos; no hay historial | Estimación |
| Hosting, dominio, correo | $45.000, $7.000, $60.000 | Sin factura a la vista | Estimación |
| Servidor propio e IA de pago | $50.000 y $80.000 | Sin cotización | Estimación |
| Mercadeo | $600.000/mes | Supuesto de trabajo | Estimación |
| Sueldo parcial del fundador | $3.500.000/mes | Supuesto de trabajo | Estimación |
| Inversión inicial | $4.500.000 | Supuesto de trabajo | Estimación |
| Rampa de suscriptores (3 → 60) | 12 meses | Supuesto. El panel no lo valida: mide intención, no adquisición | Estimación |
| Salario de un dependiente judicial | $1.500.000 a $2.500.000 | Ofertas de empleo citadas en `competitors.md` | Fuente pública |
