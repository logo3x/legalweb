<?php

namespace Tests\Feature;

use App\Models\Firm;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\ReminderDueNotification;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.enabled' => true,
            'services.twilio.sid' => 'AC123',
            'services.twilio.auth_token' => 'secret',
            'services.twilio.whatsapp_from' => '+15550001111',
            'services.twilio.templates' => ['reminder.due.lawyer' => 'HXabc'],
        ]);
    }

    public function test_template_message_is_sent_to_twilio(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        $sent = app(WhatsAppService::class)->sendTemplate('300 123 4567', 'reminder.due.lawyer', [1 => 'Ana', 2 => 'Audiencia'], 'texto');

        $this->assertTrue($sent);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
            && $request['To'] === 'whatsapp:+573001234567'
            && $request['From'] === 'whatsapp:+15550001111'
            && $request['ContentSid'] === 'HXabc'
            && $request['ContentVariables'] === '{"1":"Ana","2":"Audiencia"}');
    }

    public function test_free_text_is_used_when_template_is_missing(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        app(WhatsAppService::class)->sendTemplate('3001234567', 'portal.access.lawyer', [1 => 'Ana'], 'texto libre');

        Http::assertSent(fn (Request $request): bool => $request['Body'] === 'texto libre' && ! isset($request['ContentSid']));
    }

    public function test_twilio_errors_return_false(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['code' => 63016, 'message' => 'not approved'], 400)]);

        $this->assertFalse(app(WhatsAppService::class)->send('3001234567', 'hola'));
    }

    public function test_nothing_is_sent_when_twilio_is_not_configured(): void
    {
        config(['services.twilio.sid' => null]);
        Http::fake();

        $this->assertFalse(app(WhatsAppService::class)->send('3001234567', 'hola'));
        Http::assertNothingSent();
    }

    public function test_normalize_phone(): void
    {
        $this->assertSame('+573001234567', WhatsAppService::normalizePhone('300-123-4567'));
        $this->assertSame('+573001234567', WhatsAppService::normalizePhone('+57 300 123 4567'));
        $this->assertSame('+14155550100', WhatsAppService::normalizePhone('+1 415 555 0100'));
        $this->assertNull(WhatsAppService::normalizePhone('12345'));
        $this->assertNull(WhatsAppService::normalizePhone(null));
    }

    public function test_whatsapp_channel_is_only_added_when_the_user_opted_in(): void
    {
        $reminder = Reminder::factory()->create(['firm_id' => Firm::factory()->create()->id]);
        $notification = new ReminderDueNotification($reminder);

        $optedIn = User::factory()->create(['whatsapp_enabled' => true, 'whatsapp_phone' => '+573001234567']);
        $optedOut = User::factory()->create(['whatsapp_enabled' => false, 'whatsapp_phone' => '+573001234567']);
        $withoutPhone = User::factory()->create(['whatsapp_enabled' => true, 'whatsapp_phone' => null]);

        $this->assertContains(WhatsAppChannel::class, $notification->via($optedIn));
        $this->assertNotContains(WhatsAppChannel::class, $notification->via($optedOut));
        $this->assertNotContains(WhatsAppChannel::class, $notification->via($withoutPhone));

        config(['services.twilio.sid' => null]);
        $this->assertNotContains(WhatsAppChannel::class, $notification->via($optedIn));
    }

    public function test_reminder_notification_is_delivered_by_whatsapp(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        $user = User::factory()->create(['name' => 'Ana', 'whatsapp_enabled' => true, 'whatsapp_phone' => '+573001234567']);
        $reminder = Reminder::factory()->create(['firm_id' => Firm::factory()->create()->id, 'user_id' => $user->id, 'title' => 'Contestar demanda']);

        app(WhatsAppChannel::class)->send($user, new ReminderDueNotification($reminder));

        Http::assertSent(function (Request $request): bool {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['ContentSid'] === 'HXabc'
                && $variables['1'] === 'Ana'
                && $variables['2'] === 'Contestar demanda';
        });
    }
}
