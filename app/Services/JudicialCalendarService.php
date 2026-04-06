<?php

namespace App\Services;

use Carbon\Carbon;

class JudicialCalendarService
{
    /**
     * Festivos fijos de Colombia (Ley 51 de 1983).
     * Los que se trasladan al lunes siguiente estan marcados con 'transfer'.
     */
    private array $fixedHolidays = [
        ['month' => 1, 'day' => 1, 'transfer' => false],   // Año Nuevo
        ['month' => 5, 'day' => 1, 'transfer' => true],     // Dia del Trabajo
        ['month' => 7, 'day' => 20, 'transfer' => true],    // Grito de Independencia
        ['month' => 8, 'day' => 7, 'transfer' => false],    // Batalla de Boyaca
        ['month' => 12, 'day' => 8, 'transfer' => false],   // Inmaculada Concepcion
        ['month' => 12, 'day' => 25, 'transfer' => false],  // Navidad
        ['month' => 1, 'day' => 6, 'transfer' => true],     // Reyes Magos
        ['month' => 3, 'day' => 19, 'transfer' => true],    // San Jose
        ['month' => 6, 'day' => 29, 'transfer' => true],    // San Pedro y San Pablo
        ['month' => 8, 'day' => 15, 'transfer' => true],    // Asuncion de la Virgen
        ['month' => 10, 'day' => 12, 'transfer' => true],   // Dia de la Raza
        ['month' => 11, 'day' => 1, 'transfer' => true],    // Todos los Santos
        ['month' => 11, 'day' => 11, 'transfer' => true],   // Independencia de Cartagena
    ];

    public function getHolidaysForYear(int $year): array
    {
        $holidays = [];

        foreach ($this->fixedHolidays as $holiday) {
            $date = Carbon::create($year, $holiday['month'], $holiday['day']);

            if ($holiday['transfer'] && $date->dayOfWeek !== Carbon::MONDAY) {
                $date = $date->next(Carbon::MONDAY);
            }

            $holidays[] = $date->format('Y-m-d');
        }

        // Festivos movibles basados en Pascua
        $easter = $this->calculateEaster($year);
        $holidays[] = $easter->copy()->subDays(3)->format('Y-m-d');  // Jueves Santo
        $holidays[] = $easter->copy()->subDays(2)->format('Y-m-d');  // Viernes Santo
        $holidays[] = $easter->copy()->addDays(43)->next(Carbon::MONDAY)->format('Y-m-d'); // Ascension (lunes)
        $holidays[] = $easter->copy()->addDays(64)->next(Carbon::MONDAY)->format('Y-m-d'); // Corpus Christi (lunes)
        $holidays[] = $easter->copy()->addDays(71)->next(Carbon::MONDAY)->format('Y-m-d'); // Sagrado Corazon (lunes)

        return $holidays;
    }

    private function calculateEaster(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }

    public function isJudicialVacation(Carbon $date): bool
    {
        $month = $date->month;
        $day = $date->day;

        // Vacancia judicial: Diciembre 20 a Enero 11
        if ($month === 12 && $day >= 20) {
            return true;
        }
        if ($month === 1 && $day <= 11) {
            return true;
        }

        // Semana Santa (jueves a domingo)
        $easter = $this->calculateEaster($date->year);
        $holyThursday = $easter->copy()->subDays(3);
        $easterSunday = $easter->copy();

        if ($date->between($holyThursday, $easterSunday)) {
            return true;
        }

        return false;
    }

    public function isHoliday(Carbon $date): bool
    {
        $holidays = $this->getHolidaysForYear($date->year);

        return in_array($date->format('Y-m-d'), $holidays);
    }

    public function isBusinessDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        if ($this->isHoliday($date)) {
            return false;
        }

        if ($this->isJudicialVacation($date)) {
            return false;
        }

        return true;
    }

    /**
     * Calcular fecha de vencimiento.
     *
     * @param  string  $type  'business' (habiles), 'calendar' (calendario), 'months' (meses)
     */
    public function calculateDeadline(Carbon $startDate, int $term, string $type = 'business'): array
    {
        $current = $startDate->copy();
        $daysCount = 0;
        $skippedDays = [];

        if ($type === 'months') {
            $deadline = $current->addMonths($term);

            return [
                'start_date' => $startDate,
                'deadline' => $deadline,
                'business_days' => 0,
                'calendar_days' => $startDate->diffInDays($deadline),
                'skipped_days' => [],
                'type' => $type,
            ];
        }

        if ($type === 'calendar') {
            $deadline = $current->addDays($term);

            return [
                'start_date' => $startDate,
                'deadline' => $deadline,
                'business_days' => 0,
                'calendar_days' => $term,
                'skipped_days' => [],
                'type' => $type,
            ];
        }

        // Dias habiles
        while ($daysCount < $term) {
            $current->addDay();

            if ($this->isBusinessDay($current)) {
                $daysCount++;
            } else {
                $reason = 'Fin de semana';
                if ($this->isHoliday($current)) {
                    $reason = 'Festivo';
                } elseif ($this->isJudicialVacation($current)) {
                    $reason = 'Vacancia judicial';
                }

                $skippedDays[] = [
                    'date' => $current->format('Y-m-d'),
                    'reason' => $reason,
                ];
            }
        }

        return [
            'start_date' => $startDate,
            'deadline' => $current,
            'business_days' => $term,
            'calendar_days' => $startDate->diffInDays($current),
            'skipped_days' => $skippedDays,
            'type' => $type,
        ];
    }
}
