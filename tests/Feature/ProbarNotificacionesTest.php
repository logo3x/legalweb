<?php

namespace Tests\Feature;

use App\Filament\Pages\ProbarNotificaciones;
use App\Filament\Resources\LegalCases\Pages\ViewLegalCase;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\AutoRenewFailedNotification;
use App\Notifications\SyncFailuresNotification;
use App\Notifications\TermDeadlineNotification;
use App\Notifications\TybaSyncNotification;
use App\Notifications\VerificationReportNotification;
use App\Services\DemoDataService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProbarNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_notice_uses_a_real_demo_case_even_without_cases(): void
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

        // Sin casos, se recrean los datos de demostracion y las pruebas usan un caso demo real.
        $demo = LegalCase::where('firm_id', $user->firm_id)->where('is_demo', true)->orderBy('id')->firstOrFail();
        $this->assertSame(0, LegalCase::where('firm_id', $user->firm_id)->where('is_demo', false)->count());
        $this->assertStringNotContainsString('[DEMO]', $demo->fresh()->title, 'La marca solo va en el correo.');

        Notification::assertSentTo($user, TybaSyncNotification::class, function (TybaSyncNotification $n) use ($user): bool {
            $html = (string) $n->toMail($user)->render();

            return $n->case->is_demo
                && str_starts_with($n->case->title, '[DEMO] ')
                && str_contains($html, url("/admin/legal-cases/{$n->case->id}"));
        });
        Notification::assertSentTo($user, TermDeadlineNotification::class, fn (TermDeadlineNotification $n): bool => $n->case->is_demo);
        Notification::assertSentTo($user, SyncFailuresNotification::class);
        Notification::assertSentTo($user, VerificationReportNotification::class);
        Notification::assertSentTo($user, AutoRenewFailedNotification::class);

        // La segunda prueba reutiliza los mismos datos demo (no los duplica).
        $demoCount = LegalCase::where('firm_id', $user->firm_id)->count();
        cache()->flush();
        $page->call('enviarPrueba', 'tyba_sync');
        $this->assertSame($demoCount, LegalCase::where('firm_id', $user->firm_id)->count());
    }

    public function test_demo_case_page_says_it_is_demo_data(): void
    {
        Filament::setCurrentPanel('admin');
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id, 'role' => 'admin']);
        app(DemoDataService::class)->seedForFirm($user->firm, $user);
        $demo = LegalCase::where('firm_id', $user->firm_id)->where('is_demo', true)->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ViewLegalCase::class, ['record' => $demo->getRouteKey()])
            ->assertSee('Caso de demostración: datos de ejemplo');
    }

    public function test_user_without_firm_gets_an_unsaved_sample(): void
    {
        Filament::setCurrentPanel('admin');
        Notification::fake();
        $user = User::factory()->create(['firm_id' => null, 'role' => 'superadmin']);
        $this->actingAs($user);

        Livewire::test(ProbarNotificaciones::class)->call('enviarPrueba', 'tyba_sync')->assertNotified('Correo de prueba enviado');

        Notification::assertSentTo($user, TybaSyncNotification::class);
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
