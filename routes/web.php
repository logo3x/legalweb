<?php

use App\Console\Commands\CheckDeadlines;
use App\Console\Commands\CheckSubscriptionGrace;
use App\Console\Commands\SendMonthlyReports;
use App\Console\Commands\SyncTybaActuaciones;
use App\Console\Commands\VerifyPendingPayments;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\WompiController;
use App\Jobs\SendMassEmailCampaign;
use App\Models\CasePermission;
use App\Models\FirmInvitation;
use App\Models\MassEmailCampaign;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;

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

    $path = storage_path('app/public/generated/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path, $filename);
})->middleware('auth')->name('download.file');

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
    if ($token !== config('app.cron_token')) {
        abort(403);
    }

    $results = [];

    // Tarea: sync-tyba (diaria 3am)
    if (! $task || $task === 'sync-tyba') {
        $command = new SyncTybaActuaciones;
        $command->setLaravel(app());
        $command->setOutput(new BufferedOutput);
        $command->handle();
        $results[] = 'sync-tyba: OK';
    }

    // Tarea: check-deadlines (diaria 8am)
    if (! $task || $task === 'check-deadlines') {
        $command = new CheckDeadlines;
        $command->setLaravel(app());
        $command->setOutput(new BufferedOutput);
        $command->handle();
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
        $command = new SendMonthlyReports;
        $command->setLaravel(app());
        $command->setOutput(new BufferedOutput);
        $command->handle();
        $results[] = 'monthly-reports: OK';
    }

    // Tarea: check-subscription-grace (diaria 9am, avisa vencimientos y suspende impagos)
    if (! $task || $task === 'check-subscription-grace') {
        $command = new CheckSubscriptionGrace;
        $command->setLaravel(app());
        $command->setOutput(new BufferedOutput);
        $command->handle();
        $results[] = 'check-subscription-grace: OK';
    }

    // Tarea: verify-payments (cada 15 min, verifica pagos pendientes)
    if ($task === 'verify-payments') {
        $command = new VerifyPendingPayments;
        $command->setLaravel(app());
        $command->setOutput(new BufferedOutput);
        $command->handle();
        $results[] = 'verify-payments: OK';
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
