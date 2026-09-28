<?php

declare(strict_types=1);

/**
 * Runs the tests for the PHP files Claude changed this turn, and sends it back to
 * fix failures. A changed test runs itself, a changed class runs {Class}Test.php.
 * Set CLAUDE_SKIP_TESTS=1 to turn it off.
 */
$input = json_decode((string) stream_get_contents(STDIN), true) ?: [];

// Claude is already fixing a failure from this hook; stopping now prevents an endless loop.
// The marker stays, so the fix is tested at the end of the next turn.
if (($input['stop_hook_active'] ?? false) || getenv('CLAUDE_SKIP_TESTS')) {
    exit(0);
}

$marker = sys_get_temp_dir().'/claude-tests-'.sha1((string) ($input['session_id'] ?? ''));

if (! is_file($marker)) {
    exit(0);
}

$changed = array_unique(file($marker, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);

unlink($marker);

$project = rtrim((string) getenv('CLAUDE_PROJECT_DIR'), '/');

chdir($project);

if (! is_file('vendor/bin/pest') || ! is_dir('tests')) {
    exit(0);
}

/** @var array<string, list<string>> $testsByName */
$testsByName = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('tests', FilesystemIterator::SKIP_DOTS)) as $test) {
    if (str_ends_with($test->getFilename(), 'Test.php')) {
        $testsByName[$test->getFilename()][] = $test->getPathname();
    }
}

$tests = [];

foreach ($changed as $file) {
    $path = str_starts_with($file, $project.'/') ? substr($file, strlen($project) + 1) : $file;

    if (str_starts_with($path, 'tests/') && str_ends_with($path, 'Test.php')) {
        $tests[] = $path;

        continue;
    }

    array_push($tests, ...$testsByName[basename($path, '.php').'Test.php'] ?? []);
}

$tests = array_values(array_unique(array_filter($tests, 'is_file')));

if ($tests === []) {
    exit(0);
}

exec('vendor/bin/pest --compact '.implode(' ', array_map('escapeshellarg', $tests)).' 2>&1', $output, $code);

if ($code === 0) {
    exit(0);
}

fwrite(STDERR, "Tests are failing, fix them before finishing:\n".implode("\n", array_slice($output, -60)));

exit(2);
