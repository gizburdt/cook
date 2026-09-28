<?php

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->project = sys_get_temp_dir().'/cook-test-'.uniqid();

    mkdir($this->project.'/database/migrations', recursive: true);

    mkdir($this->project.'/.ai/guidelines', recursive: true);

    file_put_contents($this->project.'/database/migrations/2024_01_01_000000_create_users_table.php', '<?php');
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->project);
});

function guardFile(string $project, string $path, string $status = 'development'): string
{
    file_put_contents($project.'/.ai/guidelines/environment.blade.php', "# Environment\n\nSTATUS: {$status}\n");

    return runHook('guard-files', ['tool_input' => ['file_path' => "{$project}/{$path}"]], [
        'CLAUDE_PROJECT_DIR' => $project,
    ])['decision'];
}

it('decides on file edits', function (string $path, string $decision) {
    expect(guardFile($this->project, $path))->toBe($decision);
})->with([
    'vendor' => ['vendor/laravel/framework/src/Foo.php', 'deny'],
    'node_modules' => ['node_modules/foo/index.js', 'deny'],
    'composer.lock' => ['composer.lock', 'deny'],
    '.env' => ['.env', 'ask'],
    '.env.example' => ['.env.example', 'allow'],
    'app file' => ['app/Models/User.php', 'allow'],
]);

it('allows changing existing migrations during development', function () {
    expect(guardFile($this->project, 'database/migrations/2024_01_01_000000_create_users_table.php'))->toBe('allow');
});

it('denies changing existing migrations in production', function () {
    expect(guardFile($this->project, 'database/migrations/2024_01_01_000000_create_users_table.php', 'production'))->toBe('deny');
});

it('allows new migrations in production', function () {
    expect(guardFile($this->project, 'database/migrations/2099_01_01_000000_create_posts_table.php', 'production'))->toBe('allow');
});
