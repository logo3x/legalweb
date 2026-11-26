<?php

namespace Tests\Feature;

use App\Filament\Pages\ProbarNotificaciones;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\AutoRenewFailedNotification;
use App\Notifications\SyncFailuresNotification;
use App\Notifications\TermDeadlineNotification;
use App\Notifications\TybaSyncNotification;
use App\Notifications\VerificationReportNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProbarNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_notice_can_be_tested_without_cases_and_nothing_is_saved(): void
    {
        Filament::setCurrentPanel('admin');
        Notification::fake();
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id, 'role' => 'admin']);
        $this->actingAs($user);

        $page = Livewire::test(ProbarNotificaciones::class)->assertSee('No es spam', false);

        foreach (['tyba_sync', 'term_deadline', 'sync_failures', 'verification_report', 'auto_renew_failed'] as $key) {
            cache()->flush(); // limite de pruebas por usuario
            $page->call('enviarPrueba', $key)->assertNotified('Correo de prueba enviado');
        }

        Notification::assertSentTo($user, TybaSyncNotification::class);
        Notification::assertSentTo($user, TermDeadlineNotification::class);
        Notification::assertSentTo($user, SyncFailuresNotification::class);
        Notification::assertSentTo($user, VerificationReportNotification::class);
        Notification::assertSentTo($user, AutoRenewFailedNotification::class);
        $this->assertSame(0, LegalCase::withoutGlobalScopes()->count(), 'El caso de ejemplo no se guarda.');
    }

    public function test_emails_tell_users_how_to_avoid_spam(): void
    {
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id]);
        $html = (string) (new VerificationReportNotification([], ['total' => 0, 'ok' => 0, 'error' => 0, 'stale' => 0, 'new_events' => 0]))->toMail($user)->render();

        $this->assertStringContainsString('Para que nuestros avisos no terminen en spam, agregue', $html);
        $this->assertStringContainsString(config('mail.from.address').'</strong> a sus contactos', $html);
    }
}
