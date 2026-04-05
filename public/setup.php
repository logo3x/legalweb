<?php

$secret = 'legalweb-setup-2026';
if (($_GET['key'] ?? '') !== $secret) {
    die('No autorizado');
}

$step = $_GET['step'] ?? 'info';

// Boot Laravel
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
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
        echo "<a href='?key=$secret&step=deadlines'>10. Check Deadlines (manual)</a>\n";
        echo "\n=== Cron Job (agregar en cPanel) ===\n";
        echo "* * * * * cd ".base_path()." && php artisan schedule:run >> /dev/null 2>&1\n";
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
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $user->update(['role' => 'superadmin']);
                echo "Usuario {$user->name} ({$user->email}) ahora es SUPERADMIN\n";
            } else {
                echo "Usuario con email {$email} no encontrado\n";
                echo "\nUsuarios disponibles:\n";
                \App\Models\User::all()->each(fn ($u) => print("- {$u->email} ({$u->role})\n"));
            }
        }
    }

    if ($step === 'users') {
        $users = \App\Models\User::with('firm')->get();
        echo "=== Usuarios Registrados ===\n\n";
        foreach ($users as $u) {
            echo "ID: {$u->id} | {$u->name} | {$u->email} | Rol: {$u->role} | Firma: " . ($u->firm?->name ?? 'Sin firma') . " | Google: " . ($u->google_id ? 'Si' : 'No') . "\n";
        }
        echo "\nTotal: " . $users->count() . " usuarios\n";
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
