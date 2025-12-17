<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

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
    protected bool $hasRoute = false;

    protected bool $hasUseStatement = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasRoute = $this->routeExists($nodes);
        $this->hasUseStatement = $this->useStatementExists($nodes);

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasRoute) {
            return null;
        }

        if (! $this->hasUseStatement) {
            $nodes = $this->addUseStatement($nodes);
        }

        $nodes = $this->addRoute($nodes);

        return $nodes;
    }

    protected function routeExists(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Expression) {
                continue;
            }

            if (! $node->expr instanceof StaticCall) {
                continue;
            }

            $call = $node->expr;

            if (! $call->class instanceof Name || $call->class->toString() !== 'Route') {
                continue;
            }

            if (! $call->name instanceof Identifier || $call->name->name !== 'get') {
                continue;
            }

            if (isset($call->args[0]) && $call->args[0]->value instanceof String_) {
                if ($call->args[0]->value->value === 'health') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function useStatementExists(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Use_) {
                continue;
            }

            foreach ($node->uses as $use) {
                if ($use->name->toString() === 'Spatie\Health\Http\Controllers\HealthCheckJsonResultsController') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function addUseStatement(array $nodes): array
    {
        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        $useStatement = new Use_([
            new UseItem(new Name('Spatie\Health\Http\Controllers\HealthCheckJsonResultsController')),
        ]);

        if ($lastUseIndex !== null) {
            array_splice($nodes, $lastUseIndex + 1, 0, [$useStatement]);
        }

        return $nodes;
    }

    protected function addRoute(array $nodes): array
    {
        $route = new Expression(
            new StaticCall(
                new Name('Route'),
                new Identifier('get'),
                [
                    new Arg(new String_('health')),
                    new Arg(new ClassConstFetch(
                        new Name('HealthCheckJsonResultsController'),
                        new Identifier('class')
                    )),
                ]
            )
        );

        $nodes[] = new Nop;
        $nodes[] = $route;

        return $nodes;
    }
}
