<?php

namespace App\Filament\Actions;

use Filament\Actions\DetachAction;
use Filament\Support\Icons\Heroicon;

class SimpleDetachAction extends DetachAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon = Heroicon::OutlinedXCircle;

        $this->iconButton();
    }
}
