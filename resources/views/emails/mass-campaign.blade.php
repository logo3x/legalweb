{{--
    Campañas y boletines. El cuerpo ($body) es HTML de las plantillas de campaña y usa las clases lw-*;
    aqui se les da un estilo sobrio coherente con el resto de correos (sin franjas laterales,
    sin mayusculas espaciadas ni degradados).
    Variables: subject, body, previewText, unsubscribeUrl.
--}}
<x-mail.layout :title="$subject ?? null" :preheader="$previewText ?? null" audience="campaign" :unsubscribe-url="$unsubscribeUrl ?? null">
    <x-slot:styles>
      .lw-mc h2 { font-size: 22px; font-weight: 700; color: #0F172A; margin: 26px 0 12px; letter-spacing: -0.01em; line-height: 1.3; }
      .lw-mc h2:first-child { margin-top: 0; }
      .lw-mc h3 { font-size: 17px; font-weight: 700; color: #0F172A; margin: 22px 0 8px; line-height: 1.35; }
      .lw-mc p { font-size: 16px; line-height: 1.6; color: #334155; margin: 0 0 14px; }
      .lw-mc p.small { font-size: 14px; color: #475569; }
      .lw-mc strong { color: #0F172A; }
      .lw-mc a { color: #1D4ED8; }

      .lw-callout { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 14px 16px; margin: 18px 0; color: #1E3A8A; font-size: 15px; line-height: 1.55; }
      .lw-callout.success { background: #ECFDF5; border-color: #A7F3D0; color: #065F46; }
      .lw-callout.warning { background: #FFFBEB; border-color: #FDE68A; color: #92400E; }
      .lw-callout.danger { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }
      .lw-callout.brand { background: #F8FAFC; border-color: #E2E8F0; color: #0F172A; }
      .lw-callout.brand a { color: #1D4ED8 !important; }

      .lw-check-list, .lw-num-list { margin: 16px 0; padding-left: 22px; }
      .lw-check-list li, .lw-num-list li { padding: 3px 0; font-size: 16px; color: #334155; line-height: 1.55; }

      .lw-stat-row { width: 100%; margin: 18px 0; border-top: 1px solid #E2E8F0; }
      .lw-stat { display: block; padding: 10px 0; border-bottom: 1px solid #E2E8F0; }
      .lw-stat-num { font-size: 16px; font-weight: 700; color: #0F172A; margin-right: 6px; }
      .lw-stat-lbl { font-size: 15px; color: #475569; }

      .lw-divider { border: 0; border-top: 1px solid #E2E8F0; margin: 24px 0; }

      .lw-pill { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; background: #EFF6FF; color: #1E3A8A; }
      .lw-pill.success { background: #ECFDF5; color: #065F46; }
      .lw-pill.warning { background: #FFFBEB; color: #92400E; }
      .lw-pill.danger { background: #FEF2F2; color: #991B1B; }
      .lw-pill.brand { background: #F1F5F9; color: #1E3A5F; }

      .lw-cta-block { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 22px 20px; margin: 22px 0; }
      .lw-cta-block h3 { margin: 0 0 6px; }
      .lw-cta-block p { margin: 0 0 14px; color: #334155; }

      .lw-btn, .lw-btn-primary, .lw-cta-block .lw-btn { display: inline-block; padding: 13px 24px; background: #1E3A5F; color: #FFFFFF !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
      .lw-btn-outline { display: inline-block; padding: 12px 22px; background: #FFFFFF; color: #1E3A5F !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; border: 1px solid #CBD5E1; }

      .lw-card { border-top: 1px solid #E2E8F0; padding: 14px 0; margin: 0; }
      .lw-card-row { display: table; width: 100%; }
      .lw-card-ico { display: table-cell; width: 36px; vertical-align: top; padding-right: 12px; }
      .lw-card-ico div { width: 28px; height: 28px; border-radius: 6px; background: #F1F5F9; color: #1E3A5F; text-align: center; line-height: 28px; font-size: 14px; font-weight: 700; }
      .lw-card-body { display: table-cell; vertical-align: top; }
      .lw-card-body h4 { margin: 0 0 4px; font-size: 16px; font-weight: 700; color: #0F172A; }
      .lw-card-body p { margin: 0; font-size: 15px; color: #475569; line-height: 1.55; }

      @media only screen and (max-width: 600px) {
        .lw-btn, .lw-btn-primary, .lw-btn-outline { display: block !important; text-align: center; }
      }
    </x-slot:styles>

    <div class="lw-mc">
        {!! $body !!}
    </div>
</x-mail.layout>
