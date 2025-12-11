<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthRoute;

it('adds health route to web routes file', function () {
    $parser = createAddHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthRoute::class,
    ]);

    expect($result)
        ->toContain("Route::get('health', HealthCheckJsonResultsController::class)");
});

it('adds health controller use statement', function () {
    $parser = createAddHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthRoute::class,
    ]);

    expect($result)
        ->toContain('use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController');
});

it('does not add route if it already exists', function () {
    $parser = createAddHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('health', HealthCheckJsonResultsController::class);
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthRoute::class,
    ]);

    expect(substr_count($result, "Route::get('health'"))
        ->toBe(1);
});

it('does not add use statement if it already exists', function () {
    $parser = createAddHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::get('/', function () {
    return view('welcome');
});
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthRoute::class,
    ]);

    expect(substr_count($result, 'use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController'))
        ->toBe(1);
});

it('preserves existing routes when adding health route', function () {
    $parser = createAddHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthRoute::class,
    ]);

    expect($result)
        ->toContain("Route::get('/'")
        ->toContain("Route::get('/dashboard'")
        ->toContain("Route::get('health'");
});

it('adds both use statement and route when neither exists', function () {
    $parser = createAddHealthRouteParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthRoute::class,
    ]);

    expect($result)
        ->toContain('use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController')
        ->toContain("Route::get('health', HealthCheckJsonResultsController::class)");
});

function createAddHealthRouteParser(): object
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
