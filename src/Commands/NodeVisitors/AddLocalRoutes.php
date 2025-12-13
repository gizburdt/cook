<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddLocalRoutes extends NodeVisitorAbstract
{
    protected bool $hasLocalRoutes = false;

    protected bool $hasUseRoute = false;

    protected array $existingUseStatements = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->existingUseStatements[] = $use->name->toString();

                if ($use->name->toString() === 'Illuminate\Support\Facades\Route') {
                    $this->hasUseRoute = true;
                }
            }
        }

        if ($node instanceof String_ && $node->value === 'routes/local.php') {
            $this->hasLocalRoutes = true;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($this->hasLocalRoutes) {
            return null;
        }

        if (! $node instanceof MethodCall) {
            return null;
        }

        if (! $node->name instanceof Identifier || $node->name->name !== 'withRouting') {
            return null;
        }

        $thenArg = $this->createThenArgument();

        $node->args[] = $thenArg;

        return $node;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasLocalRoutes || $this->hasUseRoute) {
            return null;
        }

        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        if ($lastUseIndex === null) {
            return null;
        }

        $useStatement = new Use_([
            new UseItem(new Name('Illuminate\Support\Facades\Route')),
        ]);

        array_splice($nodes, $lastUseIndex + 1, 0, [$useStatement]);

        return $nodes;
    }

    protected function createThenArgument(): Arg
    {
        $ifCondition = new FuncCall(
            new Name('app'),
            []
        );

        $ifCondition = new MethodCall(
            $ifCondition,
            'environment',
            [new Arg(new String_('local'))]
        );

        $routeCall = new StaticCall(
            new Name('Route'),
            'middleware',
            [new Arg(new String_('web'))]
        );

        $routeCall = new MethodCall(
            $routeCall,
            'group',
            [
                new Arg(
                    new FuncCall(
                        new Name('base_path'),
                        [new Arg(new String_('routes/local.php'))]
                    )
                ),
            ]
        );

        $ifStmt = new If_(
            $ifCondition,
            [
                'stmts' => [
                    new Expression($routeCall),
                ],
            ]
        );

        $closure = new Closure([
            'stmts' => [$ifStmt],
        ]);

        return new Arg(
            $closure,
            false,
            false,
            [],
            new Identifier('then')
        );
    }
}
