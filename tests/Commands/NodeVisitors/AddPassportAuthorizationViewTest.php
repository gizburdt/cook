<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddPassportAuthorizationView;

it('adds passport method, boot call and use statement', function () {
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
        AddPassportAuthorizationView::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('use Laravel\Passport\Passport;')
        ->toContain('$this->passport();')
        ->toContain('protected function passport(): void')
        ->toContain("Passport::authorizationView(fn(array \$parameters) => view('mcp.authorize', \$parameters));");
});

it('is idempotent', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->passport();
    }

    protected function passport(): void
    {
        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddPassportAuthorizationView::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect(substr_count($result, '$this->passport();'))->toBe(1)
        ->and(substr_count($result, 'protected function passport(): void'))->toBe(1)
        ->and(substr_count($result, 'use Laravel\Passport\Passport;'))->toBe(1);
});
