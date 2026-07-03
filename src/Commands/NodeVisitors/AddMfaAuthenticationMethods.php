<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use Gizburdt\Cook\Enums\MfaMethod;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddMfaAuthenticationMethods extends NodeVisitorAbstract
{
    protected array $requiredContracts = [];

    protected array $requiredTraits = [];

    protected array $missingUseStatements = [];

    /**
     * @param  array<int, MfaMethod>  $methods
     */
    public function __construct(protected array $methods)
    {
        foreach ($this->methods as $method) {
            $this->requiredContracts = array_merge($this->requiredContracts, $method->contracts());
            $this->requiredTraits = array_merge($this->requiredTraits, $method->traits());
        }
    }

    /**
     * @param  array<int, MfaMethod>  $methods
     */
    public static function make(array $methods): static
    {
        return new static($methods);
    }

    protected function shortName(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');

        return $position === false ? $fqcn : substr($fqcn, $position + 1);
    }

    public function beforeTraverse(array $nodes)
    {
        $existingUse = $this->findExistingUseStatements($nodes);

        $required = array_merge($this->requiredContracts, $this->requiredTraits);

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

    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->addImplements($node);

            $this->addTraitUses($node);

            return $node;
        }

        return null;
    }

    protected function addImplements(Class_ $node): void
    {
        $existing = [];

        foreach ($node->implements as $implement) {
            $existing[] = $implement->toString();
        }

        foreach ($this->requiredContracts as $contract) {
            $short = $this->shortName($contract);

            if (! in_array($short, $existing, true)) {
                $node->implements[] = new Name($short);

                $existing[] = $short;
            }
        }
    }

    protected function addTraitUses(Class_ $node): void
    {
        $existing = [];

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $existing[] = $trait->toString();
                }
            }
        }

        $traitsToAdd = [];

        foreach ($this->requiredTraits as $trait) {
            $short = $this->shortName($trait);

            if (! in_array($short, $existing, true)) {
                $traitsToAdd[] = new TraitUse([new Name($short)]);

                $existing[] = $short;
            }
        }

        if (empty($traitsToAdd)) {
            return;
        }

        $lastTraitIndex = null;

        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof TraitUse) {
                $lastTraitIndex = $index;
            }
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
