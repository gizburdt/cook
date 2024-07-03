<?php

namespace App\Nova;

use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource as NovaResource;
use ReflectionClass;

abstract class Resource extends NovaResource
{
    public static string $model;

    public static $tableStyle = 'tight';

    public static $showColumnBorders = true;

    public static $perPageOptions = [50, 100, 250, 500];

    public static $perPageViaRelationship = 50;

    /*
    |--------------------------------------------------------------------------
    | Resource
    |--------------------------------------------------------------------------
    */

    public static function label(): string
    {
        return __(Str::plural(
            (new ReflectionClass(static::$model))->getShortName()
        ));
    }

    public static function singularLabel(): string
    {
        return __(Str::singular(
            (new ReflectionClass(static::$model))->getShortName()
        ));
    }

    public static function softDeletes(): bool
    {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function storeUniqueOriginal($file)
    {
        $extension = $file->getClientOriginalExtension();

        $unique = time();

        return Str::of($file->getClientOriginalName())
            ->before('.')
            ->slug('-')
            ->append("-{$unique}")
            ->finish(".{$extension}");
    }
}
