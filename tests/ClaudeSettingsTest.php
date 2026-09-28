<?php

use Gizburdt\Cook\ClaudeSettings;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-test-'.uniqid();

    $this->path = $this->tempDir.'/.claude/settings.json';

    $this->settings = new ClaudeSettings(new Filesystem, $this->path);

    $this->hook = [
        'matcher' => 'Bash',
        'hooks' => [
            ['type' => 'command', 'command' => 'php guard.php'],
        ],
    ];
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('creates the settings file when it does not exist', function () {
    expect($this->settings->mergeHooks(['PreToolUse' => [$this->hook]]))->toBe(1)
        ->and(json_decode(file_get_contents($this->path), true))->toBe([
            'hooks' => ['PreToolUse' => [$this->hook]],
        ]);
});

it('keeps existing settings and hooks', function () {
    mkdir(dirname($this->path), recursive: true);

    $existing = [
        'matcher' => 'Write',
        'hooks' => [
            ['type' => 'command', 'command' => 'php other.php'],
        ],
    ];

    file_put_contents($this->path, json_encode([
        'model' => 'opus',
        'hooks' => ['PreToolUse' => [$existing]],
    ]));

    $this->settings->mergeHooks(['PreToolUse' => [$this->hook]]);

    expect(json_decode(file_get_contents($this->path), true))->toBe([
        'model' => 'opus',
        'hooks' => ['PreToolUse' => [$existing, $this->hook]],
    ]);
});

it('skips hooks whose command is already present', function () {
    $this->settings->mergeHooks(['PreToolUse' => [$this->hook]]);

    expect($this->settings->mergeHooks(['PreToolUse' => [$this->hook]]))->toBe(0)
        ->and(json_decode(file_get_contents($this->path), true)['hooks']['PreToolUse'])->toHaveCount(1);
});

it('merges every hook from the published settings', function () {
    $source = json_decode(file_get_contents(dirname(__DIR__).'/publish/ai/.claude/settings.json'), true);

    $this->settings->mergeHooks($source['hooks']);

    expect(json_decode(file_get_contents($this->path), true))->toBe($source);
});
