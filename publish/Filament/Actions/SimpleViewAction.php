<?php

namespace App\Filament\Actions;

use Filament\Actions\ViewAction;

class SimpleViewAction extends ViewAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('');

        $this->size('lg');
    }
}
