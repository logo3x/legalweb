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

echo '<pre>';

try {
    if ($step === 'info') {
        echo "=== LegalWeb Setup ===\n\n";
        echo 'PHP: '.PHP_VERSION."\n";
        echo 'Laravel: '.app()->version()."\n";
        echo 'ENV: '.app()->environment()."\n";
        echo 'APP_URL: '.config('app.url')."\n";
        echo 'DB: '.config('database.connections.mysql.database')."\n";
        echo 'DB Host: '.config('database.connections.mysql.host')."\n";

        // Test DB connection
        try {
            $pdo = DB::connection()->getPdo();
            echo "DB Connection: OK\n";
            $tables = DB::select('SHOW TABLES');
            echo 'Tables: '.count($tables)."\n";
            foreach ($tables as $t) {
                $vals = array_values((array) $t);
                echo '  - '.$vals[0]."\n";
            }
        } catch (Exception $e) {
            echo 'DB Error: '.$e->getMessage()."\n";
        }

        // Check extensions
        echo "\n=== Extensions ===\n";
        echo 'GD: '.(extension_loaded('gd') ? 'OK' : 'MISSING')."\n";
        echo 'BCMath: '.(extension_loaded('bcmath') ? 'OK' : 'MISSING')."\n";
        echo 'Zip: '.(extension_loaded('zip') ? 'OK' : 'MISSING')."\n";
        echo 'Fileinfo: '.(extension_loaded('fileinfo') ? 'OK' : 'MISSING')."\n";

        // Check storage
        echo "\n=== Storage ===\n";
        echo 'Storage writable: '.(is_writable(storage_path()) ? 'OK' : 'NO')."\n";
        echo 'Bootstrap/cache writable: '.(is_writable(base_path('bootstrap/cache')) ? 'OK' : 'NO')."\n";
        echo 'Storage link: '.(file_exists(public_path('storage')) ? 'EXISTS' : 'MISSING')."\n";

        echo "\n=== Actions ===\n";
        echo "<a href='?key=$secret&step=key'>1. Generate Key</a>\n";
        echo "<a href='?key=$secret&step=migrate'>2. Migrate</a>\n";
        echo "<a href='?key=$secret&step=seed'>3. Seed</a>\n";
        echo "<a href='?key=$secret&step=storage'>4. Storage Link</a>\n";
        echo "<a href='?key=$secret&step=cache'>5. Cache</a>\n";
        echo "<a href='?key=$secret&step=clear'>6. Clear All Cache</a>\n";
        echo "<a href='?key=$secret&step=fresh'>7. Fresh Migrate + Seed (DANGER)</a>\n";
        echo "<a href='?key=$secret&step=users'>8. List Users</a>\n";
        echo "<a href='?key=$secret&step=superadmin&email='>9. Make Superadmin (add ?email=)</a>\n";
        echo "<a href='?key=$secret&step=cleanup_users&super=legalwebco@gmail.com'>10. Cleanup: solo superadmin legalwebco</a>\n";
        echo "<a href='?key=$secret&step=deadlines'>11. Check Deadlines (manual)</a>\n";
        echo "<a href='?key=$secret&step=test_tyba&user_id=&radicado=68081310300120240001800'>12. Crear caso prueba Tyba</a>\n";
        echo "<a href='?key=$secret&step=sync_tyba&case_id='>13. Sincronizar Tyba (add ?case_id=)</a>\n";
        echo "\n=== Cron Job (agregar en cPanel) ===\n";
        echo '* * * * * cd '.base_path()." && php artisan schedule:run >> /dev/null 2>&1\n";
    }

    if ($step === 'key') {
        Artisan::call('key:generate', ['--force' => true]);
        echo 'KEY: '.Artisan::output();
    }

    if ($step === 'migrate') {
        Artisan::call('migrate', ['--force' => true]);
        echo "MIGRATE:\n".Artisan::output();
    }

    if ($step === 'seed') {
        Artisan::call('db:seed', ['--force' => true]);
        echo "SEED:\n".Artisan::output();
    }

    if ($step === 'cleanup_users') {
        $superEmail = $_GET['super'] ?? 'legalwebco@gmail.com';

        // Buscar o crear superadmin
        $super = User::where('email', $superEmail)->first();
        if ($super) {
            $super->update(['role' => 'superadmin']);
            echo "Superadmin: {$super->name} ({$super->email})\n";
        } else {
            echo "Usuario {$superEmail} no encontrado. Debe registrarse con Google primero.\n";
        }

        // Quitar rol superadmin/admin de todos los demas
        User::where('email', '!=', $superEmail)
            ->whereIn('role', ['superadmin', 'admin'])
            ->each(function ($u) {
                // Si es dueño de firma, dejar como admin
                $firm = $u->firm;
                if ($firm && User::where('firm_id', $firm->id)->count() === 1) {
                    $u->update(['role' => 'admin']);
                    echo "Mantenido como admin (dueño de firma): {$u->email}\n";
                } else {
                    $u->update(['role' => 'abogado']);
                    echo "Cambiado a abogado: {$u->email}\n";
                }
            });

        echo "\n=== Usuarios actuales ===\n";
        User::all()->each(fn ($u) => print ("{$u->email} | {$u->role} | Firma: ".($u->firm?->name ?? 'N/A')."\n"));
    }

    if ($step === 'storage') {
        Artisan::call('storage:link');
        echo "STORAGE:\n".Artisan::output();
    }

    if ($step === 'cache') {
        Artisan::call('config:cache');
        echo "Config cached\n";
        Artisan::call('route:cache');
        echo "Routes cached\n";
        Artisan::call('view:cache');
        echo "Views cached\n";
    }

    if ($step === 'clear') {
        Artisan::call('config:clear');
        echo "Config cleared\n";
        Artisan::call('route:clear');
        echo "Routes cleared\n";
        Artisan::call('view:clear');
        echo "Views cleared\n";
        Artisan::call('cache:clear');
        echo "Cache cleared\n";
    }

    if ($step === 'superadmin') {
        $email = $_GET['email'] ?? '';
        if (! $email) {
            echo "ERROR: Debe pasar ?email=correo@ejemplo.com\n";
        } else {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['role' => 'superadmin']);
                echo "Usuario {$user->name} ({$user->email}) ahora es SUPERADMIN\n";
            } else {
                echo "Usuario con email {$email} no encontrado\n";
                echo "\nUsuarios disponibles:\n";
                User::all()->each(fn ($u) => print ("- {$u->email} ({$u->role})\n"));
            }
        }
    }

    if ($step === 'users') {
        $users = User::with('firm')->get();
        echo "=== Usuarios Registrados ===\n\n";
        foreach ($users as $u) {
            echo "ID: {$u->id} | {$u->name} | {$u->email} | Rol: {$u->role} | Firma: ".($u->firm?->name ?? 'Sin firma').' | Google: '.($u->google_id ? 'Si' : 'No')."\n";
        }
        echo "\nTotal: ".$users->count()." usuarios\n";
    }

    if ($step === 'demo_reminders') {
        $userId = $_GET['user_id'] ?? null;
        if (! $userId) {
            echo "ERROR: Pase ?user_id=X\n";
        } else {
            $user = User::find($userId);
            if (! $user) {
                echo "Usuario no encontrado\n";
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

                echo "2 recordatorios demo creados para {$user->name} ({$user->email})\n";
            }
        }
    }

    if ($step === 'test_tyba') {
        $userId = $_GET['user_id'] ?? null;
        $radicado = $_GET['radicado'] ?? '68081310300120240001800';

        if (! $userId) {
            echo "ERROR: Pase ?user_id=X&radicado=XXXXX\n";
            echo "\nUsuarios:\n";
            User::all()->each(fn ($u) => print ("- ID: {$u->id} | {$u->name} ({$u->email}) | Firma: ".($u->firm?->name ?? 'N/A')."\n"));
        } else {
            $user = User::find($userId);

            if (! $user || ! $user->firm_id) {
                echo "Usuario no encontrado o sin firma\n";
            } else {
                $caseType = CaseType::first();
                $client = Client::withoutGlobalScopes()->where('firm_id', $user->firm_id)->first();

                if (! $client) {
                    echo "No hay clientes en la firma. Cree uno primero.\n";
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

                    echo "Caso creado: ID {$case->id} | {$case->case_number}\n";
                    echo "Radicado: {$radicado}\n";
                    echo "Abogado: {$user->name}\n";
                    echo "\nAhora use: ?step=sync_tyba&case_id={$case->id}\n";
                }
            }
        }
    }

    if ($step === 'sync_tyba') {
        $caseId = $_GET['case_id'] ?? null;

        if (! $caseId) {
            echo "ERROR: Pase ?case_id=X\n";
        } else {
            $case = LegalCase::withoutGlobalScopes()->find($caseId);

            if (! $case) {
                echo "Caso no encontrado\n";
            } elseif (! $case->external_case_number) {
                echo "El caso no tiene radicado judicial\n";
            } else {
                echo "Sincronizando caso {$case->case_number} (radicado: {$case->external_case_number})...\n";
                echo "Resolviendo captcha via 2Captcha (esto puede tomar 30-60 segundos)...\n\n";

                ob_flush();
                flush();

                $tyba = new TybaService;
                $actuaciones = $tyba->consultarProceso($case->external_case_number);

                if ($actuaciones === null) {
                    echo "ERROR: No se pudieron obtener actuaciones. Verifique:\n";
                    echo "- Saldo de 2Captcha\n";
                    echo "- Que el radicado sea correcto\n";
                    echo "- Que Tyba este disponible\n";
                } elseif (empty($actuaciones)) {
                    echo "No se encontraron actuaciones para este radicado.\n";
                } else {
                    echo 'Actuaciones encontradas: '.count($actuaciones)."\n\n";
                    $new = 0;

                    foreach ($actuaciones as $a) {
                        echo "- [{$a['date']}] {$a['description']}\n";

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
                        }
                    }

                    $case->update(['last_tyba_sync' => now()]);
                    echo "\nNuevas actuaciones registradas: {$new}\n";
                }
            }
        }
    }

    if ($step === 'deadlines') {
        Artisan::call('app:check-deadlines');
        echo "CHECK DEADLINES:\n".Artisan::output();
    }

    if ($step === 'fresh') {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        echo "FRESH MIGRATE + SEED:\n".Artisan::output();
    }
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    echo "Trace:\n".$e->getTraceAsString();
}

echo "\n\nDONE</pre>";
