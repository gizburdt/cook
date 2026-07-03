<?php

use Gizburdt\Cook\Commands\Support\MfaMethodDetector;
use Gizburdt\Cook\Enums\MfaMethod;

function userModelWith(string $implements): string
{
    return <<<PHP
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements {$implements}
{
    //
}
PHP;
}

it('detects app mfa from the user model implements', function () {
    $methods = MfaMethodDetector::fromContent(
        userModelWith('HasAppAuthentication, HasAppAuthenticationRecovery')
    );

    expect($methods)->toBe([MfaMethod::App]);
});

it('detects email mfa from the user model implements', function () {
    $methods = MfaMethodDetector::fromContent(
        userModelWith('HasEmailAuthentication')
    );

    expect($methods)->toBe([MfaMethod::Email]);
});

it('detects both mfa methods from the user model implements', function () {
    $methods = MfaMethodDetector::fromContent(
        userModelWith('HasAppAuthentication, HasEmailAuthentication')
    );

    expect($methods)->toBe([MfaMethod::App, MfaMethod::Email]);
});

it('detects no mfa methods when the user model has none', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    expect(MfaMethodDetector::fromContent($content))->toBe([]);
});

it('returns nothing when the content has no class', function () {
    expect(MfaMethodDetector::fromContent("<?php\n\nreturn [];\n"))->toBe([]);
});
