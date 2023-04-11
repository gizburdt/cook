<?php

namespace Gizburdt\Cook\Commands;

class ShiftBlueprint extends Command
{
    protected $signature = 'cook:shift-blueprint {--force}';

    protected $description = 'Publish draft.yaml';

    protected $publish = [
        'draft.yaml' => '/',
    ];

    public function handle()
    {
        //
    }
}
