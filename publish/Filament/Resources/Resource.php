<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource as FilamentResource;
use Illuminate\Database\Eloquent\Builder;

class Resource extends FilamentResource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([]);
    }
}
