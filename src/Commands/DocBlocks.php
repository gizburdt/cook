<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;

class DocBlocks extends Command
{
    protected $signature = 'burn:doc-blocks';

    protected $description = 'Remove all multiline comments';

    public function handle()
    {
        //

        return Command::SUCCESS;
    }
}
