<?php

declare(strict_types=1);

/**
 * Formats a PHP file with Pint right after Claude edits or writes it, and marks
 * the session so the Stop hook knows there is PHP to test.
 */
$input = json_decode((string) stream_get_contents(STDIN), true) ?: [];

$file = $input['tool_input']['file_path'] ?? '';

if (! str_ends_with($file, '.php') || str_contains($file, '/vendor/') || ! is_file($file)) {
    exit(0);
}

touch(sys_get_temp_dir().'/claude-tests-'.sha1((string) ($input['session_id'] ?? '')));

$pint = getenv('CLAUDE_PROJECT_DIR').'/vendor/bin/pint';

if (is_file($pint)) {
    exec(sprintf('%s --quiet %s', escapeshellarg($pint), escapeshellarg($file)));
}
