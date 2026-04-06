<?php

use App\Models\CaseEvent;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Services\TybaService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

$secret = 'legalweb-setup-2026';
if (($_GET['key'] ?? '') !== $secret) {
    exit('No autorizado');
}

$step = $_GET['step'] ?? 'info';

// Boot Laravel
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Capture output
ob_start();
$hasError = false;
$output = [];

function setup_log(string $msg, string $type = 'info'): void
{
    global $output;
    $output[] = ['msg' => $msg, 'type' => $type];
}

try {
    if ($step === 'info') {
        setup_log('PHP: '.PHP_VERSION);
        setup_log('Laravel: '.app()->version());
        setup_log('ENV: '.app()->environment());
        setup_log('APP_URL: '.config('app.url'));
        setup_log('DB: '.config('database.connections.mysql.database'));
        setup_log('DB Host: '.config('database.connections.mysql.host'));

        try {
            $pdo = DB::connection()->getPdo();
            setup_log('DB Connection: OK', 'success');
            $tables = DB::select('SHOW TABLES');
            setup_log('Tablas: '.count($tables));
            foreach ($tables as $t) {
                $vals = array_values((array) $t);
                setup_log('  '.$vals[0], 'muted');
            }
        } catch (Exception $e) {
            setup_log('DB Error: '.$e->getMessage(), 'error');
        }

        setup_log('---extensions---');
        setup_log('GD: '.(extension_loaded('gd') ? 'OK' : 'FALTA'), extension_loaded('gd') ? 'success' : 'error');
        setup_log('BCMath: '.(extension_loaded('bcmath') ? 'OK' : 'FALTA'), extension_loaded('bcmath') ? 'success' : 'error');
        setup_log('Zip: '.(extension_loaded('zip') ? 'OK' : 'FALTA'), extension_loaded('zip') ? 'success' : 'error');
        setup_log('Fileinfo: '.(extension_loaded('fileinfo') ? 'OK' : 'FALTA'), extension_loaded('fileinfo') ? 'success' : 'error');

        setup_log('---storage---');
        setup_log('Storage writable: '.(is_writable(storage_path()) ? 'OK' : 'NO'), is_writable(storage_path()) ? 'success' : 'error');
        setup_log('Bootstrap/cache writable: '.(is_writable(base_path('bootstrap/cache')) ? 'OK' : 'NO'), is_writable(base_path('bootstrap/cache')) ? 'success' : 'error');
        setup_log('Storage link: '.(file_exists(public_path('storage')) ? 'Existe' : 'Falta'), file_exists(public_path('storage')) ? 'success' : 'warning');

        setup_log('---cron---');
        setup_log('* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1', 'muted');
    }

    if ($step === 'key') {
        Artisan::call('key:generate', ['--force' => true]);
        setup_log('App key generada correctamente', 'success');
        setup_log(trim(Artisan::output()), 'muted');
    }

    if ($step === 'migrate') {
        Artisan::call('migrate', ['--force' => true]);
        $migrationOutput = trim(Artisan::output());
        setup_log('Migraciones ejecutadas', 'success');
        foreach (explode("\n", $migrationOutput) as $line) {
            if (trim($line)) {
                setup_log(trim($line), 'muted');
            }
        }
    }

    if ($step === 'seed') {
        Artisan::call('db:seed', ['--force' => true]);
        setup_log('Seeders ejecutados', 'success');
        $seedOutput = trim(Artisan::output());
        foreach (explode("\n", $seedOutput) as $line) {
            if (trim($line)) {
                setup_log(trim($line), 'muted');
            }
        }
    }

    if ($step === 'cleanup_users') {
        $superEmail = $_GET['super'] ?? 'legalwebco@gmail.com';

        $super = User::where('email', $superEmail)->first();
        if ($super) {
            $super->update(['role' => 'superadmin']);
            setup_log("Superadmin: {$super->name} ({$super->email})", 'success');
        } else {
            setup_log("Usuario {$superEmail} no encontrado. Debe registrarse con Google primero.", 'error');
        }

        User::where('email', '!=', $superEmail)
            ->whereIn('role', ['superadmin', 'admin'])
            ->each(function ($u) {
                $firm = $u->firm;
                if ($firm && User::where('firm_id', $firm->id)->count() === 1) {
                    $u->update(['role' => 'admin']);
                    setup_log("Mantenido como admin (firma): {$u->email}", 'warning');
                } else {
                    $u->update(['role' => 'abogado']);
                    setup_log("Cambiado a abogado: {$u->email}", 'info');
                }
            });

        setup_log('---usuarios---');
        User::all()->each(fn ($u) => setup_log("{$u->email} | {$u->role} | Firma: ".($u->firm?->name ?? 'N/A'), 'muted'));
    }

    if ($step === 'storage') {
        Artisan::call('storage:link');
        setup_log('Storage link creado', 'success');
    }

    if ($step === 'cache') {
        Artisan::call('config:cache');
        setup_log('Config cached', 'success');
        Artisan::call('route:cache');
        setup_log('Routes cached', 'success');
        Artisan::call('view:cache');
        setup_log('Views cached', 'success');
    }

    if ($step === 'clear') {
        Artisan::call('config:clear');
        setup_log('Config cleared', 'success');
        Artisan::call('route:clear');
        setup_log('Routes cleared', 'success');
        Artisan::call('view:clear');
        setup_log('Views cleared', 'success');
        Artisan::call('cache:clear');
        setup_log('Cache cleared', 'success');
    }

    if ($step === 'superadmin') {
        $email = $_GET['email'] ?? '';
        if (! $email) {
            setup_log('Debe pasar ?email=correo@ejemplo.com', 'error');
        } else {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['role' => 'superadmin']);
                setup_log("{$user->name} ({$user->email}) ahora es SUPERADMIN", 'success');
            } else {
                setup_log("Usuario con email {$email} no encontrado", 'error');
                User::all()->each(fn ($u) => setup_log("  {$u->email} ({$u->role})", 'muted'));
            }
        }
    }

    if ($step === 'users') {
        $users = User::with('firm')->get();
        foreach ($users as $u) {
            $google = $u->google_id ? 'Google' : 'Email';
            setup_log("#{$u->id} {$u->name} | {$u->email} | {$u->role} | Firma: ".($u->firm?->name ?? 'Sin firma')." | {$google}");
        }
        setup_log("Total: {$users->count()} usuarios", 'success');
    }

    if ($step === 'demo_reminders') {
        $userId = $_GET['user_id'] ?? null;
        if (! $userId) {
            setup_log('Pase ?user_id=X', 'error');
        } else {
            $user = User::find($userId);
            if (! $user) {
                setup_log('Usuario no encontrado', 'error');
            } else {
                $cases = LegalCase::withoutGlobalScopes()->where('firm_id', $user->firm_id)->take(2)->get();

                Reminder::create([
                    'firm_id' => $user->firm_id,
                    'user_id' => $user->id,
                    'legal_case_id' => $cases->first()?->id,
                    'title' => 'Audiencia inicial - Juzgado 5 Civil',
                    'description' => 'Preparar alegatos y revisar pruebas documentales.',
                    'type' => 'audiencia',
                    'priority' => 'alta',
                    'due_date' => now()->addDays(5)->setHour(9)->setMinute(0),
                    'remind_at' => now()->addDays(4)->setHour(8)->setMinute(0),
                ]);

                Reminder::create([
                    'firm_id' => $user->firm_id,
                    'user_id' => $user->id,
                    'legal_case_id' => $cases->skip(1)->first()?->id,
                    'title' => 'Vencimiento termino para contestar demanda',
                    'description' => 'Revisar expediente y preparar contestacion con excepciones.',
                    'type' => 'vencimiento',
                    'priority' => 'urgente',
                    'due_date' => now()->addDays(2)->setHour(17)->setMinute(0),
                    'remind_at' => now()->addDay()->setHour(8)->setMinute(0),
                ]);

                setup_log("2 recordatorios demo creados para {$user->name}", 'success');
            }
        }
    }

    if ($step === 'test_tyba') {
        $userId = $_GET['user_id'] ?? null;
        $radicado = $_GET['radicado'] ?? '68081310300120240001800';

        if (! $userId) {
            setup_log('Pase ?user_id=X&radicado=XXXXX', 'error');
            User::all()->each(fn ($u) => setup_log("  #{$u->id} {$u->name} ({$u->email}) | Firma: ".($u->firm?->name ?? 'N/A'), 'muted'));
        } else {
            $user = User::find($userId);

            if (! $user || ! $user->firm_id) {
                setup_log('Usuario no encontrado o sin firma', 'error');
            } else {
                $caseType = CaseType::first();
                $client = Client::withoutGlobalScopes()->where('firm_id', $user->firm_id)->first();

                if (! $client) {
                    setup_log('No hay clientes en la firma. Cree uno primero.', 'error');
                } else {
                    $case = LegalCase::create([
                        'firm_id' => $user->firm_id,
                        'case_number' => 'LW-TYBA-'.now()->timestamp,
                        'external_case_number' => $radicado,
                        'title' => 'Prueba consulta Tyba - Radicado '.$radicado,
                        'case_type_id' => $caseType->id,
                        'client_id' => $client->id,
                        'user_id' => $user->id,
                        'status' => 'en_progreso',
                        'priority' => 'alta',
                    ]);

                    setup_log("Caso creado: ID {$case->id} | {$case->case_number}", 'success');
                    setup_log("Radicado: {$radicado}");
                    setup_log("Abogado: {$user->name}");
                    setup_log("Siguiente paso: sync_tyba con case_id={$case->id}", 'warning');
                }
            }
        }
    }

    if ($step === 'sync_tyba') {
        $caseId = $_GET['case_id'] ?? null;

        if (! $caseId) {
            setup_log('Pase ?case_id=X', 'error');
        } else {
            $case = LegalCase::withoutGlobalScopes()->find($caseId);

            if (! $case) {
                setup_log('Caso no encontrado', 'error');
            } elseif (! $case->external_case_number) {
                setup_log('El caso no tiene radicado judicial', 'error');
            } else {
                setup_log("Caso: {$case->case_number}");
                setup_log("Radicado: {$case->external_case_number}");

                $apiKey = config('services.twocaptcha.api_key');
                setup_log('2Captcha key: '.($apiKey ? substr($apiKey, 0, 8).'...' : 'NO CONFIGURADA'), $apiKey ? 'success' : 'error');

                $balanceResp = Http::get('https://2captcha.com/res.php', [
                    'key' => $apiKey,
                    'action' => 'getbalance',
                    'json' => 1,
                ]);
                $balance = json_decode($balanceResp->body(), true);
                $balanceAmount = $balance['request'] ?? '?';
                setup_log("2Captcha balance: \${$balanceAmount}", ((float) $balanceAmount > 0) ? 'success' : 'warning');

                $tybaResp = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                    ->get(config('services.tyba.url'));

                $tybaOk = $tybaResp->successful();
                setup_log('Tyba: '.($tybaOk ? 'Conectado' : 'Error '.$tybaResp->status()), $tybaOk ? 'success' : 'error');
                setup_log('VIEWSTATE: '.(str_contains($tybaResp->body(), '__VIEWSTATE') ? 'OK' : 'No'), str_contains($tybaResp->body(), '__VIEWSTATE') ? 'success' : 'error');

                $hasCaptcha = str_contains($tybaResp->body(), 'recaptcha');
                setup_log('reCAPTCHA: '.($hasCaptcha ? 'Detectado' : 'No detectado'), $hasCaptcha ? 'info' : 'warning');

                $sitekey = config('services.tyba.sitekey');
                if (preg_match('/sitekey["\s:=]+["\']?([0-9a-zA-Z_-]{40})/i', $tybaResp->body(), $matches)) {
                    $realKey = $matches[1];
                    $keysMatch = $realKey === $sitekey;
                    setup_log('Sitekey: '.($keysMatch ? 'Coincide' : "NO coincide (real: {$realKey})"), $keysMatch ? 'success' : 'error');
                }

                if (preg_match('/grecaptcha\.execute/i', $tybaResp->body())) {
                    setup_log('Tipo: reCAPTCHA v3 (score-based)', 'info');
                } elseif (preg_match('/invisible/i', $tybaResp->body()) && $hasCaptcha) {
                    setup_log('Tipo: reCAPTCHA v2 invisible', 'info');
                } else {
                    setup_log('Tipo: no determinado (posible v2/v3)', 'warning');
                }

                // Prueba directa: POST con todos los hidden fields
                setup_log('---debug---');
                setup_log('Probando POST directo...', 'info');

                $tybaUrl = config('services.tyba.url');
                $tybaHtml = $tybaResp->body();

                // Extraer TODOS los campos del form (como un navegador)
                $formFields = [];

                // Inputs no-disabled, no-submit
                preg_match_all('/<input[^>]*name="([^"]*)"[^>]*>/si', $tybaHtml, $inputs, PREG_SET_ORDER);
                foreach ($inputs as $inp) {
                    if (preg_match('/\bdisabled\b/i', $inp[0])) {
                        continue;
                    }
                    if (preg_match('/type=["\']submit["\']/i', $inp[0])) {
                        continue;
                    }
                    $val = '';
                    if (preg_match('/value="([^"]*)"/', $inp[0], $vm)) {
                        $val = $vm[1];
                    }
                    $formFields[$inp[1]] = $val;
                }

                // Selects no-disabled con su valor selected
                preg_match_all('/<select[^>]*name="([^"]*)"[^>]*>(.*?)<\/select>/si', $tybaHtml, $selects, PREG_SET_ORDER);
                foreach ($selects as $sel) {
                    if (preg_match('/\bdisabled\b/i', $sel[0])) {
                        continue;
                    }
                    $val = '';
                    if (preg_match('/selected="selected"[^>]*value="([^"]*)"/', $sel[2], $sm)) {
                        $val = $sm[1];
                    } elseif (preg_match('/value="([^"]*)"[^>]*selected="selected"/', $sel[2], $sm)) {
                        $val = $sm[1];
                    }
                    $formFields[$sel[1]] = $val;
                }

                setup_log('Form fields: '.count($formFields), 'info');
                foreach ($formFields as $name => $val) {
                    $display = strlen($val) > 50 ? strlen($val).' chars' : (strlen($val) > 0 ? $val : 'vacio');
                    setup_log("  {$name}: {$display}", 'muted');
                }

                // Cookies
                $cookieJar = $tybaResp->cookies();
                $cookies = [];
                foreach ($cookieJar as $c) {
                    $cookies[$c->getName()] = $c->getValue();
                }
                setup_log('Cookies: '.implode(', ', array_keys($cookies)), ! empty($cookies) ? 'success' : 'warning');

                $domain = parse_url($tybaUrl, PHP_URL_HOST);
                $radicadoNum = preg_replace('/[^0-9]/', '', $case->external_case_number);

                // Resolver captcha v3 via 2Captcha
                setup_log('---captchasolve---');
                setup_log('Enviando a 2Captcha (v3, action=submit)...', 'info');

                $captchaToken = null;
                $captchaResp = Http::timeout(10)->get('https://2captcha.com/in.php', [
                    'key' => $apiKey,
                    'method' => 'userrecaptcha',
                    'googlekey' => config('services.tyba.sitekey'),
                    'pageurl' => $tybaUrl,
                    'version' => 'v3',
                    'action' => 'submit',
                    'min_score' => 0.3,
                    'json' => 1,
                ]);

                $captchaJson = $captchaResp->json();
                setup_log('2Captcha in.php: '.json_encode($captchaJson), $captchaJson['status'] ?? 0 ? 'success' : 'error');

                if (($captchaJson['status'] ?? 0) === 1) {
                    $captchaId = $captchaJson['request'];
                    setup_log("Captcha ID: {$captchaId}, esperando resolucion...", 'info');

                    ob_flush();
                    flush();

                    for ($i = 0; $i < 24; $i++) {
                        sleep(5);
                        $solveResp = Http::timeout(10)->get('https://2captcha.com/res.php', [
                            'key' => $apiKey,
                            'action' => 'get',
                            'id' => $captchaId,
                            'json' => 1,
                        ]);
                        $solveJson = $solveResp->json();

                        if (($solveJson['status'] ?? 0) === 1) {
                            $captchaToken = $solveJson['request'];
                            setup_log('Captcha resuelto! Token: '.substr($captchaToken, 0, 40).'...', 'success');

                            break;
                        }

                        if (($solveJson['request'] ?? '') !== 'CAPCHA_NOT_READY') {
                            setup_log('2Captcha error: '.json_encode($solveJson), 'error');

                            break;
                        }

                        if ($i % 4 === 0) {
                            setup_log('Esperando... ('.($i * 5).'s)', 'muted');
                        }
                    }

                    if (! $captchaToken) {
                        setup_log('Captcha no resuelto despues de esperar', 'error');
                    }
                }

                // Construir form data usando __doPostBack (como lo hace Tyba JS)
                $formData = $formFields;
                $formData['__EVENTTARGET'] = 'ctl00$MainContent$btnConsultar';
                $formData['__EVENTARGUMENT'] = '';
                $formData['ctl00$MainContent$txtCodigoProceso'] = $radicadoNum;
                $formData['ctl00$MainContent$txttp'] = '1';

                if ($captchaToken) {
                    $formData['recaptchaResponse'] = $captchaToken;
                    $formData['g-recaptcha-response'] = $captchaToken;
                    setup_log('POST con captcha + __doPostBack', 'success');
                } else {
                    setup_log('POST sin captcha + __doPostBack', 'warning');
                }

                setup_log('POST fields: '.implode(', ', array_keys($formData)), 'muted');

                $postResp = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Referer' => $tybaUrl,
                    ])
                    ->withCookies($cookies, $domain)
                    ->asForm()
                    ->post($tybaUrl, $formData);

                $postStatus = $postResp->status();
                $postBody = $postResp->body();
                $postLen = strlen($postBody);

                setup_log("HTTP status: {$postStatus}", $postResp->successful() ? 'success' : 'error');
                setup_log("Respuesta: {$postLen} bytes");
                setup_log('Tiene "del Proceso": '.(str_contains($postBody, 'del Proceso') ? 'SI' : 'NO'), str_contains($postBody, 'del Proceso') ? 'success' : 'warning');
                setup_log('Tiene "grdActuaciones": '.(str_contains($postBody, 'grdActuaciones') ? 'SI' : 'NO'), str_contains($postBody, 'grdActuaciones') ? 'success' : 'warning');
                setup_log('Tiene "Capcha": '.(str_contains($postBody, 'Capcha') ? 'SI' : 'NO'), str_contains($postBody, 'Capcha') ? 'warning' : 'success');
                setup_log('Tiene "error": '.(str_contains($postBody, 'lblMensajeError') ? 'SI' : 'NO'), str_contains($postBody, 'lblMensajeError') ? 'warning' : 'success');
                setup_log('Tiene "pnlResultado": '.(str_contains($postBody, 'pnlResultadoConsulta') ? 'SI' : 'NO'), str_contains($postBody, 'pnlResultadoConsulta') ? 'success' : 'info');
                setup_log('Tiene "MensajeInformativo": '.(str_contains($postBody, 'MensajeInformativo') ? 'SI' : 'NO'), str_contains($postBody, 'MensajeInformativo') ? 'warning' : 'info');
                setup_log('Tiene "grdProceso": '.(str_contains($postBody, 'grdProceso') ? 'SI' : 'NO'), str_contains($postBody, 'grdProceso') ? 'success' : 'info');
                setup_log('Tiene "text-danger": '.(str_contains($postBody, 'text-danger') ? 'SI' : 'NO'), str_contains($postBody, 'text-danger') ? 'warning' : 'info');

                // Buscar cualquier mensaje visible (display != none)
                if (preg_match('/style="[^"]*display:\s*block[^"]*"[^>]*>(.*?)</si', $postBody, $visibleMsg)) {
                    setup_log('Mensaje visible: '.strip_tags($visibleMsg[1]), 'warning');
                }

                // Buscar validation summary contenido
                if (preg_match('/ValidationSummary[^>]*>(.*?)<\/div>/si', $postBody, $valSum)) {
                    $valText = trim(strip_tags($valSum[1]));
                    if ($valText) {
                        setup_log('Validation: '.$valText, 'error');
                    }
                }

                // Comparar VIEWSTATE del POST response vs original
                if (preg_match('/name="__VIEWSTATE"[^>]*value="([^"]{0,20})/', $postBody, $newVs)) {
                    $origVsStart = substr($formFields['__VIEWSTATE'] ?? '', 0, 20);
                    $sameVs = ($newVs[1] === $origVsStart);
                    setup_log('ViewState cambio: '.($sameVs ? 'NO (identico)' : 'SI (diferente)'), $sameVs ? 'warning' : 'success');
                }

                // Extraer contenido de grdProceso
                setup_log('---resultados---');
                if (preg_match('/<table[^>]*id="[^"]*grdProceso[^"]*"[^>]*>(.*?)<\/table>/si', $postBody, $grdMatch)) {
                    $grdHtml = $grdMatch[1];
                    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $grdHtml, $rows);
                    $rowCount = count($rows[1] ?? []);
                    setup_log("grdProceso: {$rowCount} filas (incluyendo header)", 'success');
                    foreach (array_slice($rows[1] ?? [], 0, 3) as $i => $row) {
                        $text = trim(preg_replace('/\s+/', ' ', strip_tags($row)));
                        setup_log("  Fila {$i}: ".substr($text, 0, 300), 'muted');
                    }

                    // Buscar links a procesos (frmConsultaProceso)
                    preg_match_all('/href="([^"]*frmConsultaProceso[^"]*)"/i', $grdHtml, $procLinks);
                    if (! empty($procLinks[1])) {
                        setup_log('Links a procesos: '.count($procLinks[1]), 'success');
                        foreach (array_slice($procLinks[1], 0, 3) as $link) {
                            setup_log('  '.$link, 'muted');
                        }
                    }

                    // Buscar __doPostBack links
                    preg_match_all("/__doPostBack\('([^']+)'/", $grdHtml, $postbackLinks);
                    if (! empty($postbackLinks[1])) {
                        setup_log('PostBack links: '.count($postbackLinks[1]), 'info');
                        foreach (array_slice($postbackLinks[1], 0, 3) as $pb) {
                            setup_log('  '.$pb, 'muted');
                        }
                    }
                } else {
                    setup_log('grdProceso: tabla no encontrada o vacia', 'warning');
                }

                // MensajeInformativo
                if (preg_match('/MensajeInformativo[^>]*>(.*?)<\/div>/si', $postBody, $msgMatch)) {
                    $msgText = trim(strip_tags($msgMatch[1]));
                    if ($msgText) {
                        setup_log("MensajeInformativo: {$msgText}", 'warning');
                    } else {
                        setup_log('MensajeInformativo: vacio', 'muted');
                    }
                }

                // Panel resultado visible?
                if (preg_match('/pnlResultadoConsulta[^>]*style="([^"]*)"/i', $postBody, $pnlStyle)) {
                    setup_log('pnlResultado style: '.$pnlStyle[1], str_contains($pnlStyle[1], 'none') ? 'warning' : 'success');
                }

                // Debug: analizar reCAPTCHA en HTML
                setup_log('---captchajs---');

                // Buscar grecaptcha.execute con contexto
                if (preg_match('/grecaptcha\.execute\s*\(([^)]{0,200})\)/si', $tybaHtml, $execMatch)) {
                    setup_log('grecaptcha.execute('.($execMatch[1] ?? '...').')', 'info');
                } else {
                    setup_log('grecaptcha.execute no encontrado en HTML', 'warning');
                }

                // Buscar grecaptcha.render
                if (preg_match('/grecaptcha\.render\s*\(([^)]{0,200})\)/si', $tybaHtml, $renderMatch)) {
                    setup_log('grecaptcha.render('.($renderMatch[1] ?? '...').')', 'info');
                }

                // Buscar data-sitekey
                if (preg_match('/data-sitekey=["\']([^"\']+)["\']/i', $tybaHtml, $dsk)) {
                    setup_log('data-sitekey: '.$dsk[1], 'info');
                }

                // Buscar data-callback
                if (preg_match('/data-callback=["\']([^"\']+)["\']/i', $tybaHtml, $dcb)) {
                    setup_log('data-callback: '.$dcb[1], 'info');
                }

                // Buscar data-size=invisible
                if (preg_match('/data-size=["\']invisible["\']/i', $tybaHtml)) {
                    setup_log('data-size: invisible (es v2 invisible!)', 'warning');
                }

                // Buscar recaptchaResponse asignacion
                if (preg_match('/recaptchaResponse[^;]{0,100}/si', $tybaHtml, $rrm)) {
                    setup_log('recaptchaResponse usage: '.trim($rrm[0]), 'muted');
                }

                // Buscar la URL del script de recaptcha (v2 vs v3)
                if (preg_match('/recaptcha\/api\.js\?([^"\'>\s]+)/i', $tybaHtml, $apiJs)) {
                    setup_log('reCAPTCHA API: api.js?'.$apiJs[1], 'info');
                } elseif (preg_match('/recaptcha\/(enterprise|api)\.js/i', $tybaHtml, $apiJs)) {
                    setup_log('reCAPTCHA API: '.$apiJs[0], 'info');
                }

                // Buscar todo el bloque de script que menciona recaptcha
                preg_match_all('/<script[^>]*>([^<]*(?:recaptcha|grecaptcha)[^<]*)<\/script>/si', $tybaHtml, $scripts);
                foreach ($scripts[1] ?? [] as $i => $script) {
                    $script = trim($script);
                    if (strlen($script) > 5) {
                        setup_log('Script '.($i + 1).': '.substr($script, 0, 400), 'muted');
                    }
                }

                setup_log('---sync---');
                setup_log('Ejecutando TybaService...', 'info');

                ob_flush();
                flush();

                $tyba = new TybaService;
                $actuaciones = $tyba->consultarProceso($case->external_case_number);

                if ($actuaciones === null) {
                    setup_log('No se pudieron obtener actuaciones', 'error');

                    // Mostrar logs recientes de Tyba
                    $logFile = storage_path('logs/laravel.log');
                    if (file_exists($logFile)) {
                        $logLines = array_slice(file($logFile), -50);
                        $tybaLogs = array_filter($logLines, fn ($l) => str_contains($l, 'Tyba'));
                        $tybaLogs = array_slice($tybaLogs, -10);
                        if ($tybaLogs) {
                            setup_log('---logs---');
                            foreach ($tybaLogs as $l) {
                                $l = trim($l);
                                if (str_contains($l, 'ERROR')) {
                                    setup_log(substr($l, 0, 300), 'error');
                                } elseif (str_contains($l, 'WARNING')) {
                                    setup_log(substr($l, 0, 300), 'warning');
                                } else {
                                    setup_log(substr($l, 0, 300), 'muted');
                                }
                            }
                        }
                    }
                } elseif (empty($actuaciones)) {
                    setup_log('No se encontraron actuaciones para este radicado', 'warning');
                } else {
                    setup_log('Actuaciones encontradas: '.count($actuaciones), 'success');
                    $new = 0;

                    foreach ($actuaciones as $a) {
                        $date = null;
                        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
                            try {
                                $date = Carbon::createFromFormat($fmt, trim($a['date']));

                                break;
                            } catch (Exception $e) {
                                continue;
                            }
                        }

                        if (! $date) {
                            setup_log("[{$a['date']}] {$a['description']}", 'muted');

                            continue;
                        }

                        $exists = CaseEvent::where('legal_case_id', $case->id)
                            ->where('event_date', $date)
                            ->where('title', $a['description'])
                            ->exists();

                        if (! $exists) {
                            CaseEvent::create([
                                'legal_case_id' => $case->id,
                                'title' => $a['description'],
                                'event_date' => $date,
                                'event_type' => 'actuacion',
                                'description' => 'Sincronizado desde Rama Judicial (Tyba). Radicado: '.$case->external_case_number,
                                'user_id' => $case->user_id,
                            ]);
                            $new++;
                            setup_log("[{$a['date']}] {$a['description']}", 'success');
                        } else {
                            setup_log("[{$a['date']}] {$a['description']}", 'muted');
                        }
                    }

                    $case->update(['last_tyba_sync' => now()]);
                    setup_log("Nuevas actuaciones: {$new}", $new > 0 ? 'success' : 'info');
                }
            }
        }
    }

    if ($step === 'deadlines') {
        Artisan::call('app:check-deadlines');
        setup_log('Deadlines verificados', 'success');
        $dOutput = trim(Artisan::output());
        foreach (explode("\n", $dOutput) as $line) {
            if (trim($line)) {
                setup_log(trim($line), 'muted');
            }
        }
    }

    if ($step === 'fresh') {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        setup_log('Fresh migrate + seed completado', 'success');
        $freshOutput = trim(Artisan::output());
        foreach (explode("\n", $freshOutput) as $line) {
            if (trim($line)) {
                setup_log(trim($line), 'muted');
            }
        }
    }
} catch (Throwable $e) {
    $hasError = true;
    setup_log($e->getMessage(), 'error');
    setup_log($e->getFile().':'.$e->getLine(), 'muted');
}

