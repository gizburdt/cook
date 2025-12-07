<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Jobs\FailingJob;

class FailedJob extends Command
{
    protected $signature = 'cook:failed-job';

    protected $description = 'Dispatch a failing job';

    public function handle(): void
    {
        FailingJob::dispatch();

        $this->components->info('Failing job dispatched');
    }
}
