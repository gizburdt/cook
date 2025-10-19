<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\Relation;

class Model extends EloquentModel
{
    protected $guarded = [];

    public static function morphAlias(): string
    {
        return Relation::getMorphAlias(static::class);
    }
}
