<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddAppAuthenticationMethods;

it('adds traits to class', function () {
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
        ->toContain('use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;');
});

it('does not duplicate traits if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\InteractsWithAppAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Filament\Auth\MultiFactor\App\InteractsWithAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;'))
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
        ->toContain('use Filament\Auth\MultiFactor\App\InteractsWithAppAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\App\InteractsWithAppAuthenticationRecovery;');
});

it('does not duplicate use statements if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\InteractsWithAppAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Filament\Auth\MultiFactor\App\InteractsWithAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'use Filament\Auth\MultiFactor\App\InteractsWithAppAuthenticationRecovery;'))
        ->toBe(1);
});

it('adds traits to class with existing traits', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;')
        ->toContain('use Notifiable;');
});
