<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;

class BaseModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cook:base-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //

        return Command::SUCCESS;
    }
}
