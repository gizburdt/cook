<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\NodeVisitorAbstract;

class AddBackupsDisk extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if (! $node instanceof ArrayItem) {
            return null;
        }

        if ($node->getAttribute('origNode')->key->value !== 'disks') {
            return null;
        }

        dd($node);

        // $newDisk = new Array_([
        //     new ArrayItem(new String_('s3'), new String_('driver')),
        //     new ArrayItem(new FuncCall(new Name('env'), [new Arg(new String_('MINIO_KEY'))]),    new String_('key')),
        //     new ArrayItem(new FuncCall(new Name('env'), [new Arg(new String_('MINIO_SECRET'))]), new String_('secret')),
        //     new ArrayItem(new FuncCall(new Name('env'), [new Arg(new String_('MINIO_REGION'))]), new String_('region')),
        //     new ArrayItem(new FuncCall(new Name('env'), [new Arg(new String_('MINIO_BUCKET'))]), new String_('bucket')),
        //     new ArrayItem(new FuncCall(new Name('env'), [new Arg(new String_('MINIO_ENDPOINT'))]), new String_('endpoint')),
        //     new ArrayItem(new ConstFetch(new Name('true')),  new String_('use_path_style_endpoint')),
        //     new ArrayItem(new ConstFetch(new Name('false')), new String_('throw')),
        // ], [], $kind);
    }
}
