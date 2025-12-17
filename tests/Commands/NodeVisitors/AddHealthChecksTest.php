<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddHealthChecks;

it('adds health checks method to app service provider', function () {
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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('protected function healthChecks(): void')
        ->toContain('Health::checks([')
        ->toContain('CacheCheck::new()')
        ->toContain('CpuLoadCheck::new()')
        ->toContain('DatabaseConnectionCountCheck::new()')
        ->toContain('DatabaseCheck::new()')
        ->toContain('DatabaseSizeCheck::new()')
        ->toContain('DebugModeCheck::new()')
        ->toContain('EnvironmentCheck::new()')
        ->toContain('HorizonCheck::new()')
        ->toContain('OptimizedAppCheck::new()')
        ->toContain('QueueCheck::new()')
        ->toContain('RedisCheck::new()')
        ->toContain('RedisMemoryUsageCheck::new()')
        ->toContain('ScheduleCheck::new()')
        ->toContain('SecurityAdvisoriesCheck::new()')
        ->toContain('UsedDiskSpaceCheck::new()');
});

it('adds health checks call to boot method', function () {
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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('$this->healthChecks()');
});

it('adds missing use statements for health checks in non-namespaced files', function () {
    $parser = createPhpParserHelper();

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
        ->toContain('use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck')
        ->toContain('use Spatie\Health\Checks\Checks\DatabaseSizeCheck')
        ->toContain('use Spatie\Health\Checks\Checks\DebugModeCheck')
        ->toContain('use Spatie\Health\Checks\Checks\EnvironmentCheck')
        ->toContain('use Spatie\Health\Checks\Checks\HorizonCheck')
        ->toContain('use Spatie\Health\Checks\Checks\OptimizedAppCheck')
        ->toContain('use Spatie\Health\Checks\Checks\QueueCheck')
        ->toContain('use Spatie\Health\Checks\Checks\RedisCheck')
        ->toContain('use Spatie\Health\Checks\Checks\RedisMemoryUsageCheck')
        ->toContain('use Spatie\Health\Checks\Checks\ScheduleCheck')
        ->toContain('use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck')
        ->toContain('use Spatie\CpuLoadHealthCheck\CpuLoadCheck')
        ->toContain('use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck');
});

it('adds use statements in namespaced files', function () {
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
        AddHealthChecks::class,
    ]);

    expect($result)
        ->toContain('use Spatie\Health\Facades\Health')
        ->toContain('use Spatie\Health\Checks\Checks\CacheCheck')
        ->toContain('use Spatie\Health\Checks\Checks\DatabaseCheck')
        ->toContain('use Spatie\Health\Checks\Checks\HorizonCheck')
        ->toContain('use Spatie\Health\Checks\Checks\ScheduleCheck');
});

it('does not duplicate health checks method if it already exists', function () {
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
    $parser = createPhpParserHelper();

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
    $parser = createPhpParserHelper();

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

    expect(substr_count($result, 'use Spatie\Health\Facades\Health'))->toBe(1)
        ->and(substr_count($result, 'use Spatie\Health\Checks\Checks\CacheCheck'))->toBe(1);
});

it('preserves existing boot method content', function () {
    $parser = createPhpParserHelper();

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

it('formats health checks array with each check on new line', function () {
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
        AddHealthChecks::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check that each health check is on a new line in the array
    expect($result)
        ->toMatch('/Health::checks\(\[[\s]+CacheCheck::new\(\),[\s]+CpuLoadCheck::new\(\)/s')
        ->toMatch('/DatabaseCheck::new\(\),[\s]+DatabaseSizeCheck::new\(\)/s')
        ->toMatch('/DebugModeCheck::new\(\),[\s]+EnvironmentCheck::new\(\)/s')
        ->toMatch('/HorizonCheck::new\(\),[\s]+OptimizedAppCheck::new\(\)/s')
        ->toMatch('/QueueCheck::new\(\),[\s]+RedisCheck::new\(\)/s')
        ->toMatch('/RedisCheck::new\(\),[\s]+RedisMemoryUsageCheck::new\(\)/s')
        ->toMatch('/ScheduleCheck::new\(\)[\s]+->heartbeatMaxAgeInMinutes\(2\),[\s]+SecurityAdvisoriesCheck::new\(\)/s')
        ->toMatch('/SecurityAdvisoriesCheck::new\(\),[\s]+UsedDiskSpaceCheck::new\(\)/s');
});

it('formats method chains on new lines within health checks', function () {
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
        AddHealthChecks::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check that method chains are on new lines
    expect($result)
        ->toMatch('/CpuLoadCheck::new\(\)[\s]+->failWhenLoadIsHigherInTheLast5Minutes\(2\.0\)[\s]+->failWhenLoadIsHigherInTheLast15Minutes\(1\.5\)/s')
        ->toMatch('/DatabaseConnectionCountCheck::new\(\)[\s]+->warnWhenMoreConnectionsThan\(50\)[\s]+->failWhenMoreConnectionsThan\(100\)/s')
        ->toMatch('/RedisMemoryUsageCheck::new\(\)[\s]+->warnWhenAboveMb\(900\)[\s]+->failWhenAboveMb\(1000\)/s')
        ->toMatch('/ScheduleCheck::new\(\)[\s]+->heartbeatMaxAgeInMinutes\(2\)/s');
});

it('indents health checks array items and method chains correctly', function () {
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
        AddHealthChecks::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/Health::checks\(\[\n {12}CacheCheck::new\(\)/s')
        ->toMatch('/\n {12}DatabaseCheck::new\(\)/s')
        ->toMatch('/\n {12}RedisCheck::new\(\)/s')
        ->toMatch('/\n {12}HorizonCheck::new\(\)/s')
        ->toMatch('/\n {12}CpuLoadCheck::new\(\)\n {16}->failWhenLoadIsHigherInTheLast5Minutes/s')
        ->toMatch('/\n {16}->failWhenLoadIsHigherInTheLast15Minutes/s')
        ->toMatch('/\n {12}DatabaseConnectionCountCheck::new\(\)\n {16}->warnWhenMoreConnectionsThan/s')
        ->toMatch('/\n {16}->failWhenMoreConnectionsThan/s')
        ->toMatch('/\n {12}RedisMemoryUsageCheck::new\(\)\n {16}->warnWhenAboveMb/s')
        ->toMatch('/\n {16}->failWhenAboveMb/s')
        ->toMatch('/\n {12}ScheduleCheck::new\(\)\n {16}->heartbeatMaxAgeInMinutes/s');
});

it('adds one blank line between methods', function () {
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

    public function existingMethod(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthChecks::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check for one blank line between existingMethod() and healthChecks() methods
    expect($result)
        ->toMatch('/existingMethod\(\): void[\s]*\{[\s]*\/\/[\s]*\}[\s]*\n[\s]*\n[\s]*protected function healthChecks\(\)/s');
});

it('adds health checks method call at the bottom of boot method', function () {
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
        AddHealthChecks::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\$this->existingCall\(\);.*\$this->healthChecks\(\);[\s]*\}/s');
});
