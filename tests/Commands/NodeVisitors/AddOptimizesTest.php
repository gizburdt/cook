<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddOptimizes;

it('adds optimize method to app service provider', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('protected function optimize(): void')
        ->toContain('$this->optimizes(')
        ->toContain('optimize: Optimize::class');
});

it('adds optimize use statement', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ]);

    expect($result)
        ->toContain('use App\Console\Commands\Optimize');
});

it('does not duplicate optimize method if it already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    protected function optimize(): void
    {
        // existing method
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ]);

    expect(substr_count($result, 'protected function optimize()'))
        ->toBe(1);
});

it('does not add optimize use statement if it already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use App\Console\Commands\Optimize;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ]);

    expect(substr_count($result, 'use App\Console\Commands\Optimize'))
        ->toBe(1);
});

it('adds blank line above optimize method', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\}\n\n {4}protected function optimize\(\)/s');
});

it('adds optimize method call to boot method', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('$this->optimize()');
});

it('adds optimize method call at the bottom of boot method', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->existingCall();
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddOptimizes::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\$this->existingCall\(\);.*\$this->optimize\(\);[\s]*\}/s');
});
