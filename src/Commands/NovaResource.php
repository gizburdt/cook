<?php

namespace Gizburdt\Cook\Commands;

class NovaResource extends PublishCommand
{
    protected $signature = 'cook:nova-resource {--force}';

    protected $description = 'Publish the base Nova resource';

    protected $publish = [
        'Nova/Resource.php' => "app/Nova",
    ];
}
