<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddPassportAuthorizationView extends NodeVisitorAbstract
{
    protected bool $hasPassportMethod = false;

    protected bool $hasPassportCall = false;

    protected bool $hasUseStatement = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasUseStatement = $this->useStatementExists($nodes, 'Laravel\Passport\Passport');

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
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'passport') {
                    $this->hasPassportMethod = true;
                }

                if ($stmt instanceof ClassMethod && $stmt->name->name === 'boot') {
                    $this->hasPassportCall = $this->bootHasPassportCall($stmt);
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            if (! $this->hasPassportCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createPassportCall();
            }

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasPassportMethod) {
                $method = $this->createPassportMethod();
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
                $this->addUseStatementToNamespace($node, 'Laravel\Passport\Passport');

                return $nodes;
            }
        }

        return $nodes;
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

    protected function bootHasPassportCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;

            if ($expr instanceof MethodCall
                && $expr->var instanceof Variable
                && $expr->var->name === 'this'
                && $expr->name instanceof Identifier
                && $expr->name->name === 'passport'
            ) {
                return true;
            }
        }

        return false;
    }

    protected function createPassportCall(): Expression
    {
        return new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('passport')
            )
        );
    }

    protected function createPassportMethod(): ClassMethod
    {
        $arrow = new ArrowFunction([
            'params' => [
                new Param(new Variable('parameters'), null, new Identifier('array')),
            ],
            'expr' => new FuncCall(new Name('view'), [
                new Arg(new String_('mcp.authorize')),
                new Arg(new Variable('parameters')),
            ]),
        ]);

        $call = new Expression(
            new StaticCall(
                new Name('Passport'),
                new Identifier('authorizationView'),
                [new Arg($arrow)]
            )
        );

        return new ClassMethod('passport', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'returnType' => new Identifier('void'),
            'stmts' => [$call],
        ]);
    }
}
