<?php

use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\NewFirmRegistered;
use App\Services\TybaService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

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
        $cronToken = config('app.cron_token');
        $appUrl = config('app.url');
        setup_log('Opcion 1 (si proc_open esta habilitado):', 'info');
        setup_log('* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1', 'muted');
        setup_log('Opcion 2 (via HTTP, recomendado para hosting compartido):', 'info');
        setup_log("Cada 5 min: */5 * * * * curl -s {$appUrl}/cron/{$cronToken}/send-reminders > /dev/null", 'muted');
        setup_log("Diario 3am: 0 3 * * * curl -s {$appUrl}/cron/{$cronToken}/sync-tyba > /dev/null", 'muted');
        setup_log("Diario 3:05am: 5 3 * * * curl -s {$appUrl}/cron/{$cronToken}/queue > /dev/null", 'muted');
        setup_log("Diario 8am: 0 8 * * * curl -s {$appUrl}/cron/{$cronToken}/check-deadlines > /dev/null", 'muted');
        setup_log("Cada 15 min: */15 * * * * curl -s {$appUrl}/cron/{$cronToken}/verify-payments > /dev/null", 'muted');
        setup_log("Dia 1 - 7am: 0 7 1 * * curl -s {$appUrl}/cron/{$cronToken}/monthly-reports > /dev/null", 'muted');
    }

    if ($step === 'composer') {
        $basePath = base_path();
        $output = shell_exec("cd {$basePath} && php composer.phar install --no-dev --optimize-autoloader 2>&1")
            ?? shell_exec("cd {$basePath} && composer install --no-dev --optimize-autoloader 2>&1")
            ?? 'No se pudo ejecutar composer';
        foreach (explode("\n", $output) as $line) {
            if (trim($line)) {
                setup_log(trim($line), str_contains($line, 'error') ? 'error' : 'muted');
            }
        }
    }

    if ($step === 'key') {
        Artisan::call('key:generate', ['--force' => true]);
        setup_log('App key generada correctamente', 'success');
        setup_log(trim(Artisan::output()), 'muted');
    }

    if ($step === 'migrate_status') {
        Artisan::call('migrate:status');
        $output = trim(Artisan::output());
        setup_log('Estado de migraciones (archivos vs BD):', 'info');
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (! $line) {
                continue;
            }
            $type = 'muted';
            if (stripos($line, 'Pending') !== false) {
                $type = 'warning';
            } elseif (stripos($line, 'Ran') !== false) {
                $type = 'muted';
            }
            setup_log(htmlspecialchars($line), $type);
        }
    }

    if ($step === 'git_pull') {
        if (! function_exists('shell_exec')) {
            setup_log('shell_exec esta deshabilitado en este hosting. Use FTP o el panel git de cPanel para actualizar el codigo.', 'error');
        } else {
            $cwd = base_path();
            $out = shell_exec('cd '.escapeshellarg($cwd).' && git fetch --all 2>&1 && git reset --hard origin/main 2>&1');
            setup_log('Salida de git pull:', 'info');
            foreach (explode("\n", trim((string) $out)) as $line) {
                if (trim($line)) {
                    setup_log(htmlspecialchars(trim($line)), 'muted');
                }
            }
            $commit = trim((string) shell_exec('cd '.escapeshellarg($cwd).' && git log -1 --oneline 2>&1'));
            setup_log('Commit actual: '.htmlspecialchars($commit), 'success');
        }
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

    if ($step === 'seed_email_templates') {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MassEmailTemplatesSeeder',
            '--force' => true,
        ]);
        setup_log('Plantillas de correo cargadas', 'success');
        $seedOutput = trim(Artisan::output());
        foreach (explode("\n", $seedOutput) as $line) {
            if (trim($line)) {
                setup_log(trim($line), 'muted');
            }
        }
    }

    if ($step === 'seed_mass_email_demo') {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MassEmailDemoSeeder',
            '--force' => true,
        ]);
        setup_log('Campanas demo creadas', 'success');
        $seedOutput = trim(Artisan::output());
        foreach (explode("\n", $seedOutput) as $line) {
            if (trim($line)) {
                setup_log(trim($line), 'muted');
            }
        }
    }

    if ($step === 'cleanup_users') {
        if (($_GET['confirm'] ?? '') !== 'yes') {
            setup_log('Esta accion cambiara los roles de todos los usuarios excepto el superadmin.', 'warning');
            setup_log('Los usuarios con rol admin/superadmin seran cambiados a abogado (excepto el propietario de su firma).', 'warning');
            $cleanupUrl = $baseUrl.'&step=cleanup_users&super='.urlencode($_GET['super'] ?? 'legalwebco@gmail.com').'&confirm=yes';
            setup_log("<a href='{$cleanupUrl}' style='color:#ea580c;font-weight:bold;text-decoration:underline;'>CONFIRMAR: Si, limpiar roles</a>", 'raw');
        } else {
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
    }

    if ($step === 'storage') {
        Artisan::call('storage:link');
        setup_log('Storage link creado', 'success');
    }

    if ($step === 'trim_logos') {
        if (! extension_loaded('gd')) {
            setup_log('Extension GD no disponible. No se puede procesar imagenes.', 'error');
        } else {
            $firms = Firm::whereNotNull('logo_path')->get();

            if ($firms->isEmpty()) {
                setup_log('Ninguna firma tiene logo subido.', 'warning');
            }

            foreach ($firms as $firm) {
                $relativePath = $firm->logo_path;
                $absolutePath = storage_path('app/public/'.$relativePath);

                if (! file_exists($absolutePath)) {
                    setup_log("[{$firm->name}] archivo no encontrado: {$relativePath}", 'error');

                    continue;
                }

                $info = getimagesize($absolutePath);
                if (! $info) {
                    setup_log("[{$firm->name}] no es imagen valida: {$relativePath}", 'error');

                    continue;
                }

                $originalSize = filesize($absolutePath);
                $type = $info[2];

                $img = match ($type) {
                    IMAGETYPE_PNG => imagecreatefrompng($absolutePath),
                    IMAGETYPE_JPEG => imagecreatefromjpeg($absolutePath),
                    IMAGETYPE_GIF => imagecreatefromgif($absolutePath),
                    IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($absolutePath) : null,
                    default => null,
                };

                if (! $img) {
                    setup_log("[{$firm->name}] formato no soportado", 'error');

                    continue;
                }

                $w = imagesx($img);
                $h = imagesy($img);
                $hasAlpha = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP]);

                // Buscar bounding box del contenido visible
                $minX = $w;
                $minY = $h;
                $maxX = -1;
                $maxY = -1;

                for ($y = 0; $y < $h; $y++) {
                    for ($x = 0; $x < $w; $x++) {
                        $rgba = imagecolorat($img, $x, $y);
                        $alpha = ($rgba >> 24) & 0xFF;
                        $r = ($rgba >> 16) & 0xFF;
                        $g = ($rgba >> 8) & 0xFF;
                        $b = $rgba & 0xFF;

                        // Pixel es "contenido" si no es transparente y no es casi blanco
                        $isTransparent = $hasAlpha && $alpha > 100;
                        $isWhite = $r > 240 && $g > 240 && $b > 240;

                        if (! $isTransparent && ! $isWhite) {
                            if ($x < $minX) {
                                $minX = $x;
                            }
                            if ($y < $minY) {
                                $minY = $y;
                            }
                            if ($x > $maxX) {
                                $maxX = $x;
                            }
                            if ($y > $maxY) {
                                $maxY = $y;
                            }
                        }
                    }
                }

                if ($maxX < 0 || $maxY < 0) {
                    setup_log("[{$firm->name}] imagen vacia o totalmente blanca, sin cambios", 'warning');
                    imagedestroy($img);

                    continue;
                }

                $newW = $maxX - $minX + 1;
                $newH = $maxY - $minY + 1;

                if ($newW >= $w * 0.97 && $newH >= $h * 0.97) {
                    setup_log("[{$firm->name}] ya esta recortada (sin whitespace significativo)", 'muted');
                    imagedestroy($img);

                    continue;
                }

                // Padding aureo (4%) alrededor del contenido
                $padding = (int) max(2, max($newW, $newH) * 0.04);
                $finalW = $newW + $padding * 2;
                $finalH = $newH + $padding * 2;

                $newImg = imagecreatetruecolor($finalW, $finalH);

                if ($hasAlpha) {
                    imagealphablending($newImg, false);
                    imagesavealpha($newImg, true);
                    $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                    imagefill($newImg, 0, 0, $transparent);
                } else {
                    $white = imagecolorallocate($newImg, 255, 255, 255);
                    imagefill($newImg, 0, 0, $white);
                }

                imagecopy($newImg, $img, $padding, $padding, $minX, $minY, $newW, $newH);

                // Backup antes de sobrescribir
                $backupPath = $absolutePath.'.bak';
                if (! file_exists($backupPath)) {
                    copy($absolutePath, $backupPath);
                }

                $saved = match ($type) {
                    IMAGETYPE_PNG => imagepng($newImg, $absolutePath, 6),
                    IMAGETYPE_JPEG => imagejpeg($newImg, $absolutePath, 92),
                    IMAGETYPE_GIF => imagegif($newImg, $absolutePath),
                    IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($newImg, $absolutePath, 90) : false,
                    default => false,
                };

                imagedestroy($img);
                imagedestroy($newImg);

                if ($saved) {
                    $newSize = filesize($absolutePath);
                    $reductionPct = round(($w * $h - $finalW * $finalH) / ($w * $h) * 100);
                    setup_log(
                        "[{$firm->name}] {$w}x{$h} ({$originalSize}b) -> {$finalW}x{$finalH} ({$newSize}b) | -{$reductionPct}% area",
                        'success'
                    );
                } else {
                    setup_log("[{$firm->name}] error al guardar imagen recortada", 'error');
                }
            }

            setup_log('Backup original guardado como .bak en cada archivo procesado', 'info');
        }
    }

    if ($step === 'mail_test') {
        $to = $_GET['to'] ?? config('mail.from.address', '');

        if (! $to) {
            setup_log('Pase ?to=correo@ejemplo.com', 'error');
        } else {
            setup_log('---config---');
            setup_log('MAIL_MAILER: '.config('mail.default'));
            setup_log('MAIL_HOST: '.config('mail.mailers.smtp.host', '-'));
            setup_log('MAIL_PORT: '.config('mail.mailers.smtp.port', '-'));
            setup_log('MAIL_USERNAME: '.config('mail.mailers.smtp.username', '-'));
            setup_log('MAIL_ENCRYPTION: '.(config('mail.mailers.smtp.encryption') ?? 'sin cifrado'));
            setup_log('MAIL_FROM_ADDRESS: '.config('mail.from.address'));
            setup_log('MAIL_FROM_NAME: '.config('mail.from.name'));
            setup_log('NEW_FIRM_EMAILS: '.config('services.notifications.new_firm_emails'));

            setup_log('---envio---');
            setup_log("Enviando correo de prueba a: {$to}", 'info');

            try {
                Mail::raw(
                    "Este es un correo de prueba enviado desde LegalWeb.\n\n".
                    'Fecha: '.now()->format('d/m/Y H:i:s')."\n".
                    'App: '.config('app.name')."\n".
                    'URL: '.config('app.url')."\n\n".
                    'Si recibe este mensaje, la configuracion de correo esta funcionando correctamente.',
                    function ($message) use ($to) {
                        $message->to($to)
                            ->subject('LegalWeb - Test de correo '.now()->format('H:i:s'));
                    }
                );
                setup_log('Correo enviado correctamente a '.$to, 'success');
                setup_log('Revise su bandeja de entrada (y spam)', 'info');
            } catch (Exception $e) {
                setup_log('ERROR al enviar: '.$e->getMessage(), 'error');
                setup_log($e->getFile().':'.$e->getLine(), 'muted');
            }

            setup_log('---notificacion---');
            setup_log('Probando notificacion NewFirmRegistered a TODOS los emails de NEW_FIRM_EMAILS y superadmins...', 'info');

            try {
                $testFirm = Firm::first();
                $testUser = User::first();

                if ($testFirm && $testUser) {
                    $emails = array_filter(array_map('trim', explode(',', config('services.notifications.new_firm_emails', ''))));

                    if (empty($emails)) {
                        setup_log('NEW_FIRM_EMAILS vacio o no configurado', 'warning');
                    } else {
                        setup_log('Emails configurados en NEW_FIRM_EMAILS ('.count($emails).'):', 'info');
                        foreach ($emails as $email) {
                            setup_log('  -> '.$email, 'muted');
                        }
                        foreach ($emails as $email) {
                            try {
                                Notification::route('mail', $email)
                                    ->notify(new NewFirmRegistered($testFirm, $testUser));
                                setup_log('Enviado OK: '.$email, 'success');
                            } catch (Exception $e) {
                                setup_log('ERROR '.$email.': '.$e->getMessage(), 'error');
                            }
                        }
                    }

                    setup_log('---superadmins---');
                    $superadmins = User::where('role', 'superadmin')->get();
                    if ($superadmins->isEmpty()) {
                        setup_log('No hay superadmins configurados', 'warning');
                    } else {
                        foreach ($superadmins as $sa) {
                            try {
                                $sa->notify(new NewFirmRegistered($testFirm, $testUser));
                                setup_log('Enviado a superadmin: '.$sa->email, 'success');
                            } catch (Exception $e) {
                                setup_log('ERROR superadmin '.$sa->email.': '.$e->getMessage(), 'error');
                            }
                        }
                    }
                } else {
                    setup_log('No hay firmas o usuarios para probar la notificacion', 'warning');
                }
            } catch (Exception $e) {
                setup_log('ERROR notificacion: '.$e->getMessage(), 'error');
            }
        }
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

                // Probar extractProcessInfo (acceso directo sin captcha)
                setup_log('---importar---');
                setup_log('Consultando Tyba via URL directa...', 'info');

                $tyba = new TybaService;
                $info = $tyba->extractProcessInfo($case->external_case_number);

                $tyba = new TybaService;
                $radicadoNum = preg_replace('/[^0-9]/', '', $case->external_case_number);
                $baseUrl = dirname(config('services.tyba.url'));
                $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

                // Paso 1: visitar frmConsulta para obtener session
                $searchUrl = config('services.tyba.url');
                $searchResp = Http::timeout(15)->withHeaders(['User-Agent' => $ua])->get($searchUrl);
                $cookieJar = $searchResp->cookies();
                $cookies = [];
                foreach ($cookieJar as $c) {
                    $cookies[$c->getName()] = $c->getValue();
                }
                $domain = parse_url($searchUrl, PHP_URL_HOST);
                setup_log('Session cookies: '.implode(', ', array_keys($cookies)), ! empty($cookies) ? 'success' : 'error');

                // Paso 2: acceder a frmConsultaProceso CON las cookies de session
                $processUrl = $baseUrl.'/frmConsultaProceso.aspx?IdProceso='.$radicadoNum;
                $processResp = Http::timeout(30)
                    ->withHeaders(['User-Agent' => $ua, 'Referer' => $searchUrl])
                    ->withCookies($cookies, $domain)
                    ->get($processUrl);

                $html = $processResp->body();
                setup_log("GET con session: {$processResp->status()} | ".strlen($html).' bytes', $processResp->successful() ? 'success' : 'error');

                // Verificar si los campos tienen valor
                preg_match_all('/<input[^>]*MainContent_txt[^>]*value="([^"]+)"[^>]*>/si', $html, $filledInputs);
                setup_log('Campos con valor: '.count($filledInputs[0] ?? []), count($filledInputs[0] ?? []) > 0 ? 'success' : 'error');

                // Mostrar raw del campo codigo
                if (preg_match('/<input[^>]*MainContent_txtCodigoProceso[^>]*>/si', $html, $dm)) {
                    setup_log('CodigoProceso: '.htmlspecialchars(substr($dm[0], 0, 200)), 'info');
                }
                if (preg_match('/<input[^>]*MainContent_txtNomDespacho[^>]*>/si', $html, $dm)) {
                    setup_log('Despacho: '.htmlspecialchars(substr($dm[0], 0, 200)), 'info');
                }

                // Paso 3: probar sin cookies (directo)
                setup_log('---sincookies---');
                $directResp = Http::timeout(30)->withHeaders(['User-Agent' => $ua])->get($processUrl);
                $directHtml = $directResp->body();
                preg_match_all('/<input[^>]*MainContent_txt[^>]*value="([^"]+)"[^>]*>/si', $directHtml, $directInputs);
                setup_log('Sin cookies - campos con valor: '.count($directInputs[0] ?? []), count($directInputs[0] ?? []) > 0 ? 'success' : 'error');

                $info = $tyba->extractProcessInfo($case->external_case_number);

                if ($info) {
                    setup_log('Proceso encontrado!:', 'success');
                    foreach (['codigo_proceso', 'tipo_proceso', 'clase_proceso', 'especialidad', 'departamento', 'ciudad', 'despacho', 'fecha_publicacion', 'email', 'telefono'] as $field) {
                        $val = $info[$field] ?? '';
                        setup_log("  {$field}: ".($val ?: '(vacio)'), $val ? 'success' : 'warning');
                    }

                    if (! empty($info['sujetos'])) {
                        setup_log('---sujetos---');
                        foreach ($info['sujetos'] as $s) {
                            setup_log("  {$s['rol']}: {$s['nombre']} ({$s['documento']})", 'muted');
                        }
                    }
                } else {
                    setup_log('No se pudo obtener info del proceso', 'error');
                }
            }
        }
    }

    if ($step === 'logs') {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = file($logFile);
            // Buscar solo lineas que empiezan con fecha (mensajes, no stack traces)
            $messages = [];
            foreach ($lines as $l) {
                if (preg_match('/^\[20\d{2}-/', $l)) {
                    $messages[] = trim($l);
                }
            }
            // Ultimos 20 mensajes
            $messages = array_slice($messages, -20);
            foreach ($messages as $l) {
                if (str_contains($l, 'ERROR')) {
                    setup_log(substr($l, 0, 500), 'error');
                } elseif (str_contains($l, 'WARNING')) {
                    setup_log(substr($l, 0, 500), 'warning');
                } else {
                    setup_log(substr($l, 0, 500), 'muted');
                }
            }
        } else {
            setup_log('Archivo de log no encontrado', 'warning');
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
        if (($_GET['confirm'] ?? '') !== 'yes') {
            setup_log('ADVERTENCIA: Esta accion eliminara TODOS los datos de la base de datos y los recreara con datos de ejemplo.', 'error');
            setup_log('Se perderan: todos los casos, clientes, actuaciones, documentos, facturacion y configuracion.', 'error');
            setup_log('Esta accion es IRREVERSIBLE.', 'error');
            $freshUrl = $baseUrl.'&step=fresh&confirm=yes';
            setup_log("<a href='{$freshUrl}' style='color:#dc2626;font-weight:bold;text-decoration:underline;'>CONFIRMAR: Si, borrar todo y recrear</a>", 'raw');
        } else {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            setup_log('Fresh migrate + seed completado', 'success');
            $freshOutput = trim(Artisan::output());
            foreach (explode("\n", $freshOutput) as $line) {
                if (trim($line)) {
                    setup_log(trim($line), 'muted');
                }
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
    'composer' => 'Composer Install',
    'key' => 'Generar App Key',
    'migrate' => 'Migraciones',
    'seed' => 'Seeders',
    'storage' => 'Storage Link',
    'trim_logos' => 'Recortar logos de firmas',
    'mail_test' => 'Test de Correo',
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
            <a href="<?= $baseUrl ?>&step=composer" class="<?= $step === 'composer' ? 'active' : '' ?>">Composer Install</a>
            <a href="<?= $baseUrl ?>&step=key" class="<?= $step === 'key' ? 'active' : '' ?>">App Key</a>
            <a href="<?= $baseUrl ?>&step=git_pull" class="<?= $step === 'git_pull' ? 'active' : '' ?>">Git pull</a>
            <a href="<?= $baseUrl ?>&step=migrate_status" class="<?= $step === 'migrate_status' ? 'active' : '' ?>">Estado migraciones</a>
            <a href="<?= $baseUrl ?>&step=migrate" class="<?= $step === 'migrate' ? 'active' : '' ?>">Migrar</a>
            <a href="<?= $baseUrl ?>&step=seed" class="<?= $step === 'seed' ? 'active' : '' ?>">Seed</a>
            <a href="<?= $baseUrl ?>&step=seed_email_templates" class="<?= $step === 'seed_email_templates' ? 'active' : '' ?>">Seed plantillas correo</a>
            <a href="<?= $baseUrl ?>&step=seed_mass_email_demo" class="<?= $step === 'seed_mass_email_demo' ? 'active' : '' ?>">Seed campanas demo</a>
            <a href="<?= $baseUrl ?>&step=storage" class="<?= $step === 'storage' ? 'active' : '' ?>">Storage Link</a>
            <a href="<?= $baseUrl ?>&step=trim_logos" class="<?= $step === 'trim_logos' ? 'active' : '' ?>">Recortar logos firmas</a>
            <a href="<?= $baseUrl ?>&step=mail_test&to=lgoviedo17@hotmail.com" class="<?= $step === 'mail_test' ? 'active' : '' ?>">Test Correo</a>

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
            <a href="<?= $baseUrl ?>&step=logs" class="<?= $step === 'logs' ? 'active' : '' ?>">Logs</a>
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
                                    'importar' => 'Importar Proceso',
                                    'sincookies' => 'Sin cookies (comparacion)',
                                    'sujetos' => 'Sujetos Procesales',
                                ];
                                ?>
                                <hr class="separator">
                                <div class="section-label"><?= $labels[$label] ?? ucfirst($label) ?></div>
                            <?php } else { ?>
                                <div class="log-line <?= $entry['type'] ?>">
                                    <span class="dot"></span>
                                    <span><?= $entry['type'] === 'raw' ? $entry['msg'] : htmlspecialchars($entry['msg']) ?></span>
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
