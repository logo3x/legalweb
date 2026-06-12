<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalWeb · El día que tu proceso cambie, lo sabrás antes que nadie</title>
    <meta name="description" content="LegalWeb vigila tus procesos en la Rama Judicial y te notifica por correo cada vez que aparece una nueva actuación. 3 meses gratis sin tarjeta de crédito.">
    <meta property="og:title" content="LegalWeb · Vigilancia judicial automática para abogados">
    <meta property="og:description" content="Importa tu radicado y LegalWeb vigila la Rama Judicial cada noche. Te avisa por correo apenas aparezca una nueva actuación.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://legalweb.com.co/images/og-cover.png">
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
    {{-- Google Analytics --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2Q7KJTB5MT"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-2Q7KJTB5MT');</script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-navy: #1E3A5F;
            --brand-navy-700: #16294a;
            --brand-blue: #3A86FF;
            --brand-blue-700: #1d4ed8;
            --green-50: #ECFDF5;
            --green-100: #D1FAE5;
            --green-500: #10B981;
            --green-600: #059669;
            --green-700: #047857;
            --amber-100: #FEF3C7;
            --amber-500: #F59E0B;
            --amber-600: #D97706;
            --red-100: #FEE2E2;
            --red-500: #EF4444;
            --red-600: #DC2626;
            --surface-page: #F5F7FA;
            --surface-sunken: #F8FAFC;
            --white: #FFFFFF;
            --text-body: #334155;
            --text-muted: #64748B;
            --text-faint: #94A3B8;
            --border-default: #E2E8F0;
            --border-subtle: #EEF2F7;
            --gray-400: #94A3B8;
            --gray-900: #0F172A;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 20px;
            --radius-full: 9999px;
            --shadow-xs: 0 1px 2px rgba(15,23,42,.04);
            --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
            --shadow-md: 0 4px 10px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
            --shadow-lg: 0 10px 28px rgba(15,23,42,.08), 0 4px 10px rgba(15,23,42,.05);
            --shadow-xl: 0 20px 50px rgba(15,23,42,.12), 0 8px 20px rgba(15,23,42,.06);
            --shadow-blue: 0 8px 18px rgba(58,134,255,.25);
            --text-sm: 14px;
            --text-base: 16px;
            --text-lg: 17.5px;
            --text-xl: 20px;
            --text-2xl: 24px;
            --container: 1180px;
            --nav-height: 72px;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-display: 'Poppins', 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', 'Consolas', monospace;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; }
        }
        body {
            margin: 0;
            font-family: var(--font-sans);
            color: var(--text-body);
            background: var(--white);
            -webkit-font-smoothing: antialiased;
        }
        section[id] { scroll-margin-top: 84px; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: var(--container); margin: 0 auto; padding: 0 24px; }

        .lw-gradient-text {
            color: var(--brand-blue);
            font-weight: 800;
        }
        .lw-hero-wash {
            background:
                radial-gradient(ellipse at 70% 30%, rgba(58,134,255,.10), transparent 55%),
                linear-gradient(180deg, #FBFCFE 0%, #F1F5FB 100%);
        }
        .lw-ping {
            animation: lwPing 1.8s cubic-bezier(0,0,.2,1) infinite;
        }
        @keyframes lwPing {
            0% { transform: scale(1); opacity: .65; }
            75%,100% { transform: scale(2.4); opacity: 0; }
        }

        /* Botones */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border-radius: var(--radius-lg); font-weight: 600; font-family: var(--font-sans);
            cursor: pointer; transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
            border: 1px solid transparent; text-decoration: none;
            padding: 12px 22px; font-size: 14.5px;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-lg { padding: 15px 28px; font-size: 16px; }
        .btn-sm { padding: 9px 14px; font-size: 13px; }
        .btn-primary { background: var(--brand-blue); color: #fff; box-shadow: var(--shadow-blue); }
        .btn-primary:hover { background: var(--brand-blue-700); }
        .btn-secondary { background: var(--white); color: var(--brand-navy); border-color: var(--border-default); }
        .btn-secondary:hover { border-color: var(--brand-blue); color: var(--brand-blue); }
        .btn-success { background: var(--green-600); color: #fff; box-shadow: 0 8px 18px rgba(16,185,129,.22); }
        .btn-success:hover { background: var(--green-700); }
        .btn-full { width: 100%; }

        /* Badge */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 700; letter-spacing: .03em;
            padding: 5px 10px; border-radius: var(--radius-full); text-transform: uppercase;
        }
        .badge-danger { background: var(--red-100); color: var(--red-600); }
        .badge-info-solid { background: var(--brand-blue); color: #fff; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: #EFF6FF; color: var(--brand-blue-700);
            font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
            padding: 6px 12px; border-radius: var(--radius-full);
        }
        .eyebrow.green { background: var(--green-50); color: var(--green-700); }

        /* Nav */
        .nav-shell {
            position: sticky; top: 0; z-index: 50;
            background: rgba(255,255,255,.85); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-subtle);
        }
        .nav-shell .row { height: var(--nav-height); display: flex; align-items: center; justify-content: space-between; }
        .nav-shell .links { display: flex; gap: 28px; }
        .nav-shell .links a { font-size: var(--text-sm); font-weight: 500; color: var(--text-body); transition: color .2s; }
        .nav-shell .links a:hover { color: var(--brand-blue); }
        .nav-shell .signin { font-size: var(--text-sm); font-weight: 500; color: var(--brand-navy); }
        .nav-shell .signin:hover { color: var(--brand-blue); }

        /* Hero */
        .hero-grid {
            display: grid; grid-template-columns: 1.05fr .95fr; gap: 56px; align-items: center;
            padding: 72px 0 84px;
        }
        .hero-grid h1 {
            font-family: var(--font-display); font-weight: 800;
            font-size: clamp(2.5rem, 4.5vw, 3.9rem); line-height: 1.05;
            letter-spacing: -0.02em; color: var(--brand-navy); margin: 0 0 20px;
        }
        .hero-grid .lead {
            font-size: var(--text-lg); color: var(--text-muted); line-height: 1.6;
            max-width: 520px; margin: 0 0 30px;
        }
        .hero-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--white); border: 1px solid #DBEAFE;
            border-radius: var(--radius-full); padding: 6px 14px;
            box-shadow: var(--shadow-xs); margin-bottom: 22px;
            font-size: 13px; font-weight: 600; color: var(--brand-navy);
        }

        /* Email mockup */
        .email-mockup {
            background: var(--white); border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl); border: 1px solid var(--border-subtle);
            overflow: hidden; width: 100%; max-width: 420px;
        }
        .email-head {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px; border-bottom: 1px solid var(--border-subtle);
        }
        .email-head .sender-title {
            font-size: 13px; font-weight: 700; color: var(--brand-navy);
        }
        .email-head .sender-mail { font-size: 11px; color: var(--text-faint); }
        .email-body { padding: 20px 18px 22px; }
        .email-body h3 {
            font-family: var(--font-display); font-size: 19px; font-weight: 700;
            color: var(--brand-navy); margin: 12px 0 4px; line-height: 1.25;
        }
        .email-body p.lead-2 { font-size: 13px; color: var(--text-muted); margin: 0 0 16px; }
        .email-rows {
            background: var(--surface-sunken); border-radius: var(--radius-lg);
            padding: 12px 14px; display: grid; gap: 8px; font-size: 12.5px;
        }
        .email-rows .row {
            display: flex; justify-content: space-between; gap: 12px;
        }
        .email-rows .k { color: var(--text-faint); }
        .email-rows .v { color: var(--brand-navy); font-weight: 600; text-align: right; }
        .email-rows .v.mono { font-family: var(--font-mono); font-size: 11.5px; }

        .behind-card {
            position: absolute; top: -26px; right: 4px; width: 240px;
            background: var(--white); border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md); border: 1px solid var(--border-subtle);
            padding: 12px 14px; transform: rotate(3deg); opacity: .96;
        }
        .behind-card .head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .behind-card .ico {
            width: 26px; height: 26px; border-radius: 8px;
            background: var(--green-100); color: var(--green-600);
            display: flex; align-items: center; justify-content: center;
        }
        .behind-card .ttl { font-size: 12px; font-weight: 600; color: var(--brand-navy); }
        .behind-card .desc { font-size: 11px; color: var(--text-muted); }

        /* Sections shared */
        section.std { padding: 88px 0; }
        .section-head { text-align: center; max-width: 680px; margin: 0 auto 52px; }
        .section-head h2 {
            font-family: var(--font-display); font-weight: 700;
            font-size: clamp(1.9rem, 3vw, 2.6rem); line-height: 1.15;
            color: var(--brand-navy); margin: 14px 0 14px;
        }
        .section-head p {
            font-size: var(--text-lg); color: var(--text-muted); line-height: 1.6; margin: 0;
        }

        /* Vigilancia flow */
        .flow-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; position: relative; }
        .flow-line {
            position: absolute; top: 36px; left: 12%; right: 12%; height: 2px;
            background: linear-gradient(to right,
                var(--brand-blue) 0%, var(--green-500) 33%,
                var(--amber-500) 66%, var(--red-500) 100%);
            opacity: .35; z-index: 1;
        }
        .step { position: relative; text-align: center; }
        .step .tile {
            width: 72px; height: 72px; margin: 0 auto 18px;
            border-radius: var(--radius-xl); display: flex;
            align-items: center; justify-content: center; position: relative;
            z-index: 2; border: 4px solid var(--white);
        }
        .step .tile.info { background: #EFF6FF; color: var(--brand-blue); }
        .step .tile.success { background: var(--green-100); color: var(--green-600); }
        .step .tile.warning { background: var(--amber-100); color: var(--amber-600); }
        .step .tile.danger { background: var(--red-100); color: var(--red-500); }
        .step .num {
            position: absolute; top: -8px; right: -8px;
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--brand-navy); color: #fff;
            font-size: 11px; font-weight: 700; display: flex;
            align-items: center; justify-content: center;
            font-family: var(--font-display);
        }
        .step h3 {
            font-family: var(--font-display); font-size: var(--text-lg);
            font-weight: 600; color: var(--brand-navy); margin: 0 0 8px;
        }
        .step p { font-size: var(--text-sm); color: var(--text-muted); line-height: 1.6; margin: 0; }

        .pills-row {
            margin-top: 44px; display: flex; justify-content: center;
            gap: 16px; flex-wrap: wrap; color: var(--text-muted); font-size: var(--text-sm);
        }
        .pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--surface-sunken); border: 1px solid var(--border-subtle);
            border-radius: var(--radius-full); padding: 8px 16px; font-weight: 500;
        }
        .pill svg { color: var(--brand-blue); }

        /* Features */
        .feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .feat-card {
            background: var(--white); border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl); padding: 28px;
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }
        .feat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: #DBEAFE;
        }
        .feat-card .ico {
            width: 48px; height: 48px; border-radius: var(--radius-lg);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 18px; transition: transform .25s ease;
        }
        .feat-card:hover .ico { transform: scale(1.08); }
        .feat-card .ico.info { background: #EFF6FF; color: var(--brand-blue); }
        .feat-card .ico.ai { background: #F3E8FF; color: #7C3AED; }
        .feat-card .ico.warning { background: var(--amber-100); color: var(--amber-600); }
        .feat-card .ico.danger { background: var(--red-100); color: var(--red-500); }
        .feat-card .ico.success { background: var(--green-100); color: var(--green-600); }
        .feat-card h3 {
            font-family: var(--font-display); font-size: var(--text-xl);
            font-weight: 600; color: var(--brand-navy); margin: 0 0 8px;
        }
        .feat-card p { font-size: var(--text-sm); color: var(--text-muted); line-height: 1.6; margin: 0; }

        /* Preview */
        .preview-tabs { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 28px; }
        .preview-tab {
            padding: 9px 18px; border-radius: var(--radius-md);
            font-size: var(--text-sm); font-weight: 600; cursor: pointer;
            font-family: var(--font-sans); transition: all .2s;
            border: 1px solid var(--border-default); background: var(--white); color: var(--text-body);
        }
        .preview-tab.active {
            background: var(--brand-blue); color: #fff;
            border-color: transparent; box-shadow: var(--shadow-blue);
        }
        .browser-frame {
            background: var(--gray-900); border-radius: var(--radius-2xl);
            padding: 10px; box-shadow: var(--shadow-xl);
        }
        .browser-frame .bar { display: flex; align-items: center; gap: 7px; padding: 8px 12px; }
        .browser-frame .dot { width: 11px; height: 11px; border-radius: 50%; }
        .browser-frame .dot:nth-child(1) { background: #FF5F57; }
        .browser-frame .dot:nth-child(2) { background: #FEBC2E; }
        .browser-frame .dot:nth-child(3) { background: #28C840; }
        .browser-frame .url {
            margin-left: 10px; font-size: 12px; color: var(--gray-400);
            font-family: var(--font-mono);
        }
        .browser-frame img { width: 100%; border-radius: var(--radius-lg); display: block; }
        .preview-desc {
            text-align: center; color: var(--text-muted);
            max-width: 560px; margin: 22px auto 0; font-size: var(--text-sm);
        }
        [x-cloak] { display: none !important; }

        /* Pricing */
        .plan-grid {
            display: flex; flex-wrap: wrap; gap: 24px;
            justify-content: center; align-items: stretch; margin-bottom: 40px;
        }
        .plan { position: relative; flex: 1 1 320px; max-width: 380px; }
        .plan .recommended {
            position: absolute; top: -13px; left: 50%; transform: translateX(-50%); z-index: 3;
        }
        .plan-card {
            background: var(--white); border-radius: var(--radius-2xl); padding: 32px;
            border: 1px solid var(--border-default); box-shadow: var(--shadow-sm);
            height: 100%; display: flex; flex-direction: column;
        }
        .plan-card.featured { border: 2px solid var(--brand-blue); box-shadow: var(--shadow-lg); }
        .plan-card .price {
            font-family: var(--font-display); font-size: 3rem;
            font-weight: 800; line-height: 1;
        }
        .plan-card .price.free { color: var(--green-600); }
        .plan-card .price.paid { color: var(--brand-blue); }
        .plan-card .price-suffix { font-size: var(--text-base); color: var(--text-muted); font-weight: 600; }
        .incluye-panel {
            background: var(--white); border: 1px solid var(--border-default);
            border-radius: var(--radius-2xl); padding: 32px; box-shadow: var(--shadow-sm);
        }
        .incluye-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 32px;
        }
        .incluye-grid li {
            display: flex; align-items: flex-start; gap: 10px;
            font-size: var(--text-sm); color: var(--text-body);
        }
        .incluye-grid svg { color: var(--green-600); flex-shrink: 0; margin-top: 2px; }

        /* Trust */
        .trust-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; }
        .trust-grid .item { text-align: center; }
        .trust-grid .k {
            font-family: var(--font-display); font-size: var(--text-2xl);
            font-weight: 800; color: var(--brand-navy); margin-bottom: 6px;
        }
        .trust-grid .d { font-size: var(--text-sm); color: var(--text-muted); line-height: 1.5; }

        /* Final CTA */
        .close-cta {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, var(--brand-navy), var(--brand-navy-700));
        }
        .close-cta::before {
            content: ''; position: absolute; top: -120px; left: 50%; transform: translateX(-50%);
            width: 460px; height: 460px; border-radius: 50%;
            background: radial-gradient(circle, rgba(58,134,255,.28), transparent 65%);
            pointer-events: none;
        }
        .close-cta .inner {
            max-width: 640px; margin: 0 auto; text-align: center; position: relative;
        }
        .close-cta h2 {
            font-family: var(--font-display); font-weight: 700;
            font-size: clamp(1.9rem, 3vw, 2.6rem); color: #fff; margin: 0 0 16px;
        }
        .close-cta p { font-size: var(--text-lg); color: rgba(255,255,255,.72); line-height: 1.6; margin: 0 0 30px; }

        /* Footer */
        footer.site {
            background: var(--white); border-top: 1px solid var(--border-subtle);
            padding: 56px 0 32px;
        }
        .foot-grid {
            display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px;
        }
        .foot-grid h4 {
            font-size: var(--text-sm); font-weight: 700;
            color: var(--brand-navy); margin: 0 0 14px;
        }
        .foot-grid ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
        .foot-grid a { font-size: var(--text-sm); color: var(--text-muted); transition: color .2s; }
        .foot-grid a:hover { color: var(--brand-blue); }
        .foot-bottom {
            border-top: 1px solid var(--border-subtle); padding-top: 24px;
            display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px;
            font-size: 13px; color: var(--text-faint);
        }

        /* Responsive */
        @media (max-width: 980px) {
            .hero-grid { grid-template-columns: 1fr; gap: 36px; padding: 56px 0 64px; }
            .hero-grid .visual { display: flex; justify-content: center; }
            .behind-card { display: none; }
            .flow-grid { grid-template-columns: repeat(2, 1fr); gap: 36px; }
            .flow-line { display: none; }
            .feat-grid { grid-template-columns: repeat(2, 1fr); }
            .trust-grid { grid-template-columns: 1fr; }
            .foot-grid { grid-template-columns: 1fr 1fr; }
            .nav-shell .links { display: none; }
            .incluye-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .flow-grid { grid-template-columns: 1fr; }
            .feat-grid { grid-template-columns: 1fr; }
            .foot-grid { grid-template-columns: 1fr; }
            section.std { padding: 64px 0; }
        }

        /* Scroll to top */
        .scroll-top {
            position: fixed; bottom: 24px; right: 24px;
            width: 44px; height: 44px; border-radius: 50%;
            background: var(--brand-blue); color: #fff;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: translateY(12px);
            transition: opacity .25s ease, transform .25s ease;
            box-shadow: var(--shadow-blue); cursor: pointer; border: none; z-index: 60;
        }
        .scroll-top.visible { opacity: 1; transform: translateY(0); }
        .scroll-top:hover { background: var(--brand-blue-700); }

        /* ==========================================================
           MOTION (impeccable: ease-out-expo, materials reales,
           reveal sobre default visible, reducedMotion respetado)
           ========================================================== */

        /* Curve premium */
        :root {
            --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-out-quart: cubic-bezier(0.25, 1, 0.5, 1);
        }

        /* Hero intro: ya visible, sube + fade muy sutil */
        .lw-hero-pill { animation: lwFadeUp .9s var(--ease-out-expo) both; }
        .lw-hero-h1   { animation: lwFadeUp .9s .08s var(--ease-out-expo) both; }
        .lw-hero-lead { animation: lwFadeUp .9s .16s var(--ease-out-expo) both; }
        .lw-hero-cta  { animation: lwFadeUp .9s .24s var(--ease-out-expo) both; }
        .lw-hero-trust { animation: lwFadeUp .9s .32s var(--ease-out-expo) both; }
        .lw-hero-visual { animation: lwFadeUpScale 1.1s .25s var(--ease-out-expo) both; }
        .lw-hero-badge { animation: lwFloatIn 1.1s .55s var(--ease-out-expo) both; }

        @keyframes lwFadeUp {
            from { opacity: 0; transform: translate3d(0, 14px, 0); }
            to   { opacity: 1; transform: translate3d(0, 0, 0); }
        }
        @keyframes lwFadeUpScale {
            from { opacity: 0; transform: translate3d(0, 18px, 0) scale(.985); }
            to   { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
        }
        @keyframes lwFloatIn {
            from { opacity: 0; transform: translate3d(0, -10px, 0) rotate(2deg); }
            to   { opacity: .96; transform: translate3d(0, 0, 0) rotate(3deg); }
        }

        /* Reveal on scroll: default visible, IntersectionObserver le da
           el "from" via .lw-reveal y lo libera con .is-in. Si JS no corre,
           el contenido queda visible (skill rule: nunca gate visibility) */
        .lw-reveal { transition: opacity .75s var(--ease-out-expo), transform .9s var(--ease-out-expo); }
        .lw-reveal.before { opacity: 0; transform: translate3d(0, 24px, 0); }
        .lw-reveal.before.is-in { opacity: 1; transform: translate3d(0, 0, 0); }

        /* Stagger: el padre marca, cada hijo recibe delay creciente */
        .lw-stagger > * { transition-delay: 0ms; }
        .lw-stagger.is-in > *:nth-child(1) { transition-delay: 0ms; }
        .lw-stagger.is-in > *:nth-child(2) { transition-delay: 80ms; }
        .lw-stagger.is-in > *:nth-child(3) { transition-delay: 160ms; }
        .lw-stagger.is-in > *:nth-child(4) { transition-delay: 240ms; }
        .lw-stagger.is-in > *:nth-child(5) { transition-delay: 320ms; }
        .lw-stagger.is-in > *:nth-child(6) { transition-delay: 400ms; }

        /* Plan card featured: shimmer sutil de borde + glow al hover */
        .plan-card.featured {
            position: relative;
            transition: transform .35s var(--ease-out-expo), box-shadow .35s var(--ease-out-expo);
        }
        .plan-card.featured::after {
            content: ''; position: absolute; inset: -2px;
            border-radius: inherit; pointer-events: none;
            background: linear-gradient(135deg, rgba(58,134,255,.0) 30%, rgba(58,134,255,.45) 50%, rgba(58,134,255,.0) 70%);
            background-size: 250% 250%;
            mask: linear-gradient(#000, #000) content-box, linear-gradient(#000, #000);
            mask-composite: exclude;
            -webkit-mask: linear-gradient(#000, #000) content-box, linear-gradient(#000, #000);
            -webkit-mask-composite: xor;
            padding: 2px;
            opacity: .8;
            animation: lwShimmer 3.6s linear infinite;
        }
        @keyframes lwShimmer {
            0% { background-position: 200% 50%; }
            100% { background-position: -100% 50%; }
        }
        .plan-card.featured:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 48px rgba(58,134,255,.28), 0 4px 12px rgba(58,134,255,.14);
        }

        /* Feat cards: hover ya existe, refinamos curve y agregamos icon spring */
        .feat-card { transition: transform .35s var(--ease-out-expo), box-shadow .35s var(--ease-out-expo), border-color .25s ease; }
        .feat-card:hover { transform: translateY(-4px); }
        .feat-card .ico { transition: transform .4s var(--ease-out-expo); }
        .feat-card:hover .ico { transform: scale(1.1) rotate(-3deg); }

        /* Flow steps: tile pulse on hover */
        .step .tile { transition: transform .35s var(--ease-out-expo), box-shadow .35s var(--ease-out-expo); }
        .step:hover .tile { transform: scale(1.06); box-shadow: 0 8px 20px rgba(15,23,42,.10); }

        /* Buttons premium */
        .btn { transition: transform .2s var(--ease-out-quart), box-shadow .25s var(--ease-out-quart), background .2s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(58,134,255,.35); }
        .btn-primary:active { transform: translateY(0); transition-duration: .1s; }

        /* Nav: hide-on-scroll-down, show-on-scroll-up */
        .nav-shell { transition: transform .35s var(--ease-out-expo), background .25s ease; }
        .nav-shell.is-hidden { transform: translateY(-100%); }

        /* Email mockup parallax slot */
        .lw-parallax { will-change: transform; }

        @media (prefers-reduced-motion: reduce) {
            .lw-hero-pill, .lw-hero-h1, .lw-hero-lead, .lw-hero-cta, .lw-hero-trust,
            .lw-hero-visual, .lw-hero-badge { animation: none !important; }
            .lw-reveal.before { opacity: 1 !important; transform: none !important; }
            .plan-card.featured::after { animation: none !important; }
            .lw-parallax { transform: none !important; }
        }
    </style>
</head>
<body>

    {{-- Sticky Navbar --}}
    <nav class="nav-shell" id="main-nav">
        <div class="container row">
            <a href="/" aria-label="LegalWeb">
                <img src="/images/logo.svg" alt="LegalWeb" style="height: 38px;">
            </a>
            <div class="links">
                <a href="#vigilancia">Vigilancia judicial</a>
                <a href="#funcionalidades">Funcionalidades</a>
                <a href="#vista-previa">La plataforma</a>
                <a href="#planes">Planes</a>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="/admin/login" class="signin" style="display: none;" class="lw-hide-sm">Iniciar sesión</a>
                <a href="{{ route('auth.google') }}" class="btn btn-primary btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white" fill-opacity=".85"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity=".95"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity=".75"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity=".85"/></svg>
                    Comenzar gratis
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="lw-hero-wash" style="border-bottom: 1px solid var(--border-subtle);">
        <div class="container hero-grid">
            <div>
                <div class="hero-pill lw-hero-pill">
                    <span style="position: relative; display: inline-flex; width: 8px; height: 8px;">
                        <span class="lw-ping" style="position: absolute; inset: 0; border-radius: 50%; background: var(--green-500);"></span>
                        <span style="position: relative; width: 8px; height: 8px; border-radius: 50%; background: var(--green-500);"></span>
                    </span>
                    Conectado con la Rama Judicial de Colombia
                </div>
                <h1 class="lw-hero-h1">
                    El día que tu proceso<br>
                    cambie, <span class="lw-gradient-text">lo sabrás</span><br>
                    antes que nadie.
                </h1>
                <p class="lead lw-hero-lead">
                    LegalWeb vigila tus procesos en la Rama Judicial y, cada vez que aparece una nueva actuación,
                    te lo notifica por correo. Sin entrar a consultar uno por uno. Sin perder un término.
                </p>
                <div class="lw-hero-cta" style="display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 18px;">
                    <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white" fill-opacity=".85"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity=".95"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity=".75"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity=".85"/></svg>
                        Comenzar con Google — Gratis
                    </a>
                    <a href="#vigilancia" class="btn btn-secondary btn-lg">
                        Ver cómo funciona
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                </div>
                <p class="lw-hero-trust" style="font-size: 13px; color: var(--text-faint); display: flex; align-items: center; gap: 8px; margin: 0;">
                    <svg width="15" height="15" fill="none" stroke="var(--green-600)" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    3 meses gratis · sin tarjeta · configúralo en 60 segundos
                </p>
            </div>

            <div class="visual lw-hero-visual lw-parallax" style="position: relative;">
                <div style="position: absolute; inset: -10% -6%; background: radial-gradient(circle at 70% 30%, rgba(58,134,255,.14), transparent 60%); pointer-events: none;"></div>
                <div class="behind-card lw-hero-badge">
                    <div class="head">
                        <span class="ico">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                        <span class="ttl">Sincronización 3:00 a.m.</span>
                    </div>
                    <div class="desc">Revisando 24 procesos en la Rama Judicial…</div>
                </div>

                <div style="position: relative; z-index: 2; padding-top: 30px;">
                    <div class="email-mockup">
                        <div class="email-head">
                            <img src="/images/logo-icon.svg" alt="" style="width: 30px; height: 30px;">
                            <div style="flex: 1;">
                                <div class="sender-title">LegalWeb · Vigilancia judicial</div>
                                <div class="sender-mail">notificaciones@legalweb.com.co</div>
                            </div>
                            <div style="font-size: 11px; color: var(--text-faint);">3:02 a.m.</div>
                        </div>
                        <div class="email-body">
                            <span class="badge badge-danger">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                Nueva actuación detectada
                            </span>
                            <h3>Auto admite la demanda</h3>
                            <p class="lead-2">Apareció una nueva actuación en uno de tus procesos vigilados.</p>
                            <div class="email-rows">
                                <div class="row"><span class="k">Radicado</span><span class="v mono">68081 31 03 001 2024 00180 00</span></div>
                                <div class="row"><span class="k">Despacho</span><span class="v">Juzgado 1º Civil del Circuito</span></div>
                                <div class="row"><span class="k">Fecha</span><span class="v">14 de marzo de 2026</span></div>
                                <div class="row"><span class="k">Cliente</span><span class="v">Construcciones Andinas S.A.S.</span></div>
                            </div>
                            <a href="{{ route('auth.google') }}" class="btn btn-primary btn-full btn-sm" style="margin-top: 16px;">
                                Ver actuación en LegalWeb
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Vigilancia flow --}}
    <section id="vigilancia" class="std" style="background: var(--white);">
        <div class="container">
            <div class="section-head lw-reveal before">
                <span class="eyebrow green">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: var(--green-500);"></span>
                    Vigilancia judicial automática
                </span>
                <h2>Nunca más entres a consultar proceso por proceso</h2>
                <p>La función que más le importa a un abogado: enterarte de cada movimiento de la Rama Judicial sin levantar un dedo. Así funciona.</p>
            </div>
            <div class="flow-grid lw-reveal before lw-stagger">
                <div class="flow-line"></div>

                <div class="step">
                    <div class="tile info">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span class="num">01</span>
                    </div>
                    <h3>Importas el radicado</h3>
                    <p>Pega el número de radicado y LegalWeb trae todo el expediente desde la Rama Judicial: despacho, juez, sujetos y actuaciones.</p>
                </div>

                <div class="step">
                    <div class="tile success">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span class="num">02</span>
                    </div>
                    <h3>Vigilancia diaria</h3>
                    <p>Cada día a las 3:00 a.m. el sistema vuelve a consultar la Rama Judicial por ti, de forma automática.</p>
                </div>

                <div class="step">
                    <div class="tile warning">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="num">03</span>
                    </div>
                    <h3>Detecta la novedad</h3>
                    <p>Compara y encuentra cualquier actuación nueva en cualquiera de tus procesos vigilados.</p>
                </div>

                <div class="step">
                    <div class="tile danger">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="num">04</span>
                    </div>
                    <h3>Te avisa por correo</h3>
                    <p>Recibes un correo con la actuación, el despacho y la fecha. Entras directo al caso con un click.</p>
                </div>
            </div>

            <div class="pills-row">
                <span class="pill">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Plazos en días hábiles (calendario judicial)
                </span>
                <span class="pill">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    21 flujos procesales: CGP · CPT · Ley 906 · CPACA
                </span>
                <span class="pill">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Importación masiva: hasta 20 radicados
                </span>
            </div>
        </div>
    </section>

    {{-- Funcionalidades --}}
    <section id="funcionalidades" class="std" style="background: var(--surface-page);">
        <div class="container">
            <div class="section-head lw-reveal before">
                <span class="eyebrow">Todo en un solo lugar</span>
                <h2>Tu práctica legal, ordenada de principio a fin</h2>
                <p>Herramientas pensadas por y para abogados colombianos.</p>
            </div>
            <div class="feat-grid lw-reveal before lw-stagger">
                <div class="feat-card">
                    <div class="ico info">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3>Expediente digital</h3>
                    <p>Documentos, actuaciones y evidencias centralizados. Línea de tiempo del proceso y flujo procesal integrado.</p>
                </div>
                <div class="feat-card">
                    <div class="ico ai">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <h3>Asistente IA jurídico</h3>
                    <p>Resumen del caso, siguiente paso recomendado y borradores de demandas, tutelas y memoriales. Siempre orientativos.</p>
                </div>
                <div class="feat-card">
                    <div class="ico warning">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3>Calculadora de términos</h3>
                    <p>Calcula vencimientos en días hábiles según el calendario judicial colombiano. Sin errores de conteo.</p>
                </div>
                <div class="feat-card">
                    <div class="ico ai">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3>Portal del cliente</h3>
                    <p>Tus clientes consultan el estado de su proceso en tiempo real, con un enlace seguro y sin crear cuenta.</p>
                </div>
                <div class="feat-card">
                    <div class="ico danger">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3>Reportes y analítica</h3>
                    <p>KPIs por estado, tipo y abogado. Reporte PDF mensual que se envía automáticamente al cliente.</p>
                </div>
                <div class="feat-card">
                    <div class="ico success">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h3>Facturación por caso</h3>
                    <p>Registra horas, gastos y conceptos. Controla lo facturable y genera cuentas de cobro.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Preview --}}
    <section id="vista-previa" class="std" style="background: var(--white);" x-data="{
        active: 0,
        tabs: [
            { name: 'Escritorio', img: '/images/screenshots/escritorio.png', desc: 'Vista general con KPIs: casos activos, clientes, actuaciones recientes y alertas de vencimiento.' },
            { name: 'Detalle del caso', img: '/images/screenshots/detalle-caso.png', desc: 'Datos del proceso y de la Rama Judicial, partes, despacho, juez, actuaciones y accesos al portal.' },
            { name: 'Agenda', img: '/images/screenshots/agenda.png', desc: 'Recordatorios y vencimientos con prioridad y alertas automáticas en días hábiles.' },
            { name: 'Reportes', img: '/images/screenshots/reportes.png', desc: 'Analítica de despachos, tipos de actuaciones y actividad mensual de tu práctica.' },
            { name: 'Flujo de proceso', img: '/images/screenshots/flujo-proceso.png', desc: '21 flujos precargados según la legislación colombiana (CGP, CPT, Ley 906, CPACA), totalmente editables.' },
            { name: 'Equipo de trabajo', img: '/images/screenshots/equipo.png', desc: 'Invita colaboradores y asigna casos con permisos individuales; tú mantienes el acceso total.' }
        ]
    }">
        <div class="container">
            <div class="section-head lw-reveal before">
                <span class="eyebrow">La plataforma por dentro</span>
                <h2>Así se ve LegalWeb cada mañana</h2>
                <p>Datos reales importados desde la Rama Judicial, en una interfaz hecha para trabajar rápido.</p>
            </div>
            <div class="preview-tabs">
                <template x-for="(tab, index) in tabs" :key="'tab-'+index">
                    <button type="button"
                        @click="active = index"
                        :class="{'preview-tab': true, 'active': active === index}"
                        x-text="tab.name"></button>
                </template>
            </div>
            <div class="browser-frame">
                <div class="bar">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                    <span class="url">legalweb.com.co/admin</span>
                </div>
                <template x-for="(tab, index) in tabs" :key="'img-'+index">
                    <img x-cloak x-show="active === index"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        :src="tab.img"
                        :alt="tab.name"
                        loading="lazy">
                </template>
            </div>
            <template x-for="(tab, index) in tabs" :key="'desc-'+index">
                <p x-cloak x-show="active === index" x-transition class="preview-desc" x-text="tab.desc"></p>
            </template>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="planes" class="std" style="background: var(--surface-page);">
        <div class="container" style="max-width: 980px;">
            <div class="section-head lw-reveal before">
                <h2>Un precio. Todo incluido.</h2>
                <p>Prueba LegalWeb completo y gratis por 3 meses. Después, una sola suscripción de <strong>$120.000</strong> COP/mes — sin niveles, sin límites de casos, sin sorpresas.</p>
            </div>

            <div class="plan-grid lw-reveal before lw-stagger">
                <div class="plan">
                    <div class="plan-card">
                        <div class="eyebrow green" style="align-self: flex-start; margin-bottom: 16px;">Prueba gratuita</div>
                        <h3 style="font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 700; color: var(--brand-navy); margin: 0 0 6px;">Empieza gratis</h3>
                        <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0 0 22px; line-height: 1.5;">Todo el producto, sin límites, durante 3 meses.</p>
                        <div style="margin-bottom: 22px; min-height: 70px;">
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <span class="price free">$0</span>
                                <span class="price-suffix">/ 3 meses</span>
                            </div>
                            <div style="font-size: var(--text-sm); color: var(--text-faint); margin-top: 8px;">Sin tarjeta de crédito</div>
                        </div>
                        <a href="{{ route('auth.google') }}" class="btn btn-success btn-full" style="margin-top: auto;">
                            <svg width="16" height="16" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white" fill-opacity=".85"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity=".95"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity=".75"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity=".85"/></svg>
                            Comenzar gratis
                        </a>
                        <div style="font-size: 13px; color: var(--text-faint); text-align: center; margin-top: 12px;">Configúralo en 60 segundos</div>
                    </div>
                </div>

                <div class="plan">
                    <div class="recommended">
                        <span class="badge badge-info-solid">Recomendado</span>
                    </div>
                    <div class="plan-card featured">
                        <div class="eyebrow" style="align-self: flex-start; margin-bottom: 16px;">Suscripción</div>
                        <h3 style="font-family: var(--font-display); font-size: var(--text-2xl); font-weight: 700; color: var(--brand-navy); margin: 0 0 6px;">Profesional</h3>
                        <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0 0 22px; line-height: 1.5;">Cuando termine tu prueba, sigue con todo igual.</p>
                        <div style="margin-bottom: 22px; min-height: 70px;">
                            <div style="display: flex; align-items: baseline; gap: 8px;">
                                <span class="price paid">$120.000</span>
                                <span class="price-suffix">COP / mes</span>
                            </div>
                            <div style="font-size: var(--text-sm); color: var(--text-faint); margin-top: 8px;">Una sola suscripción · cancela cuando quieras</div>
                        </div>
                        <a href="{{ route('auth.google') }}" class="btn btn-primary btn-full" style="margin-top: auto;">Suscribirme</a>
                        <div style="font-size: 13px; color: var(--text-faint); text-align: center; margin-top: 12px;">Mismas funciones que la prueba</div>
                    </div>
                </div>
            </div>

            <div class="incluye-panel">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <svg width="20" height="20" fill="none" stroke="var(--brand-blue)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <h3 style="font-family: var(--font-display); font-size: var(--text-xl); font-weight: 700; color: var(--brand-navy); margin: 0;">Ambos planes incluyen todo</h3>
                </div>
                <ul class="incluye-grid lw-reveal before lw-stagger" style="list-style: none; padding: 0; margin: 0;">
                    @foreach([
                        'Casos y clientes ilimitados',
                        'Importación y vigilancia de la Rama Judicial',
                        'Alertas de actuaciones por correo',
                        'Calculadora de términos en días hábiles',
                        'Asistente IA jurídico',
                        'Portal del cliente y reportes PDF',
                        '21 flujos procesales + facturación por caso',
                        'Equipo de trabajo con permisos por caso',
                    ] as $feat)
                        <li>
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ $feat }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p style="text-align: center; font-size: var(--text-sm); color: var(--text-faint); margin-top: 28px;">
                Precio en pesos colombianos. La diferencia entre los planes es solo el tiempo: gratis los primeros 3 meses, luego $120.000/mes.
            </p>
        </div>
    </section>

    {{-- Trust band --}}
    <section style="padding: 56px 0; background: var(--white); border-top: 1px solid var(--border-subtle); border-bottom: 1px solid var(--border-subtle);">
        <div class="container" style="max-width: 880px;">
            <div class="trust-grid lw-reveal before lw-stagger">
                <div class="item">
                    <div class="k">Ley 1581</div>
                    <div class="d">Cumplimiento en protección de datos personales</div>
                </div>
                <div class="item">
                    <div class="k">Art. 74 C.P.</div>
                    <div class="d">Secreto profesional garantizado en cada acceso</div>
                </div>
                <div class="item">
                    <div class="k">CGP · Ley 906</div>
                    <div class="d">Flujos basados en legislación colombiana vigente</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="std close-cta">
        <div class="container inner">
            <img src="/images/logo-square.svg" alt="LegalWeb" style="width: 76px; height: 76px; margin: 0 auto 24px; display: block;">
            <h2>Deja que la Rama Judicial te avise a ti</h2>
            <p>Únete a los abogados colombianos que ya gestionan sus procesos con vigilancia automática y alertas por correo.</p>
            <div style="display: flex; justify-content: center;">
                <a href="{{ route('auth.google') }}" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="white" fill-opacity=".85"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="white" fill-opacity=".95"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="white" fill-opacity=".75"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="white" fill-opacity=".85"/></svg>
                    Comenzar con Google — Es gratis
                </a>
            </div>
            <p style="font-size: 13px; color: rgba(255,255,255,.5); margin-top: 16px;">Sin tarjeta de crédito · configuración en 60 segundos</p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="site">
        <div class="container">
            <div class="foot-grid">
                <div>
                    <img src="/images/logo.svg" alt="LegalWeb" style="height: 34px; margin-bottom: 14px;">
                    <p style="font-size: var(--text-sm); color: var(--text-muted); line-height: 1.6; max-width: 280px; margin: 0;">Control inteligente de tus procesos legales. Conectado con la Rama Judicial de Colombia.</p>
                </div>
                <div>
                    <h4>Producto</h4>
                    <ul>
                        <li><a href="#vigilancia">Vigilancia judicial</a></li>
                        <li><a href="#funcionalidades">Funcionalidades</a></li>
                        <li><a href="#vista-previa">La plataforma</a></li>
                        <li><a href="#planes">Planes</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Recursos</h4>
                    <ul>
                        <li><a href="#vigilancia">Cómo funciona</a></li>
                        <li><a href="mailto:legalwebco@gmail.com">Centro de ayuda</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="/portal/terminos">Términos y condiciones</a></li>
                        <li><a href="/portal/privacidad">Política de privacidad</a></li>
                    </ul>
                </div>
            </div>
            <div class="foot-bottom">
                <span>© {{ date('Y') }} LegalWeb. Hecho en Colombia.</span>
                <span>legalweb.com.co</span>
            </div>
        </div>
    </footer>

    {{-- Scroll to top --}}
    <button id="scroll-top-btn" class="scroll-top" aria-label="Volver arriba">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
    </button>

    <script>
        (function () {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* Scroll to top */
            const btn = document.getElementById('scroll-top-btn');
            if (btn) {
                window.addEventListener('scroll', function () {
                    btn.classList.toggle('visible', window.scrollY > 600);
                }, { passive: true });
                btn.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            if (reduceMotion) return;

            /* Reveal on scroll via IntersectionObserver */
            const targets = document.querySelectorAll('.lw-reveal');
            if ('IntersectionObserver' in window && targets.length) {
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
                targets.forEach((el) => io.observe(el));
            } else {
                targets.forEach((el) => el.classList.add('is-in'));
            }

            /* Nav hide-on-scroll-down */
            const nav = document.getElementById('main-nav');
            if (nav) {
                let lastY = window.scrollY;
                let ticking = false;
                window.addEventListener('scroll', () => {
                    if (ticking) return;
                    window.requestAnimationFrame(() => {
                        const y = window.scrollY;
                        const goingDown = y > lastY && y > 140;
                        nav.classList.toggle('is-hidden', goingDown);
                        lastY = y;
                        ticking = false;
                    });
                    ticking = true;
                }, { passive: true });
            }

            /* Parallax muy sutil en el hero visual (solo desktop, no toca layout) */
            const parallax = document.querySelector('.lw-parallax');
            if (parallax && window.matchMedia('(min-width: 981px)').matches) {
                let raf = null;
                window.addEventListener('scroll', () => {
                    if (raf) return;
                    raf = window.requestAnimationFrame(() => {
                        const y = window.scrollY;
                        const offset = Math.min(y * 0.08, 40);
                        parallax.style.transform = `translate3d(0, ${offset}px, 0)`;
                        raf = null;
                    });
                }, { passive: true });
            }
        })();
    </script>

</body>
</html>
