<?php

declare(strict_types=1);

/**
 * Formats a PHP file with Pint right after Claude edits or writes it, and remembers
 * the file so the Stop hook can run the tests that belong to it.
 */
$input = json_decode((string) stream_get_contents(STDIN), true) ?: [];

$file = $input['tool_input']['file_path'] ?? '';

if (! str_ends_with($file, '.php') || str_contains($file, '/vendor/') || ! is_file($file)) {
    exit(0);
}

file_put_contents(sys_get_temp_dir().'/claude-tests-'.sha1((string) ($input['session_id'] ?? '')), $file.PHP_EOL, FILE_APPEND);

$pint = getenv('CLAUDE_PROJECT_DIR').'/vendor/bin/pint';

if (is_file($pint)) {
    exec(sprintf('%s --quiet %s', escapeshellarg($pint), escapeshellarg($file)));
}
