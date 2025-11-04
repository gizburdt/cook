<?php

namespace App\Filament\Actions;

use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;

class SimpleDeleteAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon = Heroicon::OutlinedTrash;

        $this->label('');

        $this->size('lg');
    }
}
