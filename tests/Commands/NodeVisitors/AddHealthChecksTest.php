<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthChecks;

it('adds health checks method to app service provider', function () {
    $parser = createAddHealthChecksParser();

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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('protected function healthChecks(): void')
        ->toContain('Health::checks([')
        ->toContain('CacheCheck::new()')
        ->toContain('DatabaseCheck::new()')
        ->toContain('RedisCheck::new()');
});

it('adds health checks call to boot method', function () {
    $parser = createAddHealthChecksParser();

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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('$this->healthChecks()');
});

it('adds missing use statements for health checks in non-namespaced files', function () {
    $parser = createAddHealthChecksParser();

    $content = <<<'PHP'
<?php

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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('use Spatie\Health\Facades\Health')
        ->toContain('use Spatie\Health\Checks\Checks\CacheCheck')
        ->toContain('use Spatie\Health\Checks\Checks\DatabaseCheck')
        ->toContain('use Spatie\Health\Checks\Checks\RedisCheck')
        ->toContain('use Spatie\CpuLoadHealthCheck\CpuLoadCheck')
        ->toContain('use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck');
});

it('adds use statements in namespaced files', function () {
    $parser = createAddHealthChecksParser();

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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('use Spatie\Health\Facades\Health')
        ->toContain('use Spatie\Health\Checks\Checks\CacheCheck')
        ->toContain('use Spatie\Health\Checks\Checks\DatabaseCheck');
});

it('does not duplicate health checks method if it already exists', function () {
    $parser = createAddHealthChecksParser();

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

    protected function healthChecks(): void
    {
        // existing method
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthChecks::class,
    ]);

    expect(substr_count($result, 'protected function healthChecks()'))
        ->toBe(1);
});

it('does not duplicate health checks call if it already exists in boot', function () {
    $parser = createAddHealthChecksParser();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->healthChecks();
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthChecks::class,
    ]);

    expect(substr_count($result, '$this->healthChecks()'))
        ->toBe(1);
});

it('does not add use statements that already exist', function () {
    $parser = createAddHealthChecksParser();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\CacheCheck;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthChecks::class,
    ]);

    expect(substr_count($result, 'use Spatie\Health\Facades\Health'))
        ->toBe(1)
        ->and(substr_count($result, 'use Spatie\Health\Checks\Checks\CacheCheck'))
        ->toBe(1);
});

it('preserves existing boot method content', function () {
    $parser = createAddHealthChecksParser();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('$this->healthChecks()')
        ->toContain('$this->registerPolicies()');
});

function createAddHealthChecksParser(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors): string
        {
            return $this->parseContent($content, $visitors);
        }
    };
}
