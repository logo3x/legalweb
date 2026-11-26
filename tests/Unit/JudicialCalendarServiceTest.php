<?php

namespace Tests\Unit;

use App\Services\JudicialCalendarService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JudicialCalendarServiceTest extends TestCase
{
    /**
     * Festivos oficiales de Colombia (Ley 51 de 1983).
     *
     * @return array<string, array{int, list<string>}>
     */
    public static function officialHolidays(): array
    {
        return [
            '2026' => [2026, [
                '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-01',
                '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29', '2026-07-20', '2026-08-07',
                '2026-08-17', '2026-10-12', '2026-11-02', '2026-11-16', '2026-12-08', '2026-12-25',
            ]],
            '2027' => [2027, [
                '2027-01-01', '2027-01-11', '2027-03-22', '2027-03-25', '2027-03-26', '2027-05-01',
                '2027-05-10', '2027-05-31', '2027-06-07', '2027-07-05', '2027-07-20', '2027-08-07',
                '2027-08-16', '2027-10-18', '2027-11-01', '2027-11-15', '2027-12-08', '2027-12-25',
            ]],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('officialHolidays')]
    public function test_holidays_match_the_official_calendar(int $year, array $expected): void
    {
        $holidays = (new JudicialCalendarService)->getHolidaysForYear($year);
        sort($holidays);

        $this->assertSame($expected, $holidays);
    }

    /**
     * Casos que el analisis de factibilidad encontro mal calculados.
     *
     * @return array<string, array{string, int, string}>
     */
    public static function deadlines(): array
    {
        return [
            '3 dias desde 20-may-2026 (antes daba 26-may)' => ['2026-05-20', 3, '2026-05-25'],
            '10 dias desde 11-jun-2026 (antes daba 30-jun)' => ['2026-06-11', 10, '2026-06-26'],
            '10 dias desde 17-abr-2026 (antes daba 1-may, festivo)' => ['2026-04-17', 10, '2026-05-04'],
            '5 dias desde 5-jun-2026 (antes daba 12-jun)' => ['2026-06-05', 5, '2026-06-16'],
        ];
    }

    #[DataProvider('deadlines')]
    public function test_business_day_deadlines(string $start, int $days, string $expected): void
    {
        $result = (new JudicialCalendarService)->calculateDeadline(Carbon::parse($start), $days);

        $this->assertSame($expected, $result['deadline']->format('Y-m-d'));
    }

    public function test_judicial_vacations_follow_decree_546_of_1971(): void
    {
        $calendar = new JudicialCalendarService;

        // Fin de año: del 20 de diciembre al 10 de enero inclusive.
        $this->assertTrue($calendar->isBusinessDay(Carbon::parse('2026-12-18')), 'El viernes 18 de diciembre es habil.');
        $this->assertTrue($calendar->isJudicialVacation(Carbon::parse('2026-12-21')));
        $this->assertTrue($calendar->isJudicialVacation(Carbon::parse('2027-01-08')));
        $this->assertTrue($calendar->isBusinessDay(Carbon::parse('2028-01-11')), 'El martes 11 de enero de 2028 es habil.');

        // Semana Santa 2026 (Pascua el 5 de abril): del lunes santo al domingo de resurreccion.
        foreach (['2026-03-30', '2026-03-31', '2026-04-01'] as $holyDay) {
            $this->assertFalse($calendar->isBusinessDay(Carbon::parse($holyDay)), "{$holyDay} es de Semana Santa.");
        }
        $this->assertTrue($calendar->isBusinessDay(Carbon::parse('2026-03-27')));
        $this->assertTrue($calendar->isBusinessDay(Carbon::parse('2026-04-06')));
    }
}
