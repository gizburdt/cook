<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

class RemoveEloquentModel extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if (! $node instanceof Use_) {
            return null;
        }

        foreach ($node->uses as $use) {
            if ($use->name->toString() === 'Illuminate\Database\Eloquent\Model') {
                return NodeVisitor::REMOVE_NODE;
            }
        }

        return null;
    }
}
