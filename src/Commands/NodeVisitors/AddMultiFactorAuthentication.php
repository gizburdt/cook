<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use Gizburdt\Cook\Enums\MfaMethod;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddMultiFactorAuthentication extends NodeVisitorAbstract
{
    protected bool $hasMultiFactorAuthentication = false;

    protected bool $inserted = false;

    protected array $missingUseStatements = [];

    /**
     * @param  array<int, MfaMethod>  $methods
     */
    public function __construct(protected array $methods) {}

    protected function shortName(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');

        return $position === false ? $fqcn : substr($fqcn, $position + 1);
    }

    public function beforeTraverse(array $nodes)
    {
        if (empty($this->methods)) {
            return null;
        }

        $existingUse = $this->findExistingUseStatements($nodes);

        $required = [];

        foreach ($this->methods as $method) {
            $required[] = $method->panelClass();
        }

        $this->missingUseStatements = array_values(array_diff($required, $existingUse));

        return null;
    }

    protected function findExistingUseStatements(array $nodes): array
    {
        $existing = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                return $this->findExistingUseStatements($node->stmts);
            }

            if ($node instanceof Use_) {
                foreach ($node->uses as $use) {
                    $existing[] = $use->name->toString();
                }
            }
        }

        return $existing;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'panel') {
            $this->hasMultiFactorAuthentication = $this->methodChainHasCall($node, 'multiFactorAuthentication');
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'panel') {
            if (! empty($this->methods)) {
                if ($this->hasMultiFactorAuthentication) {
                    $this->replaceEntries($node);
                } else {
                    $this->addToChain($node);
                }

                $node->setAttribute('formatMultiFactorAuthentication', true);

                $this->inserted = true;
            }

            return $node;
        }

        return null;
    }

    protected function replaceEntries(ClassMethod $method): void
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Return_) {
                continue;
            }

            $current = $stmt->expr;

            while ($current instanceof MethodCall) {
                if ($current->name instanceof Identifier
                    && $current->name->name === 'multiFactorAuthentication'
                ) {
                    $current->args[0] = new Arg($this->createEntriesArray());

                    return;
                }

                $current = $current->var;
            }
        }
    }

    protected function methodChainHasCall(ClassMethod $method, string $methodName): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Return_) {
                continue;
            }

            $expr = $stmt->expr;

            while ($expr instanceof MethodCall) {
                if ($expr->name instanceof Identifier && $expr->name->name === $methodName) {
                    return true;
                }

                $expr = $expr->var;
            }
        }

        return false;
    }

    protected function addToChain(ClassMethod $method): void
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Return_) {
                continue;
            }

            if (! $this->insertAfterInChain($stmt->expr, 'profile')) {
                $stmt->expr = $this->wrap($stmt->expr);
            }
        }
    }

    protected function insertAfterInChain(MethodCall $expr, string $afterMethod): bool
    {
        $current = $expr;

        while ($current instanceof MethodCall) {
            if ($current->var instanceof MethodCall
                && $current->var->name instanceof Identifier
                && $current->var->name->name === $afterMethod
            ) {
                $current->var = $this->wrap($current->var);

                return true;
            }

            $current = $current->var;
        }

        return false;
    }

    protected function wrap(Node\Expr $var): MethodCall
    {
        return new MethodCall(
            $var,
            new Identifier('multiFactorAuthentication'),
            [
                new Arg($this->createEntriesArray()),
                new Arg(
                    new MethodCall(
                        new FuncCall(new Name('app')),
                        new Identifier('isProduction')
                    ),
                    false,
                    false,
                    [],
                    new Identifier('isRequired')
                ),
            ]
        );
    }

    protected function createEntriesArray(): Array_
    {
        $items = [];

        foreach ($this->methods as $method) {
            $make = new StaticCall(
                new Name($this->shortName($method->panelClass())),
                new Identifier('make')
            );

            $expr = $method->panelRecoverable()
                ? new MethodCall($make, new Identifier('recoverable'))
                : $make;

            $items[] = new ArrayItem($expr);
        }

        $array = new Array_($items, ['kind' => Array_::KIND_SHORT]);

        $array->setAttribute('multiline', true);

        return $array;
    }

    public function afterTraverse(array $nodes)
    {
        if (! $this->inserted || empty($this->missingUseStatements)) {
            return null;
        }

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                foreach ($this->missingUseStatements as $class) {
                    $this->addUseStatementToNamespace($node, $class);
                }

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
}
