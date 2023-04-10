<?php

namespace Gizburdt\Cook\Commands;

class NovaResource extends PublishCommand
{
    protected $signature = 'cook:nova-resource {--force}';

    protected $description = 'Publish Nova Resource and stuff';

    protected $publish = [
        'Nova/Resource.php' => 'app/Nova',
    ];
}
