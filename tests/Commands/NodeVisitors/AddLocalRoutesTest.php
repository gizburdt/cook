<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddLocalRoutes;

it('adds local routes to withRouting without then parameter', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toContain('then: function')
        ->toContain("app()->environment('local')")
        ->toContain("Route::middleware('web')->group(base_path('routes/local.php'))");
});

it('adds local routes to existing empty then closure', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {}
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toContain("app()->environment('local')")
        ->toContain("Route::middleware('web')->group(base_path('routes/local.php'))");
});

it('adds local routes to existing then closure with code', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::get('/custom', function () {
                return 'custom';
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toContain("Route::get('/custom'")
        ->toContain("app()->environment('local')")
        ->toContain("Route::middleware('web')->group(base_path('routes/local.php'))");
});

it('adds Route use statement when not present', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toContain('use Illuminate\Support\Facades\Route');
});

it('does not add Route use statement if already present', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect(substr_count($result, 'use Illuminate\Support\Facades\Route'))
        ->toBe(1);
});

it('does not add local routes if already present', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            if (app()->environment('local')) {
                Route::middleware('web')->group(base_path('routes/local.php'));
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect(substr_count($result, 'routes/local.php'))
        ->toBe(1);
});

it('adds use statement after existing use statements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toContain('use Illuminate\Foundation\Configuration\Middleware;')
        ->toContain('use Illuminate\Support\Facades\Route;')
        ->toMatch('/use Illuminate\\\Foundation\\\Configuration\\\Middleware;.*use Illuminate\\\Support\\\Facades\\\Route;/s');
});

it('preserves existing routes when adding local routes', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toContain('web:')
        ->toContain('routes/web.php')
        ->toContain('api:')
        ->toContain('routes/api.php')
        ->toContain('commands:')
        ->toContain('routes/console.php')
        ->toContain('then: function');
});

it('formats withRouting correctly when then parameter does not exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
PHP;

    $result = $parser->testParseContent($content, [
        AddLocalRoutes::class,
    ]);

    expect($result)
        ->toMatch('/->withRouting\(\n {8}web:/s')
        ->toMatch('/\n {8}commands:/s')
        ->toMatch('/\n {8}then: function \(\) \{\n {12}if \(app\(\)->environment\(\'local\'\)\) \{\n {16}Route::middleware\(\'web\'\)->group\(base_path\(\'routes\/local\.php\'\)\);\n {12}\}\n {8}\}/s');
});
