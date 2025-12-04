<?php

namespace App\Models\Concerns;

trait HasHash
{
    protected static function bootHasHash(): void
    {
        static::creating(function ($model) {
            $model->hash = uniqid();
        });
    }
}
