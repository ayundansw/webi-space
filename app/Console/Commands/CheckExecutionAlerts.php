<?php

namespace App\Console\Commands;

use App\Services\Execution\AlertService;
use Illuminate\Console\Command;

class CheckExecutionAlerts extends Command
{
    protected $signature = 'execution:check-alerts';

    protected $description = 'Compute Eksekusi monitoring flags and send first-occurrence notifications (OVERDUE, DUE SOON, STALLED, INACTIVE MEMBER).';

    public function handle(AlertService $alerts): int
    {
        $alerts->notifyNewAlerts();

        $this->info('Execution alerts checked and notifications sent.');

        return self::SUCCESS;
    }
}
