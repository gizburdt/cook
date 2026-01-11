<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddAppAuthenticationMethods;

it('adds HasAppAuthentication and HasAppAuthenticationRecovery implements to class without implements', function () {
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
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('implements HasAppAuthentication, HasAppAuthenticationRecovery');
});

it('adds required implements to existing implements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery');
});

it('does not duplicate HasAppAuthentication if already implemented', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAppAuthentication
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'Contracts\HasAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'implements HasAppAuthentication,'))
        ->toBe(1);
});

it('does not duplicate HasAppAuthenticationRecovery if already implemented', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAppAuthenticationRecovery
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'Contracts\HasAppAuthenticationRecovery;'))
        ->toBe(1)
        ->and(substr_count($result, 'implements HasAppAuthenticationRecovery'))
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
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;')
        ->toContain('use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;');
});

it('does not add FilamentUser or Panel use statements', function () {
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
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->not->toContain('use Filament\Models\Contracts\FilamentUser;')
        ->not->toContain('use Filament\Panel;');
});

it('does not duplicate use statements if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;'))
        ->toBe(1);
});

it('adds trait use statements', function () {
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
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use InteractsWithAppAuthentication;')
        ->toContain('use InteractsWithAppAuthenticationRecovery;');
});

it('adds trait use statements after existing traits', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use HasFactory;')
        ->toContain('use InteractsWithAppAuthentication;')
        ->toContain('use InteractsWithAppAuthenticationRecovery;');
});

it('does not duplicate trait use statements if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use InteractsWithAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'use InteractsWithAppAuthenticationRecovery;'))
        ->toBe(1);
});

it('works together with AddCanAccessPanel without duplicates', function () {
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
        \Gizburdt\Cook\Commands\NodeVisitors\AddCanAccessPanel::class,
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Filament\Panel;'))
        ->toBe(1)
        ->and(substr_count($result, 'use Filament\Models\Contracts\FilamentUser;'))
        ->toBe(1)
        ->and(substr_count($result, 'public function canAccessPanel'))
        ->toBe(1);
});
