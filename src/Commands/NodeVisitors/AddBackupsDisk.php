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

    public function __construct(protected string $driver = 'google') {}

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
        };

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        /** @var Array_ $array */
        $array = $ast[0]->expr;

        return $array->items[0];
    }

    protected function getLocalDiskCode(): string
    {
        return <<<'PHP'
<?php
return [
    'backups' => [
        'driver' => 'local',
        'root' => storage_path('app/backups'),
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
        'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder' => env('GOOGLE_DRIVE_FOLDER'),
    ],
];
PHP;
    }
}
