<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\RemoveHealthRoute;

it('removes health parameter from withRouting', function () {
    $parser = createRemoveHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function(Middleware $middleware) {
        //
    })
    ->withExceptions(function(Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        RemoveHealthRoute::class,
    ]);

    expect($result)
        ->not->toContain("health: '/up'")
        ->toContain("web: __DIR__ . '/../routes/web.php'")
        ->toContain("commands: __DIR__ . '/../routes/console.php'");
});

it('preserves other parameters when removing health', function () {
    $parser = createRemoveHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function() {
            if (app()->environment('local')) {
                Route::middleware('web')->group(base_path('routes/local.php'));
            }
        },
    )
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        RemoveHealthRoute::class,
    ]);

    expect($result)
        ->not->toContain("health: '/up'")
        ->toContain("web: __DIR__ . '/../routes/web.php'")
        ->toContain("commands: __DIR__ . '/../routes/console.php'")
        ->toContain('then: function()');
});

it('does not modify file if health parameter does not exist', function () {
    $parser = createRemoveHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function(Middleware $middleware) {
        //
    })
    ->withExceptions(function(Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        RemoveHealthRoute::class,
    ]);

    expect($result)
        ->not->toContain('health:')
        ->toContain("web: __DIR__ . '/../routes/web.php'")
        ->toContain("commands: __DIR__ . '/../routes/console.php'");
});

it('handles health parameter in different positions', function () {
    $parser = createRemoveHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        RemoveHealthRoute::class,
    ]);

    expect($result)
        ->not->toContain("health: '/up'")
        ->toContain("web: __DIR__ . '/../routes/web.php'")
        ->toContain("commands: __DIR__ . '/../routes/console.php'");
});

function createRemoveHealthRouteParser(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors): string
        {
            return $this->parsePhpContent($content, $visitors);
        }
    };
}
