<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddPasswordRules;

it('adds password rules method to app service provider', function () {
    $parser = createAddPasswordRulesParser();

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
        AddPasswordRules::class,
    ]);

    expect($result)
        ->toContain('protected function passwordRules(): void')
        ->toContain('Password::defaults(function')
        ->toContain('return Password::min(8)')
        ->toContain('->mixedCase()')
        ->toContain('->numbers()')
        ->toContain('->symbols()');
});

it('formats password rules with each method call on new line', function () {
    $parser = createAddPasswordRulesParser();

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
        AddPasswordRules::class,
    ]);

    // Verify multiline formatting by checking that -> appears at start of lines (with indentation)
    expect($result)
        ->toMatch('/return Password::min\(8\)\s+->mixedCase\(\)\s+->numbers\(\)\s+->symbols\(\)/s');
});

it('adds password use statement', function () {
    $parser = createAddPasswordRulesParser();

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
        AddPasswordRules::class,
    ]);

    expect($result)
        ->toContain('use Illuminate\Validation\Rules\Password');
});

it('does not duplicate password rules method if it already exists', function () {
    $parser = createAddPasswordRulesParser();

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

    protected function passwordRules(): void
    {
        // existing method
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddPasswordRules::class,
    ]);

    expect(substr_count($result, 'protected function passwordRules()'))
        ->toBe(1);
});

it('does not add password use statement if it already exists', function () {
    $parser = createAddPasswordRulesParser();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddPasswordRules::class,
    ]);

    expect(substr_count($result, 'use Illuminate\Validation\Rules\Password'))
        ->toBe(1);
});

function createAddPasswordRulesParser(): object
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
