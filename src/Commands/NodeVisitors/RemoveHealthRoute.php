<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;

class RemoveHealthRoute extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (! $node instanceof MethodCall) {
            return null;
        }

        if (! $node->name instanceof Identifier || $node->name->name !== 'withRouting') {
            return null;
        }

        $node->args = array_filter($node->args, function (Arg $arg) {
            if (! $arg->name instanceof Identifier) {
                return true;
            }

            return $arg->name->name !== 'health';
        });

        $node->args = array_values($node->args);

        return $node;
    }
}
