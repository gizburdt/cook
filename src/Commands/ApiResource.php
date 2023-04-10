<?php

namespace Gizburdt\Cook\Commands;

class ApiResource extends PublishCommand
{
    protected $signature = 'cook:api-resource {--force}';

    protected $description = 'Publish API resource and stuff';

    protected $publish = [
        'Http/Resources/Resource.php' => 'app/Http/Resources',
    ];
}
