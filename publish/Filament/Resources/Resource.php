<?php

namespace App\Filament\Resources;

use App\Filament\Resource as FilamentResource;
use Illuminate\Database\Eloquent\Builder;

class Resource extends FilamentResource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([]);
    }
}