ob_end_clean();

// Step titles
$stepTitles = [
    'info' => 'Estado del sistema',
    'key' => 'Generar App Key',
    'migrate' => 'Migraciones',
    'seed' => 'Seeders',
    'storage' => 'Storage Link',
    'cache' => 'Cache Config',
    'clear' => 'Limpiar Cache',
    'fresh' => 'Fresh Migrate + Seed',
    'users' => 'Usuarios',
    'superadmin' => 'Superadmin',
    'cleanup_users' => 'Limpiar Usuarios',
    'deadlines' => 'Verificar Deadlines',
    'demo_reminders' => 'Recordatorios Demo',
    'test_tyba' => 'Crear Caso Tyba',
    'sync_tyba' => 'Sincronizar Tyba',
];

$baseUrl = "?key={$secret}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalWeb Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #F5F7FA;
            color: #1E3A5F;
            min-height: 100vh;
        }
        .header {
            background: #1E3A5F;
            color: #fff;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(30,58,95,0.2);
        }
        .header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 600;
        }
        .header .badge {
            background: #3A86FF;
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        .layout {
            display: flex;
            min-height: calc(100vh - 56px);
        }
        .sidebar {
            width: 240px;
            background: #fff;
            border-right: 1px solid #E2E8F0;
            padding: 16px 0;
            flex-shrink: 0;
        }
        .sidebar .group-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94A3B8;
            padding: 12px 20px 6px;
        }
        .sidebar a {
            display: block;
            padding: 8px 20px;
            color: #475569;
            text-decoration: none;
            font-size: 13px;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        .sidebar a:hover {
            background: #F1F5F9;
            color: #1E3A5F;
        }
        .sidebar a.active {
            background: #EFF6FF;
            color: #3A86FF;
            border-left-color: #3A86FF;
            font-weight: 600;
        }
        .sidebar a.danger { color: #DC2626; }
        .sidebar a.danger:hover { background: #FEF2F2; }
        .main {
            flex: 1;
            padding: 24px 32px;
            max-width: 900px;
        }
        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1E3A5F;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #E2E8F0;
            overflow: hidden;
        }
        .card-body { padding: 20px; }
        .log-line {
            padding: 6px 0;
            font-size: 13px;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.5;
        }
        .log-line + .log-line { border-top: 1px solid #F1F5F9; }
        .log-line .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }
        .log-line.success .dot { background: #22C55E; }
        .log-line.error .dot { background: #EF4444; }
        .log-line.warning .dot { background: #F59E0B; }
        .log-line.info .dot { background: #3A86FF; }
        .log-line.muted .dot { background: #CBD5E1; }
        .log-line.muted { color: #94A3B8; }
        .log-line.error { color: #DC2626; font-weight: 500; }
        .log-line.success { color: #16A34A; }
        .log-line.warning { color: #D97706; }
        .separator {
            border: none;
            border-top: 1px solid #E2E8F0;
            margin: 12px 0;
        }
        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94A3B8;
            padding: 8px 0 4px;
        }
        @media (max-width: 768px) {
            .layout { flex-direction: column; }
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #E2E8F0;
                display: flex;
                flex-wrap: wrap;
                padding: 8px;
                gap: 4px;
            }
            .sidebar .group-title { display: none; }
            .sidebar a {
                border-left: none;
                border-radius: 6px;
                padding: 6px 12px;
                font-size: 12px;
            }
            .sidebar a.active { border-left: none; }
            .main { padding: 16px; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
            <path d="M9 12l2 2 4-4"/>
        </svg>
        <h1>LegalWeb</h1>
        <span class="badge">Setup</span>
    </div>

    <div class="layout">
        <nav class="sidebar">
            <div class="group-title">Sistema</div>
            <a href="<?= $baseUrl ?>&step=info" class="<?= $step === 'info' ? 'active' : '' ?>">Estado</a>
            <a href="<?= $baseUrl ?>&step=key" class="<?= $step === 'key' ? 'active' : '' ?>">App Key</a>
            <a href="<?= $baseUrl ?>&step=migrate" class="<?= $step === 'migrate' ? 'active' : '' ?>">Migrar</a>
            <a href="<?= $baseUrl ?>&step=seed" class="<?= $step === 'seed' ? 'active' : '' ?>">Seed</a>
            <a href="<?= $baseUrl ?>&step=storage" class="<?= $step === 'storage' ? 'active' : '' ?>">Storage Link</a>

            <div class="group-title">Cache</div>
            <a href="<?= $baseUrl ?>&step=cache" class="<?= $step === 'cache' ? 'active' : '' ?>">Cachear</a>
            <a href="<?= $baseUrl ?>&step=clear" class="<?= $step === 'clear' ? 'active' : '' ?>">Limpiar</a>

            <div class="group-title">Usuarios</div>
            <a href="<?= $baseUrl ?>&step=users" class="<?= $step === 'users' ? 'active' : '' ?>">Listar</a>
            <a href="<?= $baseUrl ?>&step=superadmin&email=" class="<?= $step === 'superadmin' ? 'active' : '' ?>">Superadmin</a>
            <a href="<?= $baseUrl ?>&step=cleanup_users&super=legalwebco@gmail.com" class="<?= $step === 'cleanup_users' ? 'active' : '' ?>">Limpiar roles</a>

            <div class="group-title">Tyba</div>
            <a href="<?= $baseUrl ?>&step=test_tyba&user_id=&radicado=68081310300120240001800" class="<?= $step === 'test_tyba' ? 'active' : '' ?>">Crear caso</a>
            <a href="<?= $baseUrl ?>&step=sync_tyba&case_id=" class="<?= $step === 'sync_tyba' ? 'active' : '' ?>">Sincronizar</a>

            <div class="group-title">Otros</div>
            <a href="<?= $baseUrl ?>&step=deadlines" class="<?= $step === 'deadlines' ? 'active' : '' ?>">Deadlines</a>
            <a href="<?= $baseUrl ?>&step=demo_reminders&user_id=" class="<?= $step === 'demo_reminders' ? 'active' : '' ?>">Reminders demo</a>
            <a href="<?= $baseUrl ?>&step=fresh" class="danger <?= $step === 'fresh' ? 'active' : '' ?>">Fresh (peligro)</a>
        </nav>

        <main class="main">
            <h2 class="page-title"><?= $stepTitles[$step] ?? ucfirst($step) ?></h2>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($output)) { ?>
                        <div class="log-line muted"><span class="dot"></span><span>Sin resultados</span></div>
                    <?php } else { ?>
                        <?php foreach ($output as $entry) { ?>
                            <?php if (str_starts_with($entry['msg'], '---')) { ?>
                                <?php
                                    $label = trim($entry['msg'], '-');
                                $labels = [
                                    'extensions' => 'Extensiones PHP',
                                    'storage' => 'Almacenamiento',
                                    'cron' => 'Cron Job',
                                    'usuarios' => 'Usuarios',
                                    'debug' => 'Prueba directa',
                                    'snippet' => 'Respuesta de Tyba (texto)',
                                    'sync' => 'Sincronizacion',
                                    'captchasolve' => 'Resolucion Captcha',
                                    'captchajs' => 'JavaScript reCAPTCHA',
                                    'logs' => 'Logs recientes',
                                ];
                                ?>
                                <hr class="separator">
                                <div class="section-label"><?= $labels[$label] ?? ucfirst($label) ?></div>
                            <?php } else { ?>
                                <div class="log-line <?= $entry['type'] ?>">
                                    <span class="dot"></span>
                                    <span><?= htmlspecialchars($entry['msg']) ?></span>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
