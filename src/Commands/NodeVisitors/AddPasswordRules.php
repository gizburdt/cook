<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddPasswordRules extends NodeVisitorAbstract
{
    protected bool $hasPasswordRulesMethod = false;

    protected bool $hasPasswordRulesCall = false;

    protected bool $hasUseStatement = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasUseStatement = $this->useStatementExists($nodes, 'Illuminate\Validation\Rules\Password');

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
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'passwordRules') {
                    $this->hasPasswordRulesMethod = true;
                }

                if ($stmt instanceof ClassMethod && $stmt->name->name === 'boot') {
                    $this->hasPasswordRulesCall = $this->bootHasPasswordRulesCall($stmt);
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            if (! $this->hasPasswordRulesCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createPasswordRulesCall();
            }

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasPasswordRulesMethod) {
                $method = $this->createPasswordRulesMethod();
                $method->setAttribute('blankLineBefore', true);
                $method->setAttribute('formatChain', true);
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
                $this->addUseStatementToNamespace($node, 'Illuminate\Validation\Rules\Password');

                return $nodes;
            }
        }

        return $this->addUseStatement($nodes, 'Illuminate\Validation\Rules\Password');
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

    protected function bootHasPasswordRulesCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if ($this->isPasswordRulesCall($stmt->expr)) {
                return true;
            }
        }

        return false;
    }

    protected function isPasswordRulesCall($expr): bool
    {
        if (! $expr instanceof MethodCall) {
            return false;
        }

        if (! $expr->var instanceof Variable || $expr->var->name !== 'this') {
            return false;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'passwordRules') {
            return false;
        }

        return true;
    }

    protected function createPasswordRulesCall(): Expression
    {
        return new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('passwordRules')
            )
        );
    }

    protected function createPasswordRulesMethod(): ClassMethod
    {
        $passwordChain = new MethodCall(
            new MethodCall(
                new MethodCall(
                    new StaticCall(
                        new Name('Password'),
                        new Identifier('min'),
                        [new Arg(new Node\Scalar\Int_(8))]
                    ),
                    new Identifier('mixedCase')
                ),
                new Identifier('numbers')
            ),
            new Identifier('symbols')
        );

        $closure = new Closure([
            'stmts' => [
                new Return_($passwordChain),
            ],
        ]);

        $defaultsCall = new Expression(
            new StaticCall(
                new Name('Password'),
                new Identifier('defaults'),
                [new Arg($closure)]
            )
        );

        $method = new ClassMethod('passwordRules', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'returnType' => new Identifier('void'),
            'stmts' => [$defaultsCall],
        ]);

        return $method;
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
}
