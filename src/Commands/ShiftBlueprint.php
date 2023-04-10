<?php

namespace Gizburdt\Cook\Commands;

class ShiftBlueprint extends PublishCommand
{
    protected $signature = 'cook:shift-blueprint {--force}';

    protected $description = 'Publish draft.yaml';

    protected $publish = [
        'draft.yaml' => '/',
    ];
}
