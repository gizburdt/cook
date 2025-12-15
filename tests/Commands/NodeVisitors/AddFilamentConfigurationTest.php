<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddFilamentConfiguration;

it('adds filament configuration method to app service provider', function () {
    $parser = createAddFilamentConfigurationParser();

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
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('protected function filament(): void')
        ->toContain('Table::configureUsing')
        ->toContain('TextInput::configureUsing')
        ->toContain('TextEntry::configureUsing')
        ->toContain('TextColumn::configureUsing');
});

it('adds blank lines between statements in filament method', function () {
    $parser = createAddFilamentConfigurationParser();

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
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check that there are blank lines between configureUsing statements
    // Each configureUsing should be followed by }); then a blank line, then the next statement
    expect($result)
        ->toMatch('/Table::configureUsing\([^}]+\}\);[\s]*\n[\s]*\n[\s]*TextInput::configureUsing/s')
        ->toMatch('/TextInput::configureUsing\([^}]+\}\);[\s]*\n[\s]*\n[\s]*TextEntry::configureUsing/s')
        ->toMatch('/TextEntry::configureUsing\([^}]+\}\);[\s]*\n[\s]*\n[\s]*TextColumn::configureUsing/s');
});

it('formats table pagination options on multiple lines', function () {
    $parser = createAddFilamentConfigurationParser();

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
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check that pagination options array is on multiple lines
    expect($result)
        ->toMatch('/->paginationPageOptions\(\[[\s]+10,[\s]+25,[\s]+50,[\s]+100,?[\s]*\]\)/s')
        ->toContain('->defaultPaginationPageOption(50)');
});

it('adds method call to boot method', function () {
    $parser = createAddFilamentConfigurationParser();

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
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('$this->filament()');
});

it('adds filament use statements', function () {
    $parser = createAddFilamentConfigurationParser();

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
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect($result)
        ->toContain('use Filament\Tables\Table')
        ->toContain('use Filament\Forms\Components\TextInput')
        ->toContain('use Filament\Infolists\Components\TextEntry')
        ->toContain('use Filament\Tables\Columns\TextColumn');
});

it('adds one blank line between methods', function () {
    $parser = createAddFilamentConfigurationParser();

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
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    // Check for one blank line between existingMethod() and filament() methods
    expect($result)
        ->toMatch('/existingMethod\(\): void[\s]*\{[\s]*\/\/[\s]*\}[\s]*\n[\s]*\n[\s]*protected function filament\(\)/s');
});

it('does not duplicate filament method if it already exists', function () {
    $parser = createAddFilamentConfigurationParser();

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

    protected function filament(): void
    {
        // existing method
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddFilamentConfiguration::class,
    ], 'app/Providers/AppServiceProvider.php');

    expect(substr_count($result, 'protected function filament()'))
        ->toBe(1);
});

function createAddFilamentConfigurationParser(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors, ?string $file = null): string
        {
            return $this->parsePhpContent($content, $visitors, $file);
        }
    };
}
