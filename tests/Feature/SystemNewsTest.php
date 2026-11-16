<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SystemNewsNotification;
use App\Services\AIChangeNotifier;
use App\Services\AIModelHealth;
use App\Services\AIModelRegistry;
use App\Services\TybaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SystemNewsTest extends TestCase
{
    use RefreshDatabase;

    private const FIRST = 'openrouter:acme/first-model:free';

    private const SECOND = 'openrouter:acme/second-model:free';

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'services.gemini.api_key' => null,
            'services.nvidia.api_key' => null,
            'services.openrouter.api_key' => 'openrouter-test-key',
            'services.openrouter.data_collection' => 'deny',
        ]);

        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ['key' => self::FIRST, 'label' => 'First Model', 'verified_at' => now()->toDateTimeString()],
            ['key' => self::SECOND, 'label' => 'Second Model', 'verified_at' => now()->toDateTimeString()],
        ]);

        $this->superadmin = User::factory()->create(['role' => 'superadmin', 'firm_id' => null]);
    }

    private function health(string $key, bool $ok, ?int $status = null): void
    {
        app(AIModelHealth::class)->record($key, $ok, $status, $ok ? null : 'Modelo saturado', 100, 'automatica');
    }

    public function test_emails_the_superadmin_when_the_primary_model_stops_working(): void
    {
        $this->health(self::FIRST, true);
        $this->health(self::SECOND, true);
        $notifier = app(AIChangeNotifier::class);

        $this->assertSame([], $notifier->check(), 'La primera revision solo toma el punto de partida.');

        $this->health(self::FIRST, false, 503);
        $lines = $notifier->check();

        $this->assertStringContainsString('El modelo principal cambió de First Model a Second Model porque el anterior dejó de responder (error 503)', $lines[0]);
        Notification::assertSentTo($this->superadmin, SystemNewsNotification::class, function (SystemNewsNotification $notification): bool {
            $mail = $notification->toMail($this->superadmin);

            return $notification->title === 'Cambió el modelo de IA principal'
                && str_contains($mail->subject, 'Novedades de LegalWeb')
                && str_contains((string) $mail->render(), 'Second Model')
                && in_array('mail', $notification->via($this->superadmin), true);
        });

        // Sin cambios nuevos no se repite el correo.
        Cache::forget(AIChangeNotifier::MAIL_LOCK_KEY);
        $this->assertSame([], $notifier->check());
        Notification::assertSentToTimes($this->superadmin, SystemNewsNotification::class, 1);
    }

    public function test_reports_outage_recovery_and_new_models_at_most_once_per_hour(): void
    {
        $notifier = app(AIChangeNotifier::class);
        $this->health(self::FIRST, true);
        $this->health(self::SECOND, true);
        $notifier->check();

        $this->health(self::FIRST, false, 404);
        $this->health(self::SECOND, false, 404);
        $this->assertStringContainsString('Ningún modelo de IA está respondiendo', $notifier->check()[0]);

        // Dentro de la misma hora no sale otro correo: el cambio queda para la siguiente revision.
        $this->health(self::SECOND, true);
        $this->assertSame([], $notifier->check());

        $this->travel(61)->minutes();
        Cache::forever(AIModelRegistry::CACHE_KEY, [
            ...Cache::get(AIModelRegistry::CACHE_KEY),
            ['key' => 'openrouter:acme/new-model:free', 'label' => 'New Model', 'verified_at' => now()->toDateTimeString()],
        ]);
        $lines = $notifier->check();

        $this->assertStringContainsString('La IA volvió a responder. Modelo principal: Second Model', $lines[0]);
        $this->assertStringContainsString('Modelos nuevos disponibles: New Model', $lines[1]);
        Notification::assertSentToTimes($this->superadmin, SystemNewsNotification::class, 2);
    }

    public function test_rama_document_search_change_is_reported_once(): void
    {
        Http::fake(['*/DocumentoIdentificacion*' => Http::response(['StatusCode' => 404], 404)]);

        app(TybaService::class)->searchByDocument('79845123');
        app(TybaService::class)->searchByDocument('52987456');

        Notification::assertSentToTimes($this->superadmin, SystemNewsNotification::class, 1);
        Notification::assertSentTo($this->superadmin, SystemNewsNotification::class, fn (SystemNewsNotification $n): bool => str_contains($n->title, 'cédula o NIT'));
    }

    public function test_the_scheduled_check_runs_the_change_detection(): void
    {
        Http::fake(['*/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'La tutela protege derechos fundamentales.']]]])]);

        app(AIModelHealth::class)->runScheduledCheck(2);

        $this->assertIsArray(Cache::get(AIChangeNotifier::SNAPSHOT_KEY));
    }
}
