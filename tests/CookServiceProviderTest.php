<?php

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\Base;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Operations;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Publish;
use Gizburdt\Cook\Commands\Ui;
use Gizburdt\Cook\CookServiceProvider;

it('extends service provider', function () {
    expect(CookServiceProvider::class)
        ->toExtend(Illuminate\Support\ServiceProvider::class);
});

it('has register method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('register');
});

it('has boot method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('boot');
});

it('has protected methods for publishable groups', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('operations')
        ->toHaveMethod('base')
        ->toHaveMethod('codeQuality')
        ->toHaveMethod('ai')
        ->toHaveMethod('filament')
        ->toHaveMethod('files');
});

it('has all command classes available', function () {
    expect(Install::class)->toBeString()
        ->and(Publish::class)->toBeString()
        ->and(Operations::class)->toBeString()
        ->and(Base::class)->toBeString()
        ->and(CodeQuality::class)->toBeString()
        ->and(Ai::class)->toBeString()
        ->and(Filament::class)->toBeString()
        ->and(Ui::class)->toBeString()
        ->and(Packages::class)->toBeString()
        ->and(Backups::class)->toBeString();
});

it('has operations publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/operations';

    expect(file_exists($basePath.'/config/one-time-operations.php'))->toBeTrue()
        ->and(file_exists($basePath.'/stubs/one-time-operation.stub'))->toBeTrue();
});

it('has base publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/base';

    expect(is_dir($basePath.'/stubs'))->toBeTrue()
        ->and(file_exists($basePath.'/Http/Resources/Resource.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Models/Model.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Models/Pivot.php'))->toBeTrue()
        ->and(is_dir($basePath.'/Models/Concerns'))->toBeTrue()
        ->and(file_exists($basePath.'/Policies/Policy.php'))->toBeTrue();
});

it('has code quality publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/code-quality';

    expect(is_dir($basePath.'/.github'))->toBeTrue()
        ->and(file_exists($basePath.'/config/essentials.php'))->toBeTrue()
        ->and(file_exists($basePath.'/config/insights.php'))->toBeTrue()
        ->and(file_exists($basePath.'/phpstan.neon'))->toBeTrue()
        ->and(file_exists($basePath.'/pint.json'))->toBeTrue()
        ->and(file_exists($basePath.'/rector.php'))->toBeTrue();
});

it('has ai publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/ai';

    expect(is_dir($basePath.'/.ai'))->toBeTrue()
        ->and(is_dir($basePath.'/.claude'))->toBeTrue();
});

it('has filament publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/filament';

    expect(is_dir($basePath.'/Filament'))->toBeTrue();
});
