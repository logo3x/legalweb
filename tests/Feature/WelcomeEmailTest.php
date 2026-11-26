<?php

namespace Tests\Feature;

use App\Console\Commands\SendLifecycleEmails;
use App\Models\Firm;
use App\Models\FirmInvitation;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email, string $name = 'Laura Gómez'): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => 'google-'.md5($email),
            'email' => $email,
            'name' => $name,
            'avatar' => null,
        ])->setRaw(['email_verified' => true]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_new_firm_admin_receives_the_welcome_email_immediately(): void
    {
        Notification::fake();
        $this->fakeGoogleUser('laura@estudio.co');

        $this->get('/auth/google/callback')->assertRedirect('/admin/onboarding');

        $user = User::where('email', 'laura@estudio.co')->sole();
        Notification::assertSentTo($user, WelcomeNotification::class, fn (WelcomeNotification $n): bool => $n->isNewFirm);
        $this->assertDatabaseHas('lifecycle_emails', ['user_id' => $user->id, 'key' => 'bienvenida']);

        // El correo diario de ciclo de vida no repite la bienvenida.
        $this->assertNotSame('bienvenida', app(SendLifecycleEmails::class)->dueEmail($user));
    }

    public function test_invited_member_receives_a_welcome_with_the_firm_name(): void
    {
        Notification::fake();
        $firm = Firm::factory()->create(['name' => 'Gómez & Asociados']);
        $admin = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        FirmInvitation::createForEmail($firm, $admin, 'socio@estudio.co', 'colaborador');
        $this->fakeGoogleUser('socio@estudio.co', 'Pedro Ruiz');

        $this->get('/auth/google/callback')->assertRedirect('/admin');

        $member = User::where('email', 'socio@estudio.co')->sole();
        Notification::assertSentTo($member, WelcomeNotification::class, fn (WelcomeNotification $n): bool => ! $n->isNewFirm && $n->firmName === 'Gómez & Asociados');
    }

    public function test_welcome_email_summarizes_the_features_and_invites_to_import(): void
    {
        $user = User::factory()->make(['name' => 'Laura']);

        $html = (string) (new WelcomeNotification)->toMail($user)->render();

        $this->assertStringContainsString('3 meses con todas las funciones', $html);
        $this->assertStringContainsString('Vigilancia diaria', $html);
        $this->assertStringContainsString('informe con la última consulta exitosa', $html);
        $this->assertStringContainsString('Importar mi primer proceso', $html);
        $this->assertStringContainsString('/admin/legal-cases', $html);
    }
}
