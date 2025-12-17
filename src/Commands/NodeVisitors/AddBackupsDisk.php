<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class AddBackupsDisk extends NodeVisitorAbstract
{
    public function __construct(
        protected string $driver
    ) {}

    public function leaveNode(Node $node)
    {
        if (! $node instanceof ArrayItem) {
            return null;
        }

        if (! $node->key instanceof String_ || $node->key->value !== 'disks') {
            return null;
        }

        if (! $node->value instanceof Array_) {
            return null;
        }

        $existingItems = $this->getExistingItems($node->value);
        $backupsItem = $this->createBackupsDiskItem();

        $newArray = new Array_(array_merge($existingItems, [$backupsItem]), ['kind' => Array_::KIND_SHORT]);
        $newArray->setAttribute('multiline', true);
        $newArray->setAttribute('paddedMultiline', true);

        $node->value = $newArray;

        return $node;
    }

    protected function getExistingItems(Array_ $array): array
    {
        $items = [];
        $isFirst = true;

        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if ($item->key instanceof String_ && $item->key->value === 'backups') {
                continue;
            }

            if (! $isFirst) {
                $item->setAttribute('newlineBefore', true);
            }

            $isFirst = false;
            $items[] = $item;
        }

        return $items;
    }

    protected function createBackupsDiskItem(): ArrayItem
    {
        $config = match ($this->driver) {
            'local' => $this->getLocalConfig(),
            'google' => $this->getGoogleConfig(),
            'minio' => $this->getMinioConfig(),
            default => [],
        };

        $items = [];

        foreach ($config as $key => $value) {
            $items[] = new ArrayItem(
                $this->createValue($value),
                new String_($key)
            );
        }

        $backupsArray = new Array_($items, ['kind' => Array_::KIND_SHORT]);
        $backupsArray->setAttribute('multiline', true);

        $backupsItem = new ArrayItem(
            $backupsArray,
            new String_('backups')
        );
        $backupsItem->setAttribute('newlineBefore', true);

        return $backupsItem;
    }

    protected function createValue(mixed $value): Node\Expr
    {
        if (is_bool($value)) {
            return new ConstFetch(new Name($value ? 'true' : 'false'));
        }

        if (is_array($value) && isset($value['func'])) {
            return new FuncCall(
                new Name($value['func']),
                [new Arg(new String_($value['arg']))]
            );
        }

        if (is_array($value) && isset($value['env'])) {
            return new FuncCall(
                new Name('env'),
                [new Arg(new String_($value['env']))]
            );
        }

        return new String_($value);
    }

    protected function getLocalConfig(): array
    {
        return [
            'driver' => 'local',
            'root' => ['func' => 'storage_path', 'arg' => 'backups'],
            'serve' => true,
            'throw' => false,
            'report' => false,
        ];
    }

    protected function getGoogleConfig(): array
    {
        return [
            'driver' => 'google',
            'clientId' => ['env' => 'BACKUP_GOOGLE_CLIENT_ID'],
            'clientSecret' => ['env' => 'BACKUP_GOOGLE_CLIENT_SECRET'],
            'refreshToken' => ['env' => 'BACKUP_GOOGLE_REFRESH_TOKEN'],
            'folder' => ['env' => 'BACKUP_GOOGLE_FOLDER'],
        ];
    }

    protected function getMinioConfig(): array
    {
        return [
            'driver' => 's3',
            'key' => ['env' => 'BACKUP_S3_KEY'],
            'secret' => ['env' => 'BACKUP_S3_SECRET'],
            'region' => ['env' => 'BACKUP_S3_REGION'],
            'bucket' => ['env' => 'BACKUP_S3_BUCKET'],
            'url' => ['env' => 'BACKUP_S3_URL'],
            'endpoint' => ['env' => 'BACKUP_S3_ENDPOINT'],
            'use_path_style_endpoint' => false,
            'throw' => false,
            'report' => false,
        ];
    }
}
