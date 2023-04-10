<?php

namespace Gizburdt\Cook\Commands;

class Controller extends PublishCommand
{
    protected $signature = 'cook:controller {--force}';

    protected $description = 'Publish Controller and stuff';

    protected $publish = [
        'Http/Controllers/Controller.php' => 'app/Http/Controllers',
    ];
}
