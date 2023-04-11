<?php

namespace Gizburdt\Cook\Commands;

class Model extends Command
{
    protected $signature = 'cook:model';

    protected $description = 'Publish Model and stuff';

    public function handle()
    {
        // @todo: remove `use Illimunate\Database\Eloquent\Model;` from models
    }
}
