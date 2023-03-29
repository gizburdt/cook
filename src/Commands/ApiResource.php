<?php

namespace Gizburdt\Cook\Commands;

class ApiResource extends GenerateCommand
{
    protected $signature = 'cook:api-resource {--force}';

    protected $description = 'Create a base API resource';

    protected $subject = 'API Resrource';

    protected $file = 'Resource.php';

    protected $folder = 'app/Http/Resources';

    protected $stub = 'api-resource';
}
