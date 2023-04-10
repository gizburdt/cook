<?php

namespace Gizburdt\Cook\Commands;

class Traits extends PublishCommand
{
    protected $signature = 'cook:traits {--force}';

    protected $description = 'Publish Traits and stuff';

    protected $publish = [
        'Traits/FilterableByDates.php' => 'app/Traits',
    ];
}
