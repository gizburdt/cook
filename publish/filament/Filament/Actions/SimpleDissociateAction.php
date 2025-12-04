<?php

namespace App\Filament\Actions;

use Filament\Actions\DissociateAction;
use Filament\Support\Icons\Heroicon;

class SimpleDissociateAction extends DissociateAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon = Heroicon::OutlinedXCircle;

        $this->iconButton();
    }
}
