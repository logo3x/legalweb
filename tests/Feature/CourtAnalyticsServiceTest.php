<?php

namespace Tests\Feature;

use App\Models\CaseEvent;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Services\CourtAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_computes_stats_per_court_for_the_firm(): void
    {
        Carbon::setTestNow('2026-11-10 10:00:00');

        $firm = Firm::factory()->create();
        $otherFirm = Firm::factory()->create();

        $slowCase = LegalCase::factory()->create(['firm_id' => $firm->id, 'court' => 'JUZGADO 01  CIVIL', 'status' => 'abierto']);
        $this->events($slowCase, ['2026-06-01', '2026-07-01', '2026-08-30']);

        $fastCase = LegalCase::factory()->create(['firm_id' => $firm->id, 'court' => 'Juzgado 02 Laboral', 'status' => 'abierto']);
        $this->events($fastCase, ['2026-11-01', '2026-11-05']);

        LegalCase::factory()->create([
            'firm_id' => $firm->id, 'court' => 'Juzgado 01 Civil', 'status' => 'cerrado',
            'started_at' => '2026-01-01', 'closed_at' => '2026-03-02',
        ]);

        LegalCase::factory()->create(['firm_id' => $otherFirm->id, 'court' => 'Juzgado 99 Penal']);

        $data = app(CourtAnalyticsService::class)->forFirm($firm->id);

        $this->assertSame(2, $data['totals']['courts']);
        $this->assertSame(3, $data['totals']['cases']);
        $this->assertSame(5, $data['totals']['events']);

        $civil = collect($data['courts'])->firstWhere('court', 'Juzgado 01 Civil');
        $this->assertSame(2, $civil['total_cases']);
        $this->assertSame(1, $civil['active_cases']);
        $this->assertSame(45.0, $civil['avg_days_between_events']);
        $this->assertSame(72.0, $civil['avg_days_since_last_event']);
        $this->assertSame(60.0, $civil['avg_closed_duration_days']);
        $this->assertSame(1, $civil['stale_cases']);

        $this->assertSame('Juzgado 01 Civil', $data['slowest']['court']);
        $this->assertSame('Juzgado 02 Laboral', $data['fastest']['court']);
    }

    public function test_firm_without_cases_returns_empty_stats(): void
    {
        $data = app(CourtAnalyticsService::class)->forFirm(Firm::factory()->create()->id);

        $this->assertSame([], $data['courts']);
        $this->assertNull($data['slowest']);
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function events(LegalCase $case, array $dates): void
    {
        foreach ($dates as $date) {
            CaseEvent::factory()->create(['legal_case_id' => $case->id, 'event_date' => $date]);
        }
    }
}
