<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddApiGuard;

it('adds an api passport guard when only web exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        AddApiGuard::class,
    ], 'config/auth.php');

    expect($result)
        ->toContain("'api' =>")
        ->toContain("'driver' => 'passport'")
        ->toContain("'provider' => 'users'");
});

it('is idempotent when a passport api guard exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        AddApiGuard::class,
    ], 'config/auth.php');

    expect(substr_count($result, "'api' =>"))->toBe(1)
        ->and(substr_count($result, "'driver' => 'passport'"))->toBe(1);
});

it('switches an existing sanctum api guard to passport', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    'guards' => [
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        AddApiGuard::class,
    ], 'config/auth.php');

    expect($result)
        ->toContain("'driver' => 'passport'")
        ->not->toContain("'driver' => 'sanctum'");
});
