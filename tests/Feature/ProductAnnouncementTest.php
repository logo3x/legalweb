<?php

namespace Tests\Feature;

use App\Models\Firm;
use App\Models\MassEmailCampaign;
use App\Models\MassEmailTemplate;
use App\Models\User;
use App\Notifications\MassEmailNotification;
use App\Notifications\ProductUpdatesNotification;
use Database\Seeders\MassEmailTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_november_updates_email_template_renders_with_user_data(): void
    {
        $this->seed(MassEmailTemplatesSeeder::class);

        $template = MassEmailTemplate::where('name', 'Novedades noviembre 2026 - WhatsApp, SAMAI, IA privada y planes')->firstOrFail();
        $user = User::factory()->create(['name' => 'Camila Rojas', 'firm_id' => Firm::factory()->create()->id]);
        $campaign = new MassEmailCampaign(['subject' => $template->subject, 'body' => $template->body]);

        $html = (string) (new MassEmailNotification($campaign))->toMail($user)->render();

        $this->assertStringContainsString('Camila, LegalWeb ahora trabaja aun mas por usted', $html);
        $this->assertStringContainsString('Avisos por WhatsApp', $html);
        $this->assertStringContainsString('12 de diciembre de 2026', $html);
        $this->assertStringContainsString(url('/portal/privacidad'), $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_trial_templates_no_longer_promise_read_only_mode(): void
    {
        $this->seed(MassEmailTemplatesSeeder::class);

        $this->assertStringNotContainsString('solo-lectura', MassEmailTemplate::where('name', 'Trial expirado - reactivar')->value('body'));
        $this->assertStringContainsString('plan Gratuito', MassEmailTemplate::where('name', 'Ultima semana de prueba - convertir')->value('body'));
    }

    public function test_in_app_announcement_reaches_the_bell(): void
    {
        $user = User::factory()->create(['firm_id' => Firm::factory()->create()->id]);

        $user->notify(new ProductUpdatesNotification('Novedades en LegalWeb', 'Avisos por WhatsApp', 'Ver', url('/admin/planes')));

        $this->assertSame('Novedades en LegalWeb', $user->notifications()->first()->data['title']);
    }
}
