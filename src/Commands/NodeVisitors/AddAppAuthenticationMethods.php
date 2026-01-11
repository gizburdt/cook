<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddAppAuthenticationMethods extends NodeVisitorAbstract
{
    protected array $traitsToAdd = [
        'InteractsWithAppAuthentication',
        'InteractsWithAppAuthenticationRecovery',
    ];

    protected array $useStatementsToAdd = [
        'Filament\Auth\MultiFactor\App\InteractsWithAppAuthentication',
        'Filament\Auth\MultiFactor\App\InteractsWithAppAuthenticationRecovery',
    ];

    protected array $existingUseStatements = [];

    protected array $existingTraits = [];

    public function beforeTraverse(array $nodes): ?array
    {
        $this->existingUseStatements = $this->getExistingUseStatements($nodes);

        return null;
    }

    protected function getExistingUseStatements(array $nodes): array
    {
        $useStatements = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $useStatements = array_merge($useStatements, $this->getExistingUseStatements($node->stmts));
            }

            if ($node instanceof Use_) {
                foreach ($node->uses as $use) {
                    $useStatements[] = $use->name->toString();
                }
            }
        }

        return $useStatements;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        $this->existingTraits[] = $trait->toString();
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            $this->addMissingTraits($node);

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $this->addMissingUseStatements($node);

                return $nodes;
            }
        }

        return $nodes;
    }

    protected function addMissingUseStatements(Node\Stmt\Namespace_ $namespace): void
    {
        $lastUseIndex = null;

        foreach ($namespace->stmts as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        foreach ($this->useStatementsToAdd as $class) {
            if (in_array($class, $this->existingUseStatements)) {
                continue;
            }

            $useStatement = new Use_([
                new UseItem(new Name($class)),
            ]);

            if ($lastUseIndex !== null) {
                $lastUseIndex++;

                array_splice($namespace->stmts, $lastUseIndex, 0, [$useStatement]);
            }
        }
    }

    protected function addMissingTraits(Class_ $class): void
    {
        $traitsToAdd = [];

        foreach ($this->traitsToAdd as $trait) {
            if (in_array($trait, $this->existingTraits)) {
                continue;
            }

            $traitsToAdd[] = new Name($trait);
        }

        if (empty($traitsToAdd)) {
            return;
        }

        $traitUse = new TraitUse($traitsToAdd);

        array_unshift($class->stmts, $traitUse);
    }
}
