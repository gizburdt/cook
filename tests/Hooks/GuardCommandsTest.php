<?php

it('decides on bash commands', function (string $command, string $decision) {
    expect(runHook('guard-commands', ['tool_input' => ['command' => $command]]))
        ->decision->toBe($decision);
})->with([
    'migrate:fresh without seed' => ['php artisan migrate:fresh', 'deny'],
    'migrate:fresh with seed' => ['php artisan migrate:fresh --seed', 'allow'],
    'seed in another command' => ['php artisan migrate:fresh && php artisan db:seed --seed', 'deny'],
    'no-verify on commit' => ['git commit -m wip --no-verify', 'deny'],
    'no-verify outside git' => ['grep -r -- --no-verify docs', 'allow'],
    'make without no-interaction' => ['php artisan make:model Foo', 'deny'],
    'make with no-interaction' => ['php artisan make:model Foo --no-interaction', 'allow'],
    'make with -n' => ['php artisan make:test FooTest -n', 'allow'],
    '-n in another command' => ['grep -n foo app && php artisan make:model Foo', 'deny'],
    'force push' => ['git push --force origin main', 'ask'],
    'force push short' => ['git push -f', 'ask'],
    'force push with lease' => ['git push --force-with-lease', 'ask'],
    'force push refspec' => ['git push origin +main', 'ask'],
    'normal push' => ['git push -u origin feature-fix', 'allow'],
    'push follow tags' => ['git push --follow-tags', 'allow'],
    'composer require' => ['composer require spatie/foo', 'ask'],
    'composer remove' => ['composer remove spatie/foo', 'ask'],
    'composer install' => ['composer install', 'allow'],
    'npm install package' => ['npm install lodash', 'ask'],
    'npm install dev package' => ['npm i -D lodash', 'ask'],
    'npm install' => ['npm install', 'allow'],
    'npm install flags' => ['npm install --no-audit --no-fund', 'allow'],
    'yarn add' => ['yarn add lodash', 'ask'],
    'deny wins over ask' => ['composer require foo && php artisan migrate:fresh', 'deny'],
    'tests' => ['php artisan test', 'allow'],
]);
