<?php

declare(strict_types=1);

/**
 * Stops edits to files that should not be changed by hand.
 */
$input = json_decode((string) stream_get_contents(STDIN), true) ?: [];

$file = $input['tool_input']['file_path'] ?? '';

$project = rtrim((string) getenv('CLAUDE_PROJECT_DIR'), '/');

$path = str_starts_with($file, $project.'/') ? substr($file, strlen($project) + 1) : $file;

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

if (preg_match('#(^|/)(vendor|node_modules)/#', $path) || $path === 'composer.lock') {
    decide('deny', 'This file is managed by Composer or npm, do not edit it by hand.');
}

if (basename($path) === '.env') {
    decide('ask', 'This edits the .env file, confirm before changing it.');
}
