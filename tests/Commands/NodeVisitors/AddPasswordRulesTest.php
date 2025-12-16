<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddPasswordRules;

it('adds password rules method to app service provider', function () {
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
        AddPasswordRules::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('protected function passwordRules(): void')
        ->toContain('Password::defaults(function')
        ->toContain('return Password::min(8)')
        ->toContain('->mixedCase()')
        ->toContain('->numbers()')
        ->toContain('->symbols()');
});

it('formats password rules with each method call on new line', function () {
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
        AddPasswordRules::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Verify multiline formatting by checking that -> appears at start of lines (with indentation)
    expect($result)
        ->toMatch('/return Password::min\(8\)\s+->mixedCase\(\)\s+->numbers\(\)\s+->symbols\(\)/s');
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
        AddPasswordRules::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check for one blank line between existingMethod() and passwordRules() methods
    expect($result)
        ->toMatch('/existingMethod\(\): void[\s]*\{[\s]*\/\/[\s]*\}[\s]*\n[\s]*\n[\s]*protected function passwordRules\(\)/s');
});

it('adds password use statement', function () {
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
        AddPasswordRules::class,
    ]);

    expect($result)
        ->toContain('use Illuminate\Validation\Rules\Password');
});

it('does not duplicate password rules method if it already exists', function () {
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
    $parser = createPhpParserHelper();

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

it('adds blank line above password rules method', function () {
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
        AddPasswordRules::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\}\n\n {4}protected function passwordRules\(\)/s');
});

it('adds password rules method call to boot method', function () {
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
        AddPasswordRules::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('$this->passwordRules()');
});

it('adds password rules method call at the bottom of boot method', function () {
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
        AddPasswordRules::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toMatch('/\$this->existingCall\(\);.*\$this->passwordRules\(\);[\s]*\}/s');
});
