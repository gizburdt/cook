<?php

namespace Gizburdt\Cook\Commands;

class ShiftBlueprint extends GenerateCommand
{
    protected $signature = 'cook:shift-blueprint {--force}';

    protected $description = 'Create a draft.yaml';

    protected $subject = 'Shift Blueprint';

    protected $file = 'draft.yaml';

    protected $folder = '/';

    protected $stub = 'shift-blueprint';
}
