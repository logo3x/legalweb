<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Widgets\GettingStarted;
use App\Filament\Widgets\MyDay;
use App\Models\CaseEvent;
use App\Models\CasePermission;
use App\Models\Client;
use App\Models\Document;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\MassEmailNotification;
use Database\Seeders\MassEmailTemplatesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MyDayAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;

    private User $admin;

    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Http::fake();

        $this->firm = Firm::factory()->create();
        $this->admin = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'admin', 'name' => 'Andrea Gomez']);
        $client = Client::factory()->create(['firm_id' => $this->firm->id]);
        $this->case = LegalCase::factory()->create(['firm_id' => $this->firm->id, 'client_id' => $client->id, 'user_id' => $this->admin->id, 'is_demo' => false]);
    }

    public function test_my_day_shows_what_needs_attention_today(): void
    {
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'legal_case_id' => $this->case->id, 'type' => 'vencimiento', 'title' => 'Subsanar demanda', 'due_date' => now()->addDays(2)]);
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'legal_case_id' => $this->case->id, 'type' => 'audiencia', 'title' => 'Audiencia inicial', 'due_date' => now()->addHours(3)]);
        Reminder::factory()->create(['firm_id' => $this->firm->id, 'user_id' => $this->admin->id, 'type' => 'vencimiento', 'title' => 'Termino lejano', 'due_date' => now()->addDays(20)]);
        CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'event_type' => 'actuacion', 'title' => 'Auto admite demanda', 'event_date' => now()]);
        Document::factory()->create(['legal_case_id' => $this->case->id, 'responsible' => 'cliente', 'status' => 'recibido', 'name' => 'Poder firmado']);

        $this->actingAs($this->admin);

        Livewire::test(MyDay::class)
            ->assertSee('Andrea')
            ->assertSee('Subsanar demanda')
            ->assertSee('Vence en 2 día(s)', false)
            ->assertSee('Audiencia inicial')
            ->assertSee('Auto admite demanda')
            ->assertSee('Poder firmado')
            ->assertDontSee('Termino lejano');
    }

    public function test_my_day_respects_collaborator_permissions(): void
    {
        $collaborator = User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']);
        CasePermission::create(['user_id' => $collaborator->id, 'legal_case_id' => $this->case->id, 'permissions' => ['case.view'], 'assigned_by' => $this->admin->id]);
        CaseEvent::factory()->create(['legal_case_id' => $this->case->id, 'event_type' => 'actuacion', 'title' => 'Actuacion reservada', 'event_date' => now()]);
        Document::factory()->create(['legal_case_id' => $this->case->id, 'responsible' => 'cliente', 'status' => 'recibido', 'name' => 'Documento reservado']);

        $this->actingAs($collaborator);

        Livewire::test(MyDay::class)->assertDontSee('Actuacion reservada')->assertDontSee('Documento reservado');
    }

    public function test_getting_started_tracks_progress_and_can_be_hidden(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue(GettingStarted::canView());
        Livewire::test(GettingStarted::class)->assertSee('Primeros pasos en LegalWeb')->assertSee('Importe su primer proceso');

        $this->case->update(['external_case_number' => '11001310300120250012300']);
        $steps = collect((new GettingStarted)->getSteps())->keyBy('title');
        $this->assertTrue($steps['Importe su primer proceso']['done']);

        Livewire::test(GettingStarted::class)->call('dismiss');
        $this->assertFalse(GettingStarted::canView());
    }

    public function test_getting_started_is_only_for_firm_admins(): void
    {
        $this->actingAs(User::factory()->create(['firm_id' => $this->firm->id, 'role' => 'abogado']));

        $this->assertFalse(GettingStarted::canView());
    }

    public function test_lifecycle_emails_follow_the_user_journey(): void
    {
        Notification::fake();
        $this->seed(MassEmailTemplatesSeeder::class);
        $this->case->update(['external_case_number' => null]);

        // Dia 0: bienvenida.
        $this->artisan('app:send-lifecycle-emails')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, MassEmailNotification::class, 1);

        // Mismo dia: nada mas.
        $this->artisan('app:send-lifecycle-emails')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, MassEmailNotification::class, 1);

        // Dia 4 sin procesos: "importe su primer proceso".
        $this->travel(4)->days();
        $this->admin->update(['last_login_at' => now()]);
        $this->artisan('app:send-lifecycle-emails')->assertSuccessful();
        Notification::assertSentTo($this->admin, MassEmailNotification::class, fn ($n) => $n->campaign->subject === 'Su primer proceso en LegalWeb toma 1 minuto');

        // Una semana sin entrar: aviso de inactividad.
        $this->travel(8)->days();
        $this->artisan('app:send-lifecycle-emails')->assertSuccessful();
        Notification::assertSentTo($this->admin, MassEmailNotification::class, fn ($n) => $n->campaign->subject === 'No deje sus casos solos esta semana');
        Notification::assertSentToTimes($this->admin, MassEmailNotification::class, 3);
    }

    public function test_lifecycle_emails_respect_unsubscribe(): void
    {
        Notification::fake();
        $this->seed(MassEmailTemplatesSeeder::class);
        $this->admin->forceFill(['mass_email_opt_out_at' => now()])->save();

        $this->artisan('app:send-lifecycle-emails')->assertSuccessful();

        Notification::assertNothingSentTo($this->admin);
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateClient::class)
            ->fillForm(['document_number' => '', 'first_name' => '', 'last_name' => ''])
            ->call('create')
            ->assertSee('es obligatorio');
    }

    public function test_dashboard_renders_with_all_widgets(): void
    {
        $this->firm->update(['onboarding_completed' => true]);
        $this->admin->forceFill(['terms_accepted_at' => now(), 'tour_completed_at' => now()])->save();

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Buenos')
            ->assertSee('Primeros pasos en LegalWeb');
    }
}
