<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddAdminPanelProvider;

it('adds admin panel provider to bootstrap providers', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,

];
PHP;

    $result = $parser->testParseContent($content, [
        AddAdminPanelProvider::class,
    ], 'bootstrap/providers.php');

    expect($result)
        ->toContain('App\Providers\Filament\AdminPanelProvider::class');
});

it('does not duplicate admin panel provider if it already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

];
PHP;

    $result = $parser->testParseContent($content, [
        AddAdminPanelProvider::class,
    ], 'bootstrap/providers.php');

    expect(substr_count($result, 'App\Providers\Filament\AdminPanelProvider::class'))
        ->toBe(1);
});

it('adds admin panel provider at the end of the array', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    App\Providers\AppServiceProvider::class,

];
PHP;

    $result = $parser->testParseContent($content, [
        AddAdminPanelProvider::class,
    ], 'bootstrap/providers.php');

    expect($result)
        ->toMatch('/AppServiceProvider::class,\n\s+App\\\\Providers\\\\Filament\\\\AdminPanelProvider::class,/');
});
