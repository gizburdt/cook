<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    protected $guarded = [];

    public function scopeWhereLike($query, $column, $value)
    {
        return $query->where($column, 'like', "%{$value}%");
    }
}
