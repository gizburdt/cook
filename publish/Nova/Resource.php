<?php

namespace App\Nova;

use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource as NovaResource;

abstract class Resource extends NovaResource
{
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
            (new \ReflectionClass(static::$model))->getShortName()
        ));
    }

    public static function singularLabel(): string
    {
        return __(Str::singular(
            (new \ReflectionClass(static::$model))->getShortName()
        ));
    }

    public static function group(): string
    {
        return __(static::$group);
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

    protected function relation($title, $relation = null, $class = null): array
    {
        $class = $class ?: 'App\\Nova\\'.Str::of($title)->singular()->studly();

        return [__($title), ($relation ?: Str::camel($title)), $class];
    }

    protected function name($title, $name = null): array
    {
        return [__($title), ($name ?: Str::slug($title, '_'))];
    }

    protected function url($url, $text = null): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $string = '<a href="%s" class="link-default">%s</a>';

            return sprintf($string, $url, $text ?: $url);
        }

        return $url;
    }

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

    /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    */

    public static function detailQuery(NovaRequest $request, $query)
    {
        return parent::detailQuery($request, $query);
    }

    public static function relatableQuery(NovaRequest $request, $query)
    {
        return parent::relatableQuery($request, $query);
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    public static function scoutQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Extend
    |--------------------------------------------------------------------------
    */

    public function cards(NovaRequest $request)
    {
        return [];
    }

    public function filters(NovaRequest $request)
    {
        return [];
    }

    public function lenses(NovaRequest $request)
    {
        return [];
    }

    public function actions(NovaRequest $request)
    {
        return [];
    }
}
