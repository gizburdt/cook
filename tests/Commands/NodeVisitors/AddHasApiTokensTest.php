<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddSanctumHasApiTokens;

it('adds HasApiTokens trait to user model', function () {
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
        AddSanctumHasApiTokens::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use HasApiTokens;');
});

it('adds HasApiTokens trait after existing traits', function () {
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
        AddSanctumHasApiTokens::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use HasFactory;')
        ->toContain('use HasApiTokens;');
});

it('does not duplicate HasApiTokens trait if already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens;
}
PHP;

    $result = $parser->testParseContent($content, [
        AddSanctumHasApiTokens::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use HasApiTokens;'))
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
        AddSanctumHasApiTokens::class,
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('use Laravel\Sanctum\HasApiTokens;');
});

it('does not duplicate use statement if already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        AddSanctumHasApiTokens::class,
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Laravel\Sanctum\HasApiTokens;'))
        ->toBe(1);
});
