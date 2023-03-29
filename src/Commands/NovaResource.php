<?php

namespace Gizburdt\Cook\Commands;

class NovaResource extends GenerateCommand
{
    protected $signature = 'cook:nova-resource {--force}';

    protected $description = 'Create a base Nova resource';

    protected $subject = 'Resource.php';

    protected $file = 'Resource.php';

    protected $folder = 'app/Nova';

    protected $stub = 'nova-resource';
}
