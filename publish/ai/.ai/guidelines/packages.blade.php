# Packages

## TimoKoerber/laravel-one-time-operations

This package is used for parsing, converting, or migrating data in the database. These classes are executed only once, as the database keeps track of which ones have already been run

- Prefer setting `$async` to `false`, unless a large amount of code is being processed in a loop, for example
- To test an operation, you can use `operations:process --test`
- To run a single operation, use `php artisan operations:process [name]`
