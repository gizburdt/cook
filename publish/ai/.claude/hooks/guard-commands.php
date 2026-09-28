<?php

declare(strict_types=1);

/**
 * Stops Bash commands that break the project rules, or asks first for risky ones.
 * Each part of a chained command (&&, ||, ;, |) is checked on its own.
 */
$input = json_decode((string) stream_get_contents(STDIN), true) ?: [];

$command = (string) ($input['tool_input']['command'] ?? '');

/**
 * @return array{0: 'deny'|'ask', 1: string}|null
 */
function check(string $segment): ?array
{
    if (preg_match('/\bmigrate:fresh\b/', $segment) && ! preg_match('/(^|\s)--seed\b/', $segment)) {
        return ['deny', 'Always run migrate:fresh with --seed.'];
    }

    if (preg_match('/\bgit\b.*(^|\s)--no-verify\b/', $segment)) {
        return ['deny', 'Do not skip git hooks with --no-verify, fix the underlying issue instead.'];
    }

    if (preg_match('/\bartisan\s+make:/', $segment) && ! preg_match('/(^|\s)(--no-interaction|-n)(\s|$)/', $segment)) {
        return ['deny', 'Pass --no-interaction to artisan make commands.'];
    }

    if (preg_match('/\bgit\s+push\b.*\s(--force(-with-lease)?\b|-[a-zA-Z]*f[a-zA-Z]*\b|\+\S)/', $segment)) {
        return ['ask', 'Force push, confirm before running.'];
    }

    if (
        preg_match('/\bcomposer\s+(require|remove)\b/', $segment)
        || preg_match('/\bnpm\s+(install|i|add|uninstall|remove|rm|un)\b(\s+-\S+)*\s+[^-\s]/', $segment)
        || preg_match('/\b(pnpm|yarn|bun)\s+(add|remove)\b/', $segment)
    ) {
        return ['ask', 'This changes the project dependencies, confirm before running.'];
    }

    return null;
}

$decisions = array_filter(array_map(
    fn (string $segment): ?array => check(trim($segment)),
    preg_split('/&&|\|\||;|\|/', $command) ?: [],
));

if ($decisions === []) {
    exit(0);
}

usort($decisions, fn (array $a, array $b): int => ($a[0] === 'deny' ? 0 : 1) <=> ($b[0] === 'deny' ? 0 : 1));

[$decision, $reason] = $decisions[0];

echo json_encode([
    'hookSpecificOutput' => [
        'hookEventName' => 'PreToolUse',
        'permissionDecision' => $decision,
        'permissionDecisionReason' => $reason,
    ],
]);
