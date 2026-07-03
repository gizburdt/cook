<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddPassportHasApiTokens;

it('adds trait and interface to a bare user model', function () {
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
        AddPassportHasApiTokens::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use Laravel\Passport\HasApiTokens;')
        ->toContain('use Laravel\Passport\Contracts\OAuthenticatable;')
        ->toContain('use HasApiTokens;')
        ->toContain('implements OAuthenticatable');
});

it('appends interface to an existing implements list', function () {
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
        AddPassportHasApiTokens::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('implements FilamentUser, OAuthenticatable')
        ->and(substr_count($result, 'OAuthenticatable'))->toBe(2);
});

it('is idempotent', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;
}
PHP;

    $result = $parser->testParseContent($content, [
        AddPassportHasApiTokens::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use HasApiTokens;'))->toBe(1)
        ->and(substr_count($result, 'use Laravel\Passport\HasApiTokens;'))->toBe(1)
        ->and(substr_count($result, 'implements OAuthenticatable'))->toBe(1);
});
