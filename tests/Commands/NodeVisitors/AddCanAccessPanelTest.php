<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddCanAccessPanel;

it('adds canAccessPanel method to user model', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('public function canAccessPanel(Panel $panel): bool')
        ->toContain('return true;');
});

it('adds FilamentUser implements to class without implements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('class User extends Authenticatable implements FilamentUser');
});

it('adds FilamentUser to existing implements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements SomeInterface
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('class User extends Authenticatable implements SomeInterface, FilamentUser');
});

it('does not duplicate FilamentUser if already implemented', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'implements FilamentUser'))
        ->toBe(1);
});

it('does not duplicate canAccessPanel method if already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function canAccessPanel(Panel $panel): bool
    {
        return false;
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'public function canAccessPanel'))
        ->toBe(1);
});

it('adds required use statements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use Filament\Panel;')
        ->toContain('use Filament\Models\Contracts\FilamentUser;');
});

it('does not duplicate use statements if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Filament\Panel;'))
        ->toBe(1)
        ->and(substr_count($result, 'use Filament\Models\Contracts\FilamentUser;'))
        ->toBe(1);
});

it('adds blank line before canAccessPanel method', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function existingMethod(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddCanAccessPanel::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toMatch('/\}\n\n {4}public function canAccessPanel\(/s');
});
