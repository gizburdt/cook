<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddCarbonMacros;

it('adds carbonMacros method to app service provider', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('protected function carbonMacros(): void')
        ->toContain('$display = function () {')
        ->toContain("Carbon::macro('display', \$display);")
        ->toContain("CarbonImmutable::macro('display', \$display);");
});

it('appends carbonMacros method when passwordRules does not exist', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('protected function carbonMacros(): void');
});

it('adds Carbon and CarbonImmutable use statements', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('use Carbon\Carbon;')
        ->toContain('use Carbon\CarbonImmutable;');
});

it('does not duplicate Carbon use statements if they already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect(substr_count($result, 'use Carbon\Carbon;'))->toBe(1)
        ->and(substr_count($result, 'use Carbon\CarbonImmutable;'))->toBe(1);
});

it('adds carbonMacros method call to boot method', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('$this->carbonMacros();');
});

it('adds carbonMacros method call at the bottom of boot method', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\$this->existingCall\(\);.*\$this->carbonMacros\(\);[\s]*\}/s');
});

it('does not duplicate carbonMacros method if it already exists', function () {
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

    protected function carbonMacros(): void
    {
        // existing
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect(substr_count($result, 'protected function carbonMacros()'))
        ->toBe(1);
});

it('formats carbonMacros body with blank lines between statements', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch("/};\s*\n\s*\n\s*Carbon::macro\('display', \\\$display\);/s")
        ->toMatch("/Carbon::macro\('display', \\\$display\);\s*\n\s*\n\s*CarbonImmutable::macro\('display', \\\$display\);/s");
});

it('includes Carbon|CarbonImmutable phpdoc in closure', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('/** @var Carbon|CarbonImmutable $this */');
});

it('returns this copy with display timezone in closure', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain("return \$this->copy()->timezone(config('app.timezone'));");
});

it('adds blank line above carbonMacros method', function () {
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
        AddCarbonMacros::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\}\n\n {4}protected function carbonMacros\(\)/s');
});
