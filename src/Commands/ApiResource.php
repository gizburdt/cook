<?php

namespace Gizburdt\Cook\Commands;

class ApiResource extends PublishCommand
{
    protected $signature = 'cook:api-resource {--force}';

    protected $description = 'Publish the base API resource';

    protected $publish = [
        'Http/Resources/Resource.php' => "app/Http/Resources",
    ];
}
