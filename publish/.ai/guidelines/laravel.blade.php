# Laravel

## Console
- When a `php artisan migrate:fresh` is needed, always run with `--seed`

## Models
- Put the casts method on top of model classes
- Use SoftDeletes for all models, unless there is a specific reason not to
- Don't use $fillable in models, since we set `protected $guarded = [];` in the base model
- Don't use `\Illuminate\Database\Eloquent\Model` to extend from, there is a custom `App\Models\Model` class that all models should extend from
- Use `#[Scope]` when adding scopes to models

## Enums
- Put enums in the `App\Enums` namespace
- Use translation method `__()` for the label
- Use UPPER_SNAKE_CASE for enum cases

## Migrations
- Use datetime columns (instead of timestamp)
- Put relationship columns at the start, after `id`, `uuid` and `hash`

## Observers
- Put observers in the `App\Observers` namespace and use `ObservedBy` to register it to the model

## Queries
- When building a query, use `::query()` to start the query before calling other methods

## Translations
- Use `__('key')` and @lang('key') for translations
- Put dot notated keys in lang/{locale}/{file}.php files, when contains "enum", put in lang/{locale}/enums.php
- Put the rest of the translations the rest in {locale}.json
