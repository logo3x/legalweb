<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\WompiController;
use App\Jobs\SendMassEmailCampaign;
use App\Models\CasePermission;
use App\Models\Document;
use App\Models\FirmInvitation;
use App\Models\LegalCase;
use App\Models\MassEmailCampaign;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use App\Services\AIModelRegistry;
use App\Services\GeneratedFileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Google Auth (rate limited)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// Portal del Cliente (rate limited)
Route::prefix('portal')->name('portal.')->middleware('throttle:30,1')->group(function () {
    Route::get('/terminos', [PortalController::class, 'terms'])->name('terms');
    Route::get('/privacidad', [PortalController::class, 'privacy'])->name('privacy');
    Route::get('/{token}', [PortalController::class, 'show'])->name('show');
    Route::post('/{token}/aceptar', [PortalController::class, 'accept'])->name('accept');
    Route::post('/{token}/document/{document}/ready', [PortalController::class, 'documentReady'])->name('document.ready');
    Route::post('/{token}/document/{document}/link', [PortalController::class, 'documentLink'])->name('document.link');
});

// Delete invitation
Route::delete('/admin/team/delete-invite/{invitation}', function (FirmInvitation $invitation) {
    if (! auth()->user()->isAdmin() || $invitation->firm_id !== auth()->user()->firm_id) {
        abort(403);
    }

    $invitation->delete();

    return redirect('/admin/team-members');
})->middleware('auth')->name('team.delete-invite');

// Assign cases to team member
Route::post('/admin/team/assign-cases/{user}', function (User $user, Request $request) {
    $authUser = auth()->user();

    if (! $authUser->isAdmin() || $user->firm_id !== $authUser->firm_id) {
        abort(403);
    }

    $cases = $request->input('cases', []);

    // Eliminar permisos anteriores
    CasePermission::where('user_id', $user->id)->delete();

    // Crear nuevos permisos
    foreach ($cases as $caseId => $data) {
        if (! isset($data['enabled'])) {
            continue;
        }

        CasePermission::create([
            'user_id' => $user->id,
            'legal_case_id' => $caseId,
            'permissions' => $data['permissions'] ?? [],
            'assigned_by' => $authUser->id,
        ]);
    }

    return redirect('/admin/team-members')->with('success', 'Casos asignados correctamente.');
})->middleware('auth')->name('team.assign-cases');

// Download generated documents
Route::get('/download/{filename}', function (string $filename) {
    $filename = basename($filename);

    if (! preg_match('/^[a-zA-Z0-9_\-\.]+\.(docx|pdf|xlsx|csv)$/', $filename)) {
        abort(403, 'Tipo de archivo no permitido.');
    }

    // Solo busca en la carpeta privada de la firma del usuario: no se pueden bajar archivos de otra firma.
    $firmId = auth()->user()->firm_id;
    $path = $firmId ? app(GeneratedFileStore::class)->find($firmId, $filename) : null;

    if (! $path) {
        abort(404);
    }

    return response()->download($path, $filename)->deleteFileAfterSend();
})->middleware('auth')->name('download.file');

// Descarga de documentos subidos al caso. El caso se busca con FirmScope (firma y casos asignados),
// asi que solo lo descarga quien puede ver el caso. Siempre como adjunto, nunca se muestra en linea.
Route::get('/documents/{document}/file', function (Document $document) {
    if (! $document->file_path || ! LegalCase::find($document->legal_case_id)) {
        abort(404);
    }

    $fileName = $document->name ?: basename($document->file_path);

    // Los documentos antiguos (antes de moverlos a storage privado) siguen en el disco publico.
    foreach (['local', 'public'] as $disk) {
        if (Storage::disk($disk)->exists($document->file_path)) {
            return Storage::disk($disk)->download($document->file_path, $fileName);
        }
    }

    abort(404);
})->middleware('auth')->name('documents.file');

// Tour completion
Route::post('/admin/tour/complete', function () {
    auth()->user()?->update(['tour_completed_at' => now()]);

    return response()->json(['ok' => true]);
})->middleware('auth')->name('tour.complete');

// Tour reset (volver a verlo)
Route::post('/admin/tour/reset', function () {
    auth()->user()?->update(['tour_completed_at' => null]);

    return redirect('/admin');
})->middleware('auth')->name('tour.reset');

// Wompi Payments
Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/wompi/checkout', [WompiController::class, 'checkout'])->name('wompi.checkout');
    Route::get('/wompi/callback', [WompiController::class, 'callback'])->name('wompi.callback');
});
Route::post('/wompi/webhook', [WompiController::class, 'webhook'])->middleware('throttle:60,1')->name('wompi.webhook');

