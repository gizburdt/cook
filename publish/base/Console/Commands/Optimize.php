<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

class Optimize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extra optimize/warm';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (class_exists(ScheduleCheckHeartbeatCommand::class)) {
            $this->call(ScheduleCheckHeartbeatCommand::class);
        }
    }
}
