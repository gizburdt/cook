<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;

class DocBlocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'burn:doc-blocks';

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
