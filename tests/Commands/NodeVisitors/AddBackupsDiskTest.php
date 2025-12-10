<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsDisk;

it('adds local backups disk to filesystems config', function () {
    $parser = createAddBackupsDiskParser();

    $content = <<<'PHP'
<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        new AddBackupsDisk('local'),
    ]);

    expect($result)
        ->toContain("'backups'")
        ->toContain("'driver' => 'local'")
        ->toContain("storage_path('backups')");
});

it('adds google backups disk to filesystems config', function () {
    $parser = createAddBackupsDiskParser();

    $content = <<<'PHP'
<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        new AddBackupsDisk('google'),
    ]);

    expect($result)
        ->toContain("'backups'")
        ->toContain("'driver' => 'google'")
        ->toContain('GOOGLE_DRIVE_CLIENT_ID')
        ->toContain('GOOGLE_DRIVE_CLIENT_SECRET')
        ->toContain('GOOGLE_DRIVE_REFRESH_TOKEN')
        ->toContain('GOOGLE_DRIVE_FOLDER');
});

it('does not add backups disk if it already exists', function () {
    $parser = createAddBackupsDiskParser();

    $content = <<<'PHP'
<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'backups' => [
            'driver' => 'local',
            'root' => storage_path('existing-backups'),
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        new AddBackupsDisk('local'),
    ]);

    expect($result)
        ->toContain('existing-backups')
        ->not->toContain("storage_path('backups')");
});

it('preserves existing disks when adding backups', function () {
    $parser = createAddBackupsDiskParser();

    $content = <<<'PHP'
<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        's3' => [
            'driver' => 's3',
            'bucket' => 'my-bucket',
        ],
    ],

];
PHP;

    $result = $parser->testParseContent($content, [
        new AddBackupsDisk('local'),
    ]);

    expect($result)
        ->toContain("'local'")
        ->toContain("'s3'")
        ->toContain("'backups'");
});

function createAddBackupsDiskParser(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors): string
        {
            return $this->parseContent($content, $visitors);
        }
    };
}
