<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddAppAuthenticationMethods;

it('adds HasAppAuthentication implements to class without implements', function () {
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
        ->toContain('class User extends Authenticatable implements HasAppAuthentication');
});

it('adds HasAppAuthentication to existing implements', function () {
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
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('class User extends Authenticatable implements SomeInterface, HasAppAuthentication');
});

it('does not duplicate HasAppAuthentication if already implemented', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use DutchCodingCompany\FilamentAppAuthentication\Contracts\HasAppAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAppAuthentication
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'implements HasAppAuthentication'))
        ->toBe(1);
});

it('adds items to hidden property', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain("'app_authentication_secret',")
        ->toContain("'app_authentication_recovery_codes',");
});

it('does not duplicate items in hidden property', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, "'app_authentication_secret'"))
        ->toBe(1)
        ->and(substr_count($result, "'app_authentication_recovery_codes'"))
        ->toBe(1);
});

it('adds items to casts method', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain("'app_authentication_secret' => 'encrypted'")
        ->toContain("'app_authentication_recovery_codes' => 'encrypted:array'");
});

it('does not duplicate items in casts method', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, "'app_authentication_secret' => 'encrypted'"))
        ->toBe(1)
        ->and(substr_count($result, "'app_authentication_recovery_codes' => 'encrypted:array'"))
        ->toBe(1);
});

it('adds getAppAuthenticationSecret method', function () {
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
        ->toContain('public function getAppAuthenticationSecret(): ?string')
        ->toContain('return $this->app_authentication_secret;');
});

it('adds saveAppAuthenticationSecret method', function () {
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
        ->toContain('public function saveAppAuthenticationSecret(?string $secret): void')
        ->toContain('$this->app_authentication_secret = $secret;')
        ->toContain('$this->save();');
});

it('adds getAppAuthenticationHolderName method', function () {
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
        ->toContain('public function getAppAuthenticationHolderName(): string')
        ->toContain('return $this->email;');
});

it('adds getAppAuthenticationRecoveryCodes method', function () {
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
        ->toContain('public function getAppAuthenticationRecoveryCodes(): ?array')
        ->toContain('return $this->app_authentication_recovery_codes;');
});

it('adds saveAppAuthenticationRecoveryCodes method', function () {
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
        ->toContain('public function saveAppAuthenticationRecoveryCodes(?array $codes): void')
        ->toContain('$this->app_authentication_recovery_codes = $codes;')
        ->toContain('$this->save();');
});

it('does not duplicate methods if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'public function getAppAuthenticationSecret'))
        ->toBe(1);
});

it('adds required use statement', function () {
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
        ->toContain('use DutchCodingCompany\FilamentAppAuthentication\Contracts\HasAppAuthentication;');
});

it('does not duplicate use statement if already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use DutchCodingCompany\FilamentAppAuthentication\Contracts\HasAppAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use DutchCodingCompany\FilamentAppAuthentication\Contracts\HasAppAuthentication;'))
        ->toBe(1);
});

it('adds blank lines between method statements', function () {
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
        ->toMatch('/\$this->app_authentication_secret = \$secret;\s*\n\s*\n\s*\$this->save\(\);/s')
        ->toMatch('/\$this->app_authentication_recovery_codes = \$codes;\s*\n\s*\n\s*\$this->save\(\);/s');
});

it('adds blank line before first method', function () {
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
        AddAppAuthenticationMethods::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toMatch('/\}\n\n {4}public function getAppAuthenticationSecret\(/s');
});
