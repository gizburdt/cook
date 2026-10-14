<?php

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->project = sys_get_temp_dir().'/cook-test-'.uniqid();

    mkdir($this->project, recursive: true);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->project);
});

function guardFile(string $project, string $path): string
{
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
