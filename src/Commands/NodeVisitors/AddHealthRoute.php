<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddHealthRoute extends NodeVisitorAbstract
{
    protected bool $hasUseStatement = false;

    protected bool $hasRoute = false;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                if ($use->name->toString() === 'Spatie\Health\Http\Controllers\HealthCheckJsonResultsController') {
                    $this->hasUseStatement = true;
                }
            }
        }

        if ($node instanceof Expression && $node->expr instanceof StaticCall) {
            $call = $node->expr;

            if ($call->name instanceof Identifier && $call->name->name === 'get') {
                if (isset($call->args[0]) && $call->args[0]->value instanceof String_) {
                    if ($call->args[0]->value->value === 'health') {
                        $this->hasRoute = true;
                    }
                }
            }
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasUseStatement && $this->hasRoute) {
            return null;
        }

        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        if (! $this->hasUseStatement && $lastUseIndex !== null) {
            $useStatement = new Use_([
                new UseItem(new Name('Spatie\Health\Http\Controllers\HealthCheckJsonResultsController')),
            ]);

            array_splice($nodes, $lastUseIndex + 1, 0, [$useStatement]);
        }

        if (! $this->hasRoute) {
            $route = new Expression(
                new StaticCall(
                    new Name('Route'),
                    'get',
                    [
                        new Arg(new String_('health')),
                        new Arg(
                            new ClassConstFetch(
                                new Name('HealthCheckJsonResultsController'),
                                new Identifier('class')
                            )
                        ),
                    ]
                )
            );

            $nodes[] = new Nop;

            $nodes[] = $route;
        }

        return $nodes;
    }
}
