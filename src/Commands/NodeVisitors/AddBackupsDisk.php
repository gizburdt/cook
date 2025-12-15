<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class AddBackupsDisk extends NodeVisitorAbstract
{
    protected bool $hasBackupsDisk = false;

    public function __construct(protected string $driver) {}

    public function enterNode(Node $node)
    {
        if (! $node instanceof ArrayItem) {
            return null;
        }

        if (! $node->key instanceof String_ || $node->key->value !== 'backups') {
            return null;
        }

        $this->hasBackupsDisk = true;

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($this->hasBackupsDisk) {
            return null;
        }

        if (! $node instanceof ArrayItem) {
            return null;
        }

        if (! $node->key instanceof String_ || $node->key->value !== 'disks') {
            return null;
        }

        if (! $node->value instanceof Array_) {
            return null;
        }

        $node->value->items[] = $this->createBackupsDisk();

        return $node;
    }

    protected function createBackupsDisk(): ArrayItem
    {
        $code = match ($this->driver) {
            'local' => $this->getLocalDiskCode(),
            'google' => $this->getGoogleDiskCode(),
            'minio' => $this->getMinioDiskCode(),
        };

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse($code);

        /** @var Array_ $array */
        $array = $ast[0]->expr;

        $item = $array->items[0];

        // Mark this as a new item that needs a blank line before it
        $item->setAttribute('needsBlankLine', true);

        return $item;
    }

    protected function getLocalDiskCode(): string
    {
        return <<<'PHP'
<?php
return [
    'backups' => [
        'driver' => 'local',
        'root' => storage_path('backups'),
        'serve' => true,
        'throw' => false,
        'report' => false,
    ],
];
PHP;
    }

    protected function getGoogleDiskCode(): string
    {
        return <<<'PHP'
<?php
return [
    'backups' => [
        'driver' => 'google',
        'clientId' => env('BACKUP_GOOGLE_CLIENT_ID'),
        'clientSecret' => env('BACKUP_GOOGLE_CLIENT_SECRET'),
        'refreshToken' => env('BACKUP_GOOGLE_REFRESH_TOKEN'),
        'folder' => env('BACKUP_GOOGLE_FOLDER'),
    ],
];
PHP;
    }

    protected function getMinioDiskCode(): string
    {
        return <<<'PHP'
<?php
return [
    'backups' => [
        'driver' => 's3',
        'key' => env('BACKUP_S3_KEY'),
        'secret' => env('BACKUP_S3_SECRET'),
        'region' => env('BACKUP_S3_REGION'),
        'bucket' => env('BACKUP_S3_BUCKET'),
        'url' => env('BACKUP_S3_URL'),
        'endpoint' => env('BACKUP_S3_ENDPOINT'),
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
    ],
];
PHP;
    }
}
