<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddFilamentConfiguration extends NodeVisitorAbstract
{
    protected bool $hasFilamentMethod = false;

    protected bool $hasFilamentCall = false;

    protected array $missingUseStatements = [];

    protected array $requiredUseStatements = [
        'Filament\Tables\Table',
        'Filament\Forms\Components\TextInput',
        'Filament\Infolists\Components\TextEntry',
        'Filament\Tables\Columns\TextColumn',
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
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'filament') {
                    $this->hasFilamentMethod = true;
                }

                if ($stmt instanceof ClassMethod && $stmt->name->name === 'boot') {
                    $this->hasFilamentCall = $this->bootHasFilamentCall($stmt);
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            if (! $this->hasFilamentCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createFilamentCall();
            }

            return $node;
        }

        if ($node instanceof ClassMethod && $node->name->name === 'filament') {
            $this->addFilamentConfigurationStatements($node);

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasFilamentMethod) {
                $method = $this->createFilamentMethod();
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

    protected function bootHasFilamentCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if ($this->isFilamentCall($stmt->expr)) {
                return true;
            }
        }

        return false;
    }

    protected function isFilamentCall($expr): bool
    {
        if (! $expr instanceof MethodCall) {
            return false;
        }

        if (! $expr->var instanceof Variable || $expr->var->name !== 'this') {
            return false;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'filament') {
            return false;
        }

        return true;
    }

    protected function createFilamentCall(): Expression
    {
        return new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('filament')
            )
        );
    }

    protected function addFilamentConfigurationStatements(ClassMethod $method): void
    {
        $hasTable = $this->methodHasConfigureUsing($method, 'Table');
        $hasTextInput = $this->methodHasConfigureUsing($method, 'TextInput');
        $hasTextEntry = $this->methodHasConfigureUsing($method, 'TextEntry');
        $hasTextColumn = $this->methodHasConfigureUsing($method, 'TextColumn');

        $needsBlankLine = ! empty($method->stmts);

        if (! $hasTable) {
            if ($needsBlankLine) {
                $method->stmts[] = new Nop;
            }

            $method->stmts[] = $this->createTableConfigureUsing();
            $needsBlankLine = true;
        }

        if (! $hasTextInput) {
            if ($needsBlankLine) {
                $method->stmts[] = new Nop;
            }

            $method->stmts[] = $this->createTextInputConfigureUsing();
            $needsBlankLine = true;
        }

        if (! $hasTextEntry) {
            if ($needsBlankLine) {
                $method->stmts[] = new Nop;
            }

            $method->stmts[] = $this->createTextEntryConfigureUsing();
            $needsBlankLine = true;
        }

        if (! $hasTextColumn) {
            if ($needsBlankLine) {
                $method->stmts[] = new Nop;
            }

            $method->stmts[] = $this->createTextColumnConfigureUsing();
        }
    }

    protected function methodHasConfigureUsing(ClassMethod $method, string $className): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if ($stmt->expr instanceof StaticCall) {
                $call = $stmt->expr;

                if ($call->class instanceof Name && $call->class->toString() === $className) {
                    if ($call->name instanceof Identifier && $call->name->name === 'configureUsing') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function createFilamentMethod(): ClassMethod
    {
        $method = new ClassMethod('filament', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'returnType' => new Identifier('void'),
            'stmts' => [],
        ]);

        $this->addFilamentConfigurationStatements($method);

        return $method;
    }

    protected function createTableConfigureUsing(): Expression
    {
        $paginationOptions = new Array_([
            new ArrayItem(new Int_(10)),
            new ArrayItem(new Int_(25)),
            new ArrayItem(new Int_(50)),
            new ArrayItem(new Int_(100)),
        ], ['kind' => Array_::KIND_SHORT]);

        $tableChain = new MethodCall(
            new MethodCall(
                new Variable('table'),
                new Identifier('paginationPageOptions'),
                [new Arg($paginationOptions)]
            ),
            new Identifier('defaultPaginationPageOption'),
            [new Arg(new Int_(50))]
        );

        $closure = new Closure([
            'params' => [new Param(new Variable('table'), null, new Name('Table'))],
            'stmts' => [new Expression($tableChain)],
        ]);

        return new Expression(
            new StaticCall(
                new Name('Table'),
                new Identifier('configureUsing'),
                [new Arg($closure)]
            )
        );
    }

    protected function createTextInputConfigureUsing(): Expression
    {
        $maxLengthCall = new Expression(
            new MethodCall(
                new Variable('input'),
                new Identifier('maxLength'),
                [new Arg(new Int_(255))]
            )
        );

        $closure = new Closure([
            'params' => [new Param(new Variable('input'), null, new Name('TextInput'))],
            'stmts' => [$maxLengthCall],
        ]);

        return new Expression(
            new StaticCall(
                new Name('TextInput'),
                new Identifier('configureUsing'),
                [new Arg($closure)]
            )
        );
    }

    protected function createTextEntryConfigureUsing(): Expression
    {
        $placeholderCall = new Expression(
            new MethodCall(
                new Variable('entry'),
                new Identifier('placeholder'),
                [new Arg(new Node\Scalar\String_('-'))]
            )
        );

        $closure = new Closure([
            'params' => [new Param(new Variable('entry'), null, new Name('TextEntry'))],
            'stmts' => [$placeholderCall],
        ]);

        return new Expression(
            new StaticCall(
                new Name('TextEntry'),
                new Identifier('configureUsing'),
                [new Arg($closure)]
            )
        );
    }

    protected function createTextColumnConfigureUsing(): Expression
    {
        $placeholderCall = new Expression(
            new MethodCall(
                new Variable('column'),
                new Identifier('placeholder'),
                [new Arg(new Node\Scalar\String_('-'))]
            )
        );

        $closure = new Closure([
            'params' => [new Param(new Variable('column'), null, new Name('TextColumn'))],
            'stmts' => [$placeholderCall],
        ]);

        return new Expression(
            new StaticCall(
                new Name('TextColumn'),
                new Identifier('configureUsing'),
                [new Arg($closure)]
            )
        );
    }
}
