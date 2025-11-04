<?php

namespace App\Filament\Actions;

use Filament\Actions\EditAction;

class SimpleEditAction extends EditAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('');

        $this->size('lg');
    }
}
