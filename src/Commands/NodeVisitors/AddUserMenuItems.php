<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddUserMenuItems extends NodeVisitorAbstract
{
    protected bool $hasUserMenuItems = false;

    protected array $missingUseStatements = [];

    protected array $requiredUseStatements = [
        'Filament\Actions\Action',
        'App\Filament\Pages\ApiTokens',
        'Filament\Support\Icons\Heroicon',
    ];

    public function beforeTraverse(array $nodes)
    {
        $existingUse = $this->findExistingUseStatements($nodes);
        $this->missingUseStatements = array_diff($this->requiredUseStatements, $existingUse);

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
            $this->hasUserMenuItems = $this->methodChainHasCall($node, 'userMenuItems');
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'panel') {
            if (! $this->hasUserMenuItems) {
                $this->addUserMenuItemsToChain($node);
                $node->setAttribute('formatUserMenuItems', true);
            }

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if (empty($this->missingUseStatements)) {
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

    protected function addUserMenuItemsToChain(ClassMethod $method): void
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Return_) {
                continue;
            }

            if (! $this->insertAfterInChain($stmt->expr, 'profile')) {
                $stmt->expr = new MethodCall(
                    $stmt->expr,
                    new Identifier('userMenuItems'),
                    [new Arg($this->createUserMenuItemsArray())]
                );
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
                $current->var = new MethodCall(
                    $current->var,
                    new Identifier('userMenuItems'),
                    [new Arg($this->createUserMenuItemsArray())]
                );

                return true;
            }

            $current = $current->var;
        }

        return false;
    }

    protected function createUserMenuItemsArray(): Array_
    {
        $actionChain = new MethodCall(
            new MethodCall(
                new MethodCall(
                    new StaticCall(
                        new Name('Action'),
                        new Identifier('make'),
                        [new Arg(new String_('api-tokens'))]
                    ),
                    new Identifier('label'),
                    [new Arg(new FuncCall(new Name('__'), [new Arg(new String_('API tokens'))]))]
                ),
                new Identifier('url'),
                [new Arg(new ArrowFunction([
                    'returnType' => new Identifier('string'),
                    'expr' => new StaticCall(
                        new Name('ApiTokens'),
                        new Identifier('getUrl')
                    ),
                ]))]
            ),
            new Identifier('icon'),
            [new Arg(new ClassConstFetch(new Name('Heroicon'), new Identifier('OutlinedKey')))]
        );

        $array = new Array_([
            new ArrayItem($actionChain),
        ], ['kind' => Array_::KIND_SHORT]);

        $array->setAttribute('multiline', true);

        return $array;
    }
}
