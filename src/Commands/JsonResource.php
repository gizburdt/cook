<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;

class JsonResource extends Command
{
    protected $signature = 'cook:base-json-resource';

    protected $description = 'Command description';

    public function handle()
    {
        //

        return Command::SUCCESS;
    }
}
