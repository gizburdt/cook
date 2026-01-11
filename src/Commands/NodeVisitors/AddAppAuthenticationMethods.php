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
    protected bool $hasAppAuthenticationInterface = false;

    protected bool $hasAppAuthenticationRecoveryInterface = false;

    protected bool $hasInteractsWithAppAuthenticationTrait = false;

    protected bool $hasInteractsWithAppAuthenticationRecoveryTrait = false;

    protected array $missingUseStatements = [];

    protected array $requiredUseStatements = [
        'Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication',
        'Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery',
        'Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication',
        'Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery',
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
                if ($stmt instanceof TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        if ($trait->toString() === 'InteractsWithAppAuthentication') {
                            $this->hasInteractsWithAppAuthenticationTrait = true;
                        }

                        if ($trait->toString() === 'InteractsWithAppAuthenticationRecovery') {
                            $this->hasInteractsWithAppAuthenticationRecoveryTrait = true;
                        }
                    }
                }
            }

            foreach ($node->implements as $implement) {
                if ($implement->toString() === 'HasAppAuthentication') {
                    $this->hasAppAuthenticationInterface = true;
                }

                if ($implement->toString() === 'HasAppAuthenticationRecovery') {
                    $this->hasAppAuthenticationRecoveryInterface = true;
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            if (! $this->hasAppAuthenticationInterface) {
                $node->implements[] = new Name('HasAppAuthentication');
            }

            if (! $this->hasAppAuthenticationRecoveryInterface) {
                $node->implements[] = new Name('HasAppAuthenticationRecovery');
            }

            $this->addTraitUses($node);

            return $node;
        }

        return null;
    }

    protected function addTraitUses(Class_ $node): void
    {
        if ($this->hasInteractsWithAppAuthenticationTrait && $this->hasInteractsWithAppAuthenticationRecoveryTrait) {
            return;
        }

        $lastTraitIndex = null;

        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof TraitUse) {
                $lastTraitIndex = $index;
            }
        }

        $traitsToAdd = [];

        if (! $this->hasInteractsWithAppAuthenticationTrait) {
            $traitsToAdd[] = new TraitUse([new Name('InteractsWithAppAuthentication')]);
        }

        if (! $this->hasInteractsWithAppAuthenticationRecoveryTrait) {
            $traitsToAdd[] = new TraitUse([new Name('InteractsWithAppAuthenticationRecovery')]);
        }

        if (empty($traitsToAdd)) {
            return;
        }

        if ($lastTraitIndex !== null) {
            array_splice($node->stmts, $lastTraitIndex + 1, 0, $traitsToAdd);
        } else {
            $node->stmts = array_merge($traitsToAdd, $node->stmts);
        }
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
}
