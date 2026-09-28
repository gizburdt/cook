<?php

declare(strict_types=1);

/**
 * Runs the changed tests when Claude finishes a turn in which it edited PHP, and
 * sends it back to fix failures. Set CLAUDE_SKIP_TESTS=1 to turn it off.
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

unlink($marker);

chdir((string) getenv('CLAUDE_PROJECT_DIR'));

if (! is_file('vendor/bin/pest')) {
    exit(0);
}

exec('vendor/bin/pest --dirty --compact 2>&1', $output, $code);

if ($code === 0) {
    exit(0);
}

fwrite(STDERR, "Tests are failing, fix them before finishing:\n".implode("\n", array_slice($output, -60)));

exit(2);
