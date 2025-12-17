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
    protected bool $hasRouteUse = false;

    protected bool $hasLocalRoutes = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasRouteUse = $this->hasUseStatement($nodes, 'Illuminate\Support\Facades\Route');
        $this->hasLocalRoutes = $this->hasLocalRoutesInWithRouting($nodes);

        return null;
    }

    protected function hasUseStatement(array $nodes, string $class): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Use_) {
                foreach ($node->uses as $use) {
                    if ($use->name->toString() === $class) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function hasLocalRoutesInWithRouting(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($this->nodeContainsLocalRoutes($node)) {
                return true;
            }
        }

        return false;
    }

    protected function nodeContainsLocalRoutes(Node $node): bool
    {
        if ($node instanceof String_ && str_contains($node->value, 'routes/local.php')) {
            return true;
        }

        foreach ($node->getSubNodeNames() as $name) {
            $subNode = $node->$name;

            if (is_array($subNode)) {
                foreach ($subNode as $child) {
                    if ($child instanceof Node && $this->nodeContainsLocalRoutes($child)) {
                        return true;
                    }
                }
            } elseif ($subNode instanceof Node && $this->nodeContainsLocalRoutes($subNode)) {
                return true;
            }
        }

        return false;
    }

    public function leaveNode(Node $node)
    {
        if ($this->hasLocalRoutes) {
            return null;
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier && $node->name->name === 'withRouting') {
            $this->addLocalRoutesToWithRouting($node);

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasRouteUse) {
            return null;
        }

        return $this->addUseStatement($nodes, 'Illuminate\Support\Facades\Route');
    }

    protected function addUseStatement(array $nodes, string $class): array
    {
        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        $useStatement = new Use_([
            new UseItem(new Name($class)),
        ]);

        if ($lastUseIndex !== null) {
            array_splice($nodes, $lastUseIndex + 1, 0, [$useStatement]);
        }

        return $nodes;
    }

    protected function addLocalRoutesToWithRouting(MethodCall $node): void
    {
        $thenArg = $this->findThenArg($node);

        if ($thenArg === null) {
            $node->args[] = new Arg(
                $this->createThenClosure(),
                false,
                false,
                [],
                new Identifier('then')
            );
        } else {
            $this->addLocalRoutesToThenClosure($thenArg);
        }
    }

    protected function findThenArg(MethodCall $node): ?Arg
    {
        foreach ($node->args as $arg) {
            if ($arg->name instanceof Identifier && $arg->name->name === 'then') {
                return $arg;
            }
        }

        return null;
    }

    protected function addLocalRoutesToThenClosure(Arg $arg): void
    {
        if (! $arg->value instanceof Closure) {
            return;
        }

        $arg->value->stmts[] = $this->createLocalRoutesIf();
    }

    protected function createThenClosure(): Closure
    {
        return new Closure([
            'stmts' => [$this->createLocalRoutesIf()],
        ]);
    }

    protected function createLocalRoutesIf(): If_
    {
        $condition = new MethodCall(
            new FuncCall(new Name('app')),
            new Identifier('environment'),
            [new Arg(new String_('local'))]
        );

        $routeCall = new Expression(
            new MethodCall(
                new StaticCall(
                    new Name('Route'),
                    new Identifier('middleware'),
                    [new Arg(new String_('web'))]
                ),
                new Identifier('group'),
                [new Arg(new FuncCall(new Name('base_path'), [new Arg(new String_('routes/local.php'))]))]
            )
        );

        return new If_($condition, [
            'stmts' => [$routeCall],
        ]);
    }
}
