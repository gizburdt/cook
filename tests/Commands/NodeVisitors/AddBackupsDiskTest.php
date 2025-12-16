<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsDisk;

it('adds local backups disk to filesystems config', function () {
    $parser = createPhpParserHelper();

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
        ->toContain("'root' => storage_path('backups')")
        ->toContain("'serve' => true")
        ->toContain("'throw' => false")
        ->toContain("'report' => false");
});

it('adds google backups disk to filesystems config', function () {
    $parser = createPhpParserHelper();

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
        ->toContain("'clientId' => env('BACKUP_GOOGLE_CLIENT_ID')")
        ->toContain("'clientSecret' => env('BACKUP_GOOGLE_CLIENT_SECRET')")
        ->toContain("'refreshToken' => env('BACKUP_GOOGLE_REFRESH_TOKEN')")
        ->toContain("'folder' => env('BACKUP_GOOGLE_FOLDER')");
});

it('adds minio backups disk to filesystems config', function () {
    $parser = createPhpParserHelper();

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
        new AddBackupsDisk('minio'),
    ]);

    expect($result)
        ->toContain("'backups'")
        ->toContain("'driver' => 's3'")
        ->toContain("'key' => env('BACKUP_S3_KEY')")
        ->toContain("'secret' => env('BACKUP_S3_SECRET')")
        ->toContain("'region' => env('BACKUP_S3_REGION')")
        ->toContain("'bucket' => env('BACKUP_S3_BUCKET')")
        ->toContain("'url' => env('BACKUP_S3_URL')")
        ->toContain("'endpoint' => env('BACKUP_S3_ENDPOINT')")
        ->toContain("'use_path_style_endpoint' => false")
        ->toContain("'throw' => false")
        ->toContain("'report' => false");
});

it('replaces existing backups disk with new one', function () {
    $parser = createPhpParserHelper();

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
        ->toContain("storage_path('backups')")
        ->not->toContain('existing-backups');
});

it('preserves existing disks when adding backups', function () {
    $parser = createPhpParserHelper();

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

it('adds newline before backups disk entry', function () {
    $parser = createPhpParserHelper();

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
        ->toMatch('/\],\s*\n\s*\'backups\'/s');
});

it('formats local disk config keys on separate lines', function () {
    $parser = createPhpParserHelper();

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
        ->toMatch('/\'backups\'\s*=>\s*\[\s*\n\s*\'driver\'\s*=>\s*\'local\'/s')
        ->toMatch('/\'driver\'\s*=>\s*\'local\',\s*\n\s*\'root\'/s')
        ->toMatch('/\'root\'\s*=>\s*storage_path\(\'backups\'\),\s*\n\s*\'serve\'/s')
        ->toMatch('/\'serve\'\s*=>\s*true,\s*\n\s*\'throw\'/s')
        ->toMatch('/\'throw\'\s*=>\s*false,\s*\n\s*\'report\'/s');
});

it('formats google disk config keys on separate lines', function () {
    $parser = createPhpParserHelper();

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
        ->toMatch('/\'backups\'\s*=>\s*\[\s*\n\s*\'driver\'\s*=>\s*\'google\'/s')
        ->toMatch('/\'driver\'\s*=>\s*\'google\',\s*\n\s*\'clientId\'/s')
        ->toMatch('/\'clientId\'\s*=>\s*env\(\'BACKUP_GOOGLE_CLIENT_ID\'\),\s*\n\s*\'clientSecret\'/s')
        ->toMatch('/\'clientSecret\'\s*=>\s*env\(\'BACKUP_GOOGLE_CLIENT_SECRET\'\),\s*\n\s*\'refreshToken\'/s')
        ->toMatch('/\'refreshToken\'\s*=>\s*env\(\'BACKUP_GOOGLE_REFRESH_TOKEN\'\),\s*\n\s*\'folder\'/s');
});

it('formats minio disk config keys on separate lines', function () {
    $parser = createPhpParserHelper();

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
        new AddBackupsDisk('minio'),
    ]);

    expect($result)
        ->toMatch('/\'backups\'\s*=>\s*\[\s*\n\s*\'driver\'\s*=>\s*\'s3\'/s')
        ->toMatch('/\'driver\'\s*=>\s*\'s3\',\s*\n\s*\'key\'/s')
        ->toMatch('/\'key\'\s*=>\s*env\(\'BACKUP_S3_KEY\'\),\s*\n\s*\'secret\'/s')
        ->toMatch('/\'secret\'\s*=>\s*env\(\'BACKUP_S3_SECRET\'\),\s*\n\s*\'region\'/s')
        ->toMatch('/\'region\'\s*=>\s*env\(\'BACKUP_S3_REGION\'\),\s*\n\s*\'bucket\'/s')
        ->toMatch('/\'bucket\'\s*=>\s*env\(\'BACKUP_S3_BUCKET\'\),\s*\n\s*\'url\'/s')
        ->toMatch('/\'url\'\s*=>\s*env\(\'BACKUP_S3_URL\'\),\s*\n\s*\'endpoint\'/s')
        ->toMatch('/\'endpoint\'\s*=>\s*env\(\'BACKUP_S3_ENDPOINT\'\),\s*\n\s*\'use_path_style_endpoint\'/s')
        ->toMatch('/\'use_path_style_endpoint\'\s*=>\s*false,\s*\n\s*\'throw\'/s')
        ->toMatch('/\'throw\'\s*=>\s*false,\s*\n\s*\'report\'/s');
});

it('does not add extra blank lines within disk config array', function () {
    $parser = createPhpParserHelper();

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
        ->not->toMatch('/\'backups\'\s*=>\s*\[\s*\n\s*\n/s');
});

it('preserves block comments in existing config', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

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
        ->toContain('Filesystem Disks');
});
