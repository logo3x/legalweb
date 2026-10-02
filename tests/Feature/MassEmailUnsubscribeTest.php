<?php

namespace Tests\Feature;

use App\Models\Firm;
use App\Models\MassEmailCampaign;
use App\Models\User;
use App\Notifications\MassEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MassEmailUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(string $body): MassEmailCampaign
    {
        return MassEmailCampaign::create([
            'user_id' => User::factory()->create(['role' => 'superadmin'])->id,
            'subject' => 'Novedades',
            'body' => $body,
            'audience_type' => 'all',
            'status' => 'borrador',
        ]);
    }

    public function test_signed_link_unsubscribes_the_user(): void
    {
        $user = User::factory()->create();

        $this->get(URL::signedRoute('mass-email.unsubscribe', ['user' => $user->id]))
            ->assertOk()
            ->assertSee($user->email);

        $this->assertNotNull($user->fresh()->mass_email_opt_out_at);
    }

    public function test_unsigned_or_tampered_link_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->get(route('mass-email.unsubscribe', ['user' => $user->id]))->assertForbidden();

        $signedForUser = URL::signedRoute('mass-email.unsubscribe', ['user' => $user->id]);
        $this->get(str_replace("/baja/{$user->id}", "/baja/{$other->id}", $signedForUser))->assertForbidden();

        $this->assertNull($user->fresh()->mass_email_opt_out_at);
        $this->assertNull($other->fresh()->mass_email_opt_out_at);
    }

    public function test_unsubscribed_users_are_excluded_from_campaigns(): void
    {
        $subscribed = User::factory()->create();
        $unsubscribed = User::factory()->create();
        $unsubscribed->forceFill(['mass_email_opt_out_at' => now()])->save();

        $recipients = $this->campaign('<p>Hola</p>')->resolveRecipients();

        $this->assertTrue($recipients->contains('id', $subscribed->id));
        $this->assertFalse($recipients->contains('id', $unsubscribed->id));
    }

    public function test_html_email_escapes_user_values_and_includes_unsubscribe(): void
    {
        $firm = Firm::factory()->create(['name' => '<script>alert(1)</script> Abogados']);
        $user = User::factory()->create(['firm_id' => $firm->id, 'name' => 'Ana <b>Perez</b>']);

        $mail = (new MassEmailNotification($this->campaign('<p>Hola {{name}} de {{firm}}</p>')))->toMail($user);
        $html = (string) $mail->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('Ana &lt;b&gt;Perez&lt;/b&gt;', $html);
        $this->assertStringContainsString('/correos/baja/'.$user->id, $html);
    }

    public function test_plain_text_email_includes_unsubscribe_link(): void
    {
        $user = User::factory()->create();

        $mail = (new MassEmailNotification($this->campaign("Hola colega\n\nTenemos novedades")))->toMail($user);

        $this->assertStringContainsString('/correos/baja/'.$user->id, (string) $mail->render());
    }
}
