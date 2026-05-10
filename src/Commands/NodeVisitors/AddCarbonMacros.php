<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddCarbonMacros extends NodeVisitorAbstract
{
    protected bool $hasCarbonMacrosMethod = false;

    protected bool $hasCarbonMacrosCall = false;

    protected array $missingUseStatements = [];

    protected array $requiredUseStatements = [
        'Carbon\Carbon',
        'Carbon\CarbonImmutable',
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
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'carbonMacros') {
                    $this->hasCarbonMacrosMethod = true;
                }

                if ($stmt instanceof ClassMethod && $stmt->name->name === 'boot') {
                    $this->hasCarbonMacrosCall = $this->bootHasCarbonMacrosCall($stmt);
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            if (! $this->hasCarbonMacrosCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createCarbonMacrosCall();
            }

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasCarbonMacrosMethod) {
                $method = $this->createCarbonMacrosMethod();
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

    protected function bootHasCarbonMacrosCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if ($this->isCarbonMacrosCall($stmt->expr)) {
                return true;
            }
        }

        return false;
    }

    protected function isCarbonMacrosCall($expr): bool
    {
        if (! $expr instanceof MethodCall) {
            return false;
        }

        if (! $expr->var instanceof Variable || $expr->var->name !== 'this') {
            return false;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'carbonMacros') {
            return false;
        }

        return true;
    }

    protected function createCarbonMacrosCall(): Expression
    {
        return new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('carbonMacros')
            )
        );
    }

    protected function createCarbonMacrosMethod(): ClassMethod
    {
        $return = new Return_(
            new MethodCall(
                new MethodCall(
                    new Variable('this'),
                    new Identifier('copy')
                ),
                new Identifier('timezone'),
                [new Arg(
                    new FuncCall(
                        new Name('config'),
                        [new Arg(new String_('app.timezone'))]
                    )
                )]
            )
        );
        $return->setDocComment(new Doc('/** @var Carbon|CarbonImmutable $this */'));

        $closure = new Closure([
            'stmts' => [$return],
        ]);

        $assign = new Expression(
            new Assign(new Variable('display'), $closure)
        );

        $carbonMacro = new Expression(
            new StaticCall(
                new Name('Carbon'),
                new Identifier('macro'),
                [
                    new Arg(new String_('display')),
                    new Arg(new Variable('display')),
                ]
            )
        );

        $carbonImmutableMacro = new Expression(
            new StaticCall(
                new Name('CarbonImmutable'),
                new Identifier('macro'),
                [
                    new Arg(new String_('display')),
                    new Arg(new Variable('display')),
                ]
            )
        );

        return new ClassMethod('carbonMacros', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'returnType' => new Identifier('void'),
            'stmts' => [
                $assign,
                new Nop,
                $carbonMacro,
                new Nop,
                $carbonImmutableMacro,
            ],
        ]);
    }
}
