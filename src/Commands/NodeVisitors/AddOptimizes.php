<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddOptimizes extends NodeVisitorAbstract
{
    protected bool $hasOptimizeMethod = false;

    protected bool $hasOptimizesCall = false;

    protected bool $hasUseStatement = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasUseStatement = $this->useStatementExists($nodes, 'App\Console\Commands\Optimize');

        return null;
    }

    protected function useStatementExists(array $nodes, string $class): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                if ($this->useStatementExists($node->stmts, $class)) {
                    return true;
                }
            }

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

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'optimize') {
                    $this->hasOptimizeMethod = true;
                }

                if ($stmt instanceof ClassMethod && $stmt->name->name === 'boot') {
                    $this->hasOptimizesCall = $this->bootHasOptimizesCall($stmt);
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            if (! $this->hasOptimizesCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createOptimizesCall();
            }

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasOptimizeMethod) {
                $method = $this->createOptimizesMethod();
                $method->setAttribute('blankLineBefore', true);
                $node->stmts[] = $method;
            }

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasUseStatement) {
            return null;
        }

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $this->addUseStatementToNamespace($node, 'App\Console\Commands\Optimize');

                return $nodes;
            }
        }

        return $this->addUseStatement($nodes, 'App\Console\Commands\Optimize');
    }

    protected function addUseStatementToNamespace(Node\Stmt\Namespace_ $namespace, string $class): void
    {
        $lastUseIndex = null;

        foreach ($namespace->stmts as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        $useStatement = new Use_([
            new UseItem(new Name($class)),
        ]);

        if ($lastUseIndex !== null) {
            array_splice($namespace->stmts, $lastUseIndex + 1, 0, [$useStatement]);
        }
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

    protected function bootHasOptimizesCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if ($this->isOptimizesCall($stmt->expr)) {
                return true;
            }
        }

        return false;
    }

    protected function isOptimizesCall($expr): bool
    {
        if (! $expr instanceof MethodCall) {
            return false;
        }

        if (! $expr->var instanceof Variable || $expr->var->name !== 'this') {
            return false;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'optimize') {
            return false;
        }

        return true;
    }

    protected function createOptimizesCall(): Expression
    {
        return new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('optimize')
            )
        );
    }

    protected function createOptimizesMethod(): ClassMethod
    {
        $optimizeArg = new Arg(
            new ClassConstFetch(
                new Name('Optimize'),
                new Identifier('class')
            ),
            name: new Identifier('optimize')
        );

        $optimizesCall = new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('optimizes'),
                [$optimizeArg]
            )
        );

        return new ClassMethod('optimize', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'returnType' => new Identifier('void'),
            'stmts' => [$optimizesCall],
        ]);
    }
}
