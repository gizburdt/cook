<?php

namespace Gizburdt\Cook\Commands;

class Traits extends PublishCommand
{
    protected $signature = 'cook:traits {--force}';

    protected $description = 'Publish traits to app/Traits';

    protected $publish = [
        'Traits/FilterableByDates.php' => "app/Traits",
    ];
}
