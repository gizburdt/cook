<?php

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->project = sys_get_temp_dir().'/cook-test-'.uniqid();

    $this->session = uniqid();

    $this->marker = sys_get_temp_dir().'/claude-tests-'.sha1($this->session);

    mkdir($this->project.'/vendor/bin', recursive: true);

    mkdir($this->project.'/tests/Unit', recursive: true);

    mkdir($this->project.'/app/Models', recursive: true);

    // A fake Pest that fails and prints the test files it was given.
    file_put_contents($this->project.'/vendor/bin/pest', "#!/bin/sh\necho \"ran: \$*\"\nexit 1\n");

    chmod($this->project.'/vendor/bin/pest', 0755);

    file_put_contents($this->project.'/app/Models/User.php', '<?php');

    file_put_contents($this->project.'/tests/Unit/UserTest.php', '<?php');

    file_put_contents($this->project.'/tests/Unit/OtherTest.php', '<?php');
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->project);

    if (is_file($this->marker)) {
        unlink($this->marker);
    }
});

function stopHook(object $test, array $input = [], array $env = []): array
{
    return runHook('tests', ['session_id' => $test->session, ...$input], [
        'CLAUDE_PROJECT_DIR' => $test->project,
        ...$env,
    ]);
}

function markChanged(object $test, string $path): void
{
    runHook('pint', [
        'session_id' => $test->session,
        'tool_input' => ['file_path' => "{$test->project}/{$path}"],
    ], ['CLAUDE_PROJECT_DIR' => $test->project]);
}

it('does nothing when no php file was changed', function () {
    expect(stopHook($this))->exit->toBe(0);
});

it('runs the test belonging to a changed class', function () {
    markChanged($this, 'app/Models/User.php');

    expect(stopHook($this))
        ->exit->toBe(2)
        ->errors->toContain('tests/Unit/UserTest.php')
        ->errors->not->toContain('OtherTest.php');
});

it('runs a changed test itself', function () {
    markChanged($this, 'tests/Unit/OtherTest.php');

    expect(stopHook($this))
        ->exit->toBe(2)
        ->errors->toContain('tests/Unit/OtherTest.php');
});

it('runs the tests only once', function () {
    markChanged($this, 'app/Models/User.php');

    stopHook($this);

    expect(stopHook($this))->exit->toBe(0);
});

it('does nothing when a changed class has no test', function () {
    file_put_contents($this->project.'/app/Models/Post.php', '<?php');

    markChanged($this, 'app/Models/Post.php');

    expect(stopHook($this))->exit->toBe(0);
});

it('does nothing while claude is fixing a failure', function () {
    markChanged($this, 'app/Models/User.php');

    expect(stopHook($this, ['stop_hook_active' => true]))->exit->toBe(0)
        ->and($this->marker)->toBeFile();
});

it('can be turned off', function () {
    markChanged($this, 'app/Models/User.php');

    expect(stopHook($this, env: ['CLAUDE_SKIP_TESTS' => '1']))->exit->toBe(0);
});