// Cron alternativo via HTTP (para hostings sin proc_open)
Route::get('/cron/{token}/{task?}', function (string $token, ?string $task = null) {
    $cronToken = (string) config('app.cron_token');

    if (strlen($cronToken) < 20 || ! hash_equals($cronToken, $token)) {
        abort(403);
    }

    $results = [];

    // Tarea: sync-tyba (diaria 3am)
    if (! $task || $task === 'sync-tyba') {
        Artisan::call('app:sync-tyba-actuaciones');
        $results[] = 'sync-tyba: OK';
    }

    // Tarea: check-deadlines (diaria 8am)
    if (! $task || $task === 'check-deadlines') {
        Artisan::call('app:check-deadlines');
        $results[] = 'check-deadlines: OK';
    }

    // Tarea: send-reminders (cada 5 min)
    // Antes usabamos ventana de "ultima hora" lo que perdia recordatorios
    // silenciosamente si el cron fallaba un dia. Ahora trackeamos cada envio
    // con notified_at y mandamos todo lo pendiente cuyo remind_at ya paso.
    // Tope de 100 por corrida para no saturar SMTP en backfill grande.
    if (! $task || $task === 'send-reminders') {
        // Soporte de catch-up: si la columna notified_at todavia no existe
        // (migracion pendiente), volvemos al comportamiento anterior.
        $hasNotifiedAt = Schema::hasColumn('reminders', 'notified_at');

        $query = Reminder::with('user')
            ->where('is_completed', false)
            ->whereNotNull('remind_at')
            ->where('remind_at', '<=', now());

        if ($hasNotifiedAt) {
            $query->whereNull('notified_at');
        } else {
            $query->where('remind_at', '>=', now()->subHour());
        }

        $reminders = $query->orderBy('remind_at')->limit(100)->get();

        $sent = 0;
        $failed = 0;
        foreach ($reminders as $reminder) {
            if (! $reminder->user) {
                continue;
            }
            try {
                $reminder->user->notify(new ReminderDueNotification($reminder));
                if ($hasNotifiedAt) {
                    $reminder->forceFill(['notified_at' => now()])->saveQuietly();
                }
                $sent++;
            } catch (Throwable $e) {
                Log::warning('send-reminders fallo: '.$e->getMessage(), ['id' => $reminder->id]);
                $failed++;
            }
        }
        $results[] = "send-reminders: {$sent} enviados".($failed > 0 ? ", {$failed} fallidos" : '');
    }

    // Procesar jobs pendientes en la cola (max 50 seg)
    if (! $task || $task === 'queue') {
        $processed = 0;
        $start = time();
        while (time() - $start < 50) {
            $job = app('queue')->connection('database')->pop();
            if (! $job) {
                break;
            }
            try {
                $job->fire();
                $job->delete();
                $processed++;
            } catch (Exception $e) {
                $job->fail($e);
                Log::error('Cron queue error: '.$e->getMessage());
            }
        }
        $results[] = "queue: {$processed} job(s) procesados";
    }

    // Tarea: monthly-reports (dia 1 de cada mes a las 7am)
    if ($task === 'monthly-reports') {
        Artisan::call('app:send-monthly-reports');
        $results[] = 'monthly-reports: OK';
    }

    // Tarea: check-subscription-grace (diaria 9am, avisa vencimientos y suspende impagos)
    if (! $task || $task === 'check-subscription-grace') {
        Artisan::call('app:check-subscription-grace');
        $results[] = 'check-subscription-grace: OK';
    }

    // Tarea: setup (manual, una vez por deploy). Corre migraciones nuevas + clear caches.
    // Util cuando solo hay acceso por File Manager / git pull, sin shell/ssh.
    if ($task === 'setup') {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = Artisan::output();
        } catch (Throwable $e) {
            $migrateOutput = 'ERROR: '.$e->getMessage();
        }
        try {
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            $clearOutput = 'caches limpiados';
        } catch (Throwable $e) {
            $clearOutput = 'ERROR clear: '.$e->getMessage();
        }
        $results[] = 'setup: migrate => '.trim($migrateOutput);
        $results[] = 'setup: '.$clearOutput;
    }

    // Tarea: verify-payments (cada 15 min, verifica pagos pendientes)
    if ($task === 'verify-payments') {
        Artisan::call('app:verify-pending-payments');
        $results[] = 'verify-payments: OK';
    }

    // Tarea: verify-ai-models (diaria, prueba los modelos de IA disponibles)
    if ($task === 'verify-ai-models') {
        Artisan::call('app:verify-ai-models');
        $results[] = 'verify-ai-models: '.count(app(AIModelRegistry::class)->verifiedModels()).' modelo(s) disponibles';
    }

    // Tarea: mass-emails (cada 5 min, despacha campañas programadas que llegaron a su hora)
    if (! $task || $task === 'mass-emails') {
        $pending = MassEmailCampaign::where('status', 'programado')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($pending as $campaign) {
            SendMassEmailCampaign::dispatch($campaign->id);
        }
        $results[] = 'mass-emails: '.$pending->count().' campania(s) despachadas';
    }

    return response()->json([
        'status' => 'ok',
        'time' => now()->toDateTimeString(),
        'results' => $results,
    ]);
})->middleware('throttle:6,1');
