<?php

namespace Tests\Feature;

use App\Models\CaseEvent;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\VerificationReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monday_report_lists_every_process_with_failures_first(): void
    {
        Notification::fake();
        $this->travelTo('2026-12-07 08:00:00'); // lunes

        $firm = Firm::factory()->create();
        $lawyer = User::factory()->create(['firm_id' => $firm->id, 'role' => 'admin']);
        $other = User::factory()->create(['firm_id' => $firm->id, 'role' => 'abogado']);

        $base = ['firm_id' => $firm->id, 'user_id' => $lawyer->id, 'status' => 'abierto', 'is_demo' => false];
        $ok = LegalCase::factory()->create([...$base, 'case_number' => 'LW-0001-2026', 'external_case_number' => '11001310300120260000100', 'last_tyba_sync' => now()->subHours(5)]);
        LegalCase::factory()->create([...$base, 'case_number' => 'LW-0002-2026', 'external_case_number' => '11001310300120260000200', 'last_tyba_sync' => now()->subDays(4), 'sync_failed_at' => now()->subDays(2), 'sync_error' => 'La Rama Judicial no respondio (HTTP 503)']);
        LegalCase::factory()->create([...$base, 'case_number' => 'LW-0003-2026', 'external_case_number' => '11001310300120260000300', 'last_tyba_sync' => now()->subDays(5)]);
        LegalCase::factory()->create([...$base, 'case_number' => 'LW-0004-2026', 'external_case_number' => '', 'last_tyba_sync' => null]);
        LegalCase::factory()->create([...$base, 'case_number' => 'LW-0005-2026', 'external_case_number' => '11001310300120260000500', 'status' => 'cerrado']);
        CaseEvent::create(['legal_case_id' => $ok->id, 'title' => 'Auto admite', 'event_date' => now()->subDay(), 'event_type' => 'actuacion']);

        $this->artisan('app:send-verification-reports')->assertSuccessful();
        $this->artisan('app:send-verification-reports')->assertSuccessful();

        Notification::assertSentToTimes($lawyer, VerificationReportNotification::class, 1);
        Notification::assertNotSentTo($other, VerificationReportNotification::class);
        Notification::assertSentTo($lawyer, VerificationReportNotification::class, function (VerificationReportNotification $n) use ($lawyer): bool {
            $mail = $n->toMail($lawyer);

            return $n->summary === ['total' => 3, 'ok' => 1, 'error' => 1, 'stale' => 1, 'new_events' => 1]
                && array_column($n->rows, 'case_number') === ['LW-0002-2026', 'LW-0003-2026', 'LW-0001-2026']
                && str_contains((string) $mail->render(), 'HTTP 503')
                && str_contains($mail->subject, '2 de 3 procesos requieren');
        });
    }
}
