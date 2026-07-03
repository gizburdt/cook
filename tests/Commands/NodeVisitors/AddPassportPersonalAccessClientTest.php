<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddPassportPersonalAccessClient;

it('adds a passport client call and the Artisan use statement', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddPassportPersonalAccessClient::class,
    ], 'database/seeders/DatabaseSeeder.php');

    expect($result)
        ->toContain('use Illuminate\Support\Facades\Artisan;')
        ->toContain("Artisan::call('passport:client'")
        ->toContain("'--personal' => true")
        ->toContain("'--provider' => 'users'");
});

it('is idempotent', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('passport:client', [
            '--personal' => true,
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddPassportPersonalAccessClient::class,
    ], 'database/seeders/DatabaseSeeder.php');

    expect(substr_count($result, "Artisan::call('passport:client'"))->toBe(1)
        ->and(substr_count($result, 'use Illuminate\Support\Facades\Artisan;'))->toBe(1);
});
