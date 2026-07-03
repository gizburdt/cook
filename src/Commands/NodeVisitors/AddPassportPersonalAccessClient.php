<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddPassportPersonalAccessClient extends NodeVisitorAbstract
{
    protected bool $hasCall = false;

    protected bool $hasUseStatement = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasUseStatement = $this->useStatementExists($nodes, 'Illuminate\Support\Facades\Artisan');

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
        if ($node instanceof ClassMethod && $node->name->name === 'run') {
            $this->hasCall = $this->runHasPassportClientCall($node);
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'run') {
            if (! $this->hasCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createPassportClientCall();
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
                $this->addUseStatementToNamespace($node, 'Illuminate\Support\Facades\Artisan');

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

    protected function runHasPassportClientCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;

            if ($expr instanceof StaticCall
                && $expr->class instanceof Name
                && $expr->class->toString() === 'Artisan'
                && $expr->name instanceof Identifier
                && $expr->name->name === 'call'
                && isset($expr->args[0])
                && $expr->args[0]->value instanceof String_
                && $expr->args[0]->value->value === 'passport:client'
            ) {
                return true;
            }
        }

        return false;
    }

    protected function createPassportClientCall(): Expression
    {
        $options = new Array_([
            new ArrayItem(new ConstFetch(new Name('true')), new String_('--personal')),
            new ArrayItem(new String_('users'), new String_('--provider')),
            new ArrayItem(new ConstFetch(new Name('true')), new String_('--no-interaction')),
        ], ['kind' => Array_::KIND_SHORT]);

        $options->setAttribute('multiline', true);

        return new Expression(
            new StaticCall(
                new Name('Artisan'),
                new Identifier('call'),
                [
                    new Arg(new String_('passport:client')),
                    new Arg($options),
                ]
            )
        );
    }
}
