<?php

namespace Gizburdt\Cook\Commands;

class Traits extends MoveCommand
{
    protected $signature = 'cook:traits {--force}';

    protected $description = 'Move traits to app/Traits';

    protected $move = [
        'Traits/FilterableByDates.php' => "app/Traits",
    ];
}
