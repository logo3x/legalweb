<?php

namespace App\Jobs;

use App\Models\LegalCase;
use App\Services\CaseSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCaseActuaciones implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 2;

    public function __construct(
        public LegalCase $case,
        public string $origen = 'automatico',
    ) {}

    public function handle(CaseSyncService $sync): void
    {
        if (! $this->case->external_case_number) {
            return;
        }

        $sync->sync($this->case, $this->origen);
    }
}
