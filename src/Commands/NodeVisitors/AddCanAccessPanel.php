<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddCanAccessPanel extends NodeVisitorAbstract
{
    protected bool $hasCanAccessPanelMethod = false;

    protected bool $hasFilamentUserInterface = false;

    protected array $missingUseStatements = [];

    protected array $requiredUseStatements = [
        'Filament\Panel',
        'Filament\Models\Contracts\FilamentUser',
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
        if ($node instanceof Class_) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'canAccessPanel') {
                    $this->hasCanAccessPanelMethod = true;
                }
            }

            foreach ($node->implements as $implement) {
                if ($implement->toString() === 'FilamentUser') {
                    $this->hasFilamentUserInterface = true;
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            if (! $this->hasFilamentUserInterface) {
                $node->implements[] = new Name('FilamentUser');
            }

            if (! $this->hasCanAccessPanelMethod) {
                $method = $this->createCanAccessPanelMethod();
                $method->setAttribute('blankLineBefore', true);
                $node->stmts[] = $method;
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

    protected function createCanAccessPanelMethod(): ClassMethod
    {
        return new ClassMethod('canAccessPanel', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'params' => [
                new Param(new Variable('panel'), null, new Name('Panel')),
            ],
            'returnType' => new Identifier('bool'),
            'stmts' => [
                new Return_(new Node\Expr\ConstFetch(new Name('true'))),
            ],
        ]);
    }
}
