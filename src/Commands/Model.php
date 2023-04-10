<?php

namespace Gizburdt\Cook\Commands;

class Model extends PublishCommand
{
    protected $signature = 'cook:model {--force}';

    protected $description = 'Publish Model and stuff';

    protected $publish = [
        'Models/Model.php' => 'app/Models',
    ];

    protected function after()
    {
        // @todo: remove `use Illimunate\Database\Eloquent\Model;` from models
    }
}
