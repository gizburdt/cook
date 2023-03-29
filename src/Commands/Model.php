<?php

namespace Gizburdt\Cook\Commands;

class Model extends GenerateCommand
{
    protected $signature = 'cook:model {--force}';

    protected $description = 'Create a base Model';

    protected $subject = 'Model.php';

    protected $file = 'Model.php';

    protected $folder = 'app/Models';

    protected $stub = 'model';
}
