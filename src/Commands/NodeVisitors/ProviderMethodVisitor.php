<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

abstract class ProviderMethodVisitor extends NodeVisitorAbstract
{
    protected array $useStatements = [];

    protected array $existingUseStatements = [];

    protected bool $hasMethod = false;

    protected bool $hasMethodCall = false;

    abstract protected function getMethodName(): string;

    abstract protected function createMethod(): ClassMethod;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->existingUseStatements[] = $use->name->toString();
            }
        }

        if ($node instanceof ClassMethod && $node->name->name === $this->getMethodName()) {
            $this->hasMethod = true;
        }

        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            foreach ($node->stmts ?? [] as $stmt) {
                if ($stmt instanceof Expression && $stmt->expr instanceof MethodCall) {
                    $call = $stmt->expr;

                    if ($call->var instanceof Variable && $call->var->name === 'this') {
                        if ($call->name instanceof Identifier && $call->name->name === $this->getMethodName()) {
                            $this->hasMethodCall = true;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot' && ! $this->hasMethodCall) {
            $methodCall = new Expression(
                new MethodCall(
                    new Variable('this'),
                    $this->getMethodName()
                )
            );

            // If boot method has existing content, add a blank line after the method call
            if (! empty($node->stmts)) {
                $node->stmts = array_merge([$methodCall, new Nop], $node->stmts);
            } else {
                $node->stmts = [$methodCall];
            }

            return $node;
        }

        if ($node instanceof Class_ && ! $this->hasMethod) {
            $node->stmts[] = new Nop;

            $node->stmts[] = $this->createMethod();

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        $missingUseStatements = array_diff($this->useStatements, $this->existingUseStatements);

        if (empty($missingUseStatements)) {
            return null;
        }

        $newUseStatements = [];

        foreach ($missingUseStatements as $useStatement) {
            $newUseStatements[] = new Use_([
                new UseItem(new Name($useStatement)),
            ]);
        }

        // Handle namespaced files where use statements are inside the Namespace node
        if (isset($nodes[0]) && $nodes[0] instanceof Namespace_) {
            $lastUseIndex = $this->findLastUseIndex($nodes[0]->stmts);

            if ($lastUseIndex === null) {
                return null;
            }

            array_splice($nodes[0]->stmts, $lastUseIndex + 1, 0, $newUseStatements);

            return $nodes;
        }

        // Handle non-namespaced files
        $lastUseIndex = $this->findLastUseIndex($nodes);

        if ($lastUseIndex === null) {
            return null;
        }

        array_splice($nodes, $lastUseIndex + 1, 0, $newUseStatements);

        return $nodes;
    }

    protected function findLastUseIndex(array $nodes): ?int
    {
        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        return $lastUseIndex;
    }
}
