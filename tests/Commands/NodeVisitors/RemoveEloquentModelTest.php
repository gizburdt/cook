<?php

use Gizburdt\Cook\Commands\NodeVisitors\RemoveEloquentModel;

it('removes eloquent model use statement', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        RemoveEloquentModel::class,
    ]);

    expect($result)
        ->not->toContain('use Illuminate\Database\Eloquent\Model')
        ->toContain('namespace App\Models')
        ->toContain('class User');
});

it('preserves other use statements when removing eloquent model', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory;
    use SoftDeletes;
}
PHP;

    $result = $parser->testParseContent($content, [
        RemoveEloquentModel::class,
    ]);

    expect($result)
        ->not->toContain('use Illuminate\Database\Eloquent\Model;')
        ->toContain('use Illuminate\Database\Eloquent\Factories\HasFactory')
        ->toContain('use Illuminate\Database\Eloquent\SoftDeletes');
});

it('does nothing when eloquent model use statement does not exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

use App\Models\Model;

class User extends Model
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        RemoveEloquentModel::class,
    ]);

    expect($result)
        ->toContain('use App\Models\Model')
        ->toContain('namespace App\Models');
});

it('handles files without any use statements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Models;

class User
{
    //
}
PHP;

    $result = $parser->testParseContent($content, [
        RemoveEloquentModel::class,
    ]);

    expect($result)
        ->toContain('namespace App\Models')
        ->toContain('class User');
});
