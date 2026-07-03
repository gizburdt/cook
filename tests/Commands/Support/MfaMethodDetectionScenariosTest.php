<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddMultiFactorAuthentication;
use Gizburdt\Cook\Commands\Support\MfaMethodDetector;
use Gizburdt\Cook\Enums\MfaMethod;

it('detects app from a realistic user model as cook:filament generates it', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
PHP;

    expect(MfaMethodDetector::fromContent($content))->toBe([MfaMethod::App]);
});

it('detects email from a realistic user model', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication
{
    use InteractsWithEmailAuthentication;
}
PHP;

    expect(MfaMethodDetector::fromContent($content))->toBe([MfaMethod::Email]);
});

it('detects both from a realistic user model', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    //
}
PHP;

    expect(MfaMethodDetector::fromContent($content))->toBe([MfaMethod::App, MfaMethod::Email]);
});

it('does not detect app when only the recovery contract is present', function () {
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

    expect(MfaMethodDetector::fromContent($content))->toBe([]);
});

it('feeds detected methods straight into the panel visitor', function () {
    $userModel = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    //
}
PHP;

    $methods = MfaMethodDetector::fromContent($userModel);

    $panelProvider = <<<'PHP'
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->profile(EditProfile::class)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
PHP;

    $result = createPhpParserHelper()->testParseContent($panelProvider, [
        new AddMultiFactorAuthentication($methods),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain('AppAuthentication::make()')
        ->toContain('->recoverable()')
        ->toContain('EmailAuthentication::make()')
        ->toContain('use Filament\Auth\MultiFactor\App\AppAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\Email\EmailAuthentication;');
});
