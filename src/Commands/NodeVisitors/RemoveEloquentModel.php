<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

class RemoveEloquentModel extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            if ($node->uses[0]->name->name === Model::class) {
                return NodeVisitor::REMOVE_NODE;
            }
        }
    }
}
