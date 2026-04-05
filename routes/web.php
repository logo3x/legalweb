<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\WompiController;
use App\Models\CasePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google Auth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Portal del Cliente
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/terminos', [PortalController::class, 'terms'])->name('terms');
    Route::get('/privacidad', [PortalController::class, 'privacy'])->name('privacy');
    Route::get('/{token}', [PortalController::class, 'show'])->name('show');
    Route::post('/{token}/aceptar', [PortalController::class, 'accept'])->name('accept');
});

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
    $path = storage_path('app/public/generated/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
})->middleware('auth')->name('download.file')->where('filename', '.*');

// Wompi Payments
Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/wompi/checkout', [WompiController::class, 'checkout'])->name('wompi.checkout');
    Route::get('/wompi/callback', [WompiController::class, 'callback'])->name('wompi.callback');
});
Route::post('/wompi/webhook', [WompiController::class, 'webhook'])->name('wompi.webhook');
