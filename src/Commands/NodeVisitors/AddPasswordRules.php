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

class AddPasswordRules extends NodeVisitorAbstract
{
    protected array $useStatements = [
        'Illuminate\Validation\Rules\Password',
    ];

    protected array $existingUseStatements = [];

    protected bool $hasPasswordRulesMethod = false;

    protected bool $hasPasswordRulesCall = false;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->existingUseStatements[] = $use->name->toString();
            }
        }

        if ($node instanceof ClassMethod && $node->name->name === 'passwordRules') {
            $this->hasPasswordRulesMethod = true;
        }

        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            foreach ($node->stmts ?? [] as $stmt) {
                if ($stmt instanceof Expression && $stmt->expr instanceof MethodCall) {
                    $call = $stmt->expr;

                    if ($call->var instanceof Variable && $call->var->name === 'this') {
                        if ($call->name instanceof Identifier && $call->name->name === 'passwordRules') {
                            $this->hasPasswordRulesCall = true;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot' && ! $this->hasPasswordRulesCall) {
            $passwordRulesCall = new Expression(
                new MethodCall(
                    new Variable('this'),
                    'passwordRules'
                )
            );

            $node->stmts = array_merge([$passwordRulesCall], $node->stmts ?? []);

            return $node;
        }

        if ($node instanceof Class_ && ! $this->hasPasswordRulesMethod) {
            $node->stmts[] = new Nop;

            $node->stmts[] = $this->createPasswordRulesMethod();

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

    protected function createPasswordRulesMethod(): ClassMethod
    {
        $code = <<<'PHP'
<?php
class Temp {
    protected function passwordRules(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });
    }
}
PHP;

        $parser = (new \PhpParser\ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse($code);

        /** @var Class_ $class */
        $class = $ast[0];

        return $class->stmts[0];
    }
}
