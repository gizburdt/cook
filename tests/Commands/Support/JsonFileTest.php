<?php

use Gizburdt\Cook\Commands\Support\JsonFile;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-test-'.uniqid();

    $this->path = $this->tempDir.'/.claude/settings.json';

    $this->byName = fn (array $item) => $item['name'];
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

function writeJson(string $path, array $data): void
{
    (new Filesystem)->ensureDirectoryExists(dirname($path));

    file_put_contents($path, json_encode($data));
}

function readJson(string $path): array
{
    return json_decode(file_get_contents($path), true);
}

it('reads values with dot notation', function () {
    writeJson($this->path, ['hooks' => ['Stop' => ['foo']]]);

    expect(new JsonFile(new Filesystem, $this->path))
        ->get('hooks.Stop')->toBe(['foo'])
        ->get('hooks.Missing', 'default')->toBe('default');
});

it('creates the file and directory when missing', function () {
    $json = new JsonFile(new Filesystem, $this->path);

    $json->mergeList('items', [['name' => 'a']], $this->byName);

    $json->save();

    expect(readJson($this->path))->toBe(['items' => [['name' => 'a']]]);
});

it('keeps existing data and appends new items', function () {
    writeJson($this->path, ['model' => 'opus', 'items' => [['name' => 'a']]]);

    $json = new JsonFile(new Filesystem, $this->path);

    expect($json->mergeList('items', [['name' => 'a'], ['name' => 'b']], $this->byName))->toBe(1);

    $json->save();

    expect(readJson($this->path))->toBe([
        'model' => 'opus',
        'items' => [['name' => 'a'], ['name' => 'b']],
    ]);
});

it('does not write the file when nothing changed', function () {
    writeJson($this->path, ['items' => [['name' => 'a']]]);

    $before = file_get_contents($this->path);

    $json = new JsonFile(new Filesystem, $this->path);

    $json->mergeList('items', [['name' => 'a']], $this->byName);

    $json->save();

    expect(file_get_contents($this->path))->toBe($before);
});

it('merges the published claude hooks into empty settings', function () {
    $source = new JsonFile(new Filesystem, dirname(__DIR__, 3).'/publish/ai/.claude/settings.json');

    $settings = new JsonFile(new Filesystem, $this->path);

    foreach ($source->get('hooks') as $event => $groups) {
        $settings->mergeList("hooks.{$event}", $groups, fn (array $group) => array_column($group['hooks'], 'command'));
    }

    $settings->save();

    expect(readJson($this->path))->toBe(['hooks' => $source->get('hooks')]);
});
