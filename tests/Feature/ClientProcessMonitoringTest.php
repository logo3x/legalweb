<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\NewProcessesFoundNotification;
use App\Services\TybaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

class ClientProcessMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $lawyer;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $firm = Firm::factory()->create();
        $this->lawyer = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $this->client = Client::factory()->create([
            'firm_id' => $firm->id,
            'user_id' => $this->lawyer->id,
            'document_type' => 'NIT',
            'document_number' => '900123456',
            'process_monitoring_enabled' => true,
            'is_demo' => false,
        ]);
    }

    public function test_it_notifies_only_processes_not_yet_imported(): void
    {
        LegalCase::factory()->create([
            'firm_id' => $this->client->firm_id,
            'external_case_number' => '11001310300120260000100',
        ]);

        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('searchByDocument')
            ->once()
            ->with('900123456', 3)
            ->andReturn([
                $this->process('11001310300120260000100'),
                $this->process('05001310500120260000200'),
            ]));

        $this->artisan('app:monitor-client-processes')->assertSuccessful();

        Notification::assertSentTo($this->lawyer, NewProcessesFoundNotification::class, function (NewProcessesFoundNotification $n): bool {
            return count($n->processes) === 1 && $n->processes[0]['radicado'] === '05001310500120260000200';
        });

        $client = $this->client->fresh();
        $this->assertNotNull($client->process_monitoring_last_at);
        $this->assertContains('05001310500120260000200', $client->process_monitoring_known);
    }

    public function test_known_processes_are_not_notified_twice(): void
    {
        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldReceive('searchByDocument')
            ->twice()
            ->andReturn([$this->process('05001310500120260000200')]));

        $this->artisan('app:monitor-client-processes')->assertSuccessful();
        $this->artisan('app:monitor-client-processes')->assertSuccessful();

        Notification::assertSentToTimes($this->lawyer, NewProcessesFoundNotification::class, 1);
    }

    public function test_clients_without_monitoring_or_demo_are_skipped(): void
    {
        $this->client->update(['process_monitoring_enabled' => false]);
        Client::factory()->create([
            'firm_id' => $this->client->firm_id,
            'document_number' => '123',
            'process_monitoring_enabled' => true,
            'is_demo' => true,
        ]);

        $this->mock(TybaService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('searchByDocument'));

        $this->artisan('app:monitor-client-processes')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_notification_links_to_document_search(): void
    {
        $notification = new NewProcessesFoundNotification($this->client, [$this->process('05001310500120260000200')]);

        $mail = $notification->toMail($this->lawyer);

        $this->assertStringContainsString('documento=900123456', $mail->actionUrl);
        $this->assertStringContainsString('tipo_documento=3', $mail->actionUrl);
        $this->assertSame('client.new_processes.lawyer', $notification->toWhatsApp($this->lawyer)->template);
    }

    /**
     * @return array<string, mixed>
     */
    private function process(string $radicado): array
    {
        return ['radicado' => $radicado, 'despacho' => 'Juzgado 01 Civil', 'departamento' => 'Bogota', 'fecha' => '01/11/2026', 'sujetos' => '', 'es_privado' => false];
    }
}
