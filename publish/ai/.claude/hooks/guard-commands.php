<?php

declare(strict_types=1);

/**
 * Stops Bash commands that break the project rules, or asks first for risky ones.
 */
$input = json_decode((string) stream_get_contents(STDIN), true) ?: [];

$command = $input['tool_input']['command'] ?? '';

function decide(string $decision, string $reason): never
{
    echo json_encode([
        'hookSpecificOutput' => [
            'hookEventName' => 'PreToolUse',
            'permissionDecision' => $decision,
            'permissionDecisionReason' => $reason,
        ],
    ]);

    exit(0);
}

if (preg_match('/\bmigrate:fresh\b/', $command) && ! preg_match('/--seed\b/', $command)) {
    decide('deny', 'Always run migrate:fresh with --seed.');
}

if (preg_match('/--no-verify\b/', $command)) {
    decide('deny', 'Do not skip git hooks with --no-verify, fix the underlying issue instead.');
}

if (preg_match('/\bartisan\s+make:/', $command) && ! preg_match('/(--no-interaction|\s-n)\b/', $command)) {
    decide('deny', 'Pass --no-interaction to artisan make commands.');
}

if (preg_match('/\bgit\s+push\b.*\s(--force(-with-lease)?|-[a-zA-Z]*f[a-zA-Z]*)\b/', $command)) {
    decide('ask', 'Force push, confirm before running.');
}

if (preg_match('/\bcomposer\s+(require|remove)\b/', $command) || preg_match('/\bnpm\s+(install|i|add|uninstall|remove)\s+[^-\s]/', $command)) {
    decide('ask', 'This changes the project dependencies, confirm before running.');
}
