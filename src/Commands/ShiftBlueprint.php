<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;

class ShiftBlueprint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cook:shift-blueprint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'New shift blueprint';

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
