<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddHealthChecks extends NodeVisitorAbstract
{
    protected bool $hasHealthChecksMethod = false;

    protected bool $hasHealthChecksCall = false;

    protected array $missingUseStatements = [];

    protected array $requiredUseStatements = [
        'Spatie\Health\Facades\Health',
        'Spatie\Health\Checks\Checks\CacheCheck',
        'Spatie\Health\Checks\Checks\DatabaseCheck',
        'Spatie\Health\Checks\Checks\DatabaseSizeCheck',
        'Spatie\Health\Checks\Checks\RedisCheck',
        'Spatie\Health\Checks\Checks\RedisMemoryUsageCheck',
        'Spatie\CpuLoadHealthCheck\CpuLoadCheck',
        'Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck',
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
                if ($stmt instanceof ClassMethod && $stmt->name->name === 'healthChecks') {
                    $this->hasHealthChecksMethod = true;
                }

                if ($stmt instanceof ClassMethod && $stmt->name->name === 'boot') {
                    $this->hasHealthChecksCall = $this->bootHasHealthChecksCall($stmt);
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            if (! $this->hasHealthChecksCall) {
                $node->stmts[] = new Nop;
                $node->stmts[] = $this->createHealthChecksCall();
            }

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasHealthChecksMethod) {
                $method = $this->createHealthChecksMethod();
                $method->setAttribute('blankLineBefore', true);
                $method->setAttribute('formatHealthChecks', true);
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

        $hasNamespace = false;

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $hasNamespace = true;

                foreach ($this->missingUseStatements as $class) {
                    $this->addUseStatementToNamespace($node, $class);
                }

                return $nodes;
            }
        }

        if (! $hasNamespace) {
            return $this->addUseStatementsToFile($nodes);
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

    protected function addUseStatementsToFile(array $nodes): array
    {
        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        foreach ($this->missingUseStatements as $class) {
            $useStatement = new Use_([
                new UseItem(new Name($class)),
            ]);

            if ($lastUseIndex !== null) {
                $lastUseIndex++;
                array_splice($nodes, $lastUseIndex, 0, [$useStatement]);
            }
        }

        return $nodes;
    }

    protected function bootHasHealthChecksCall(ClassMethod $method): bool
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if ($this->isHealthChecksCall($stmt->expr)) {
                return true;
            }
        }

        return false;
    }

    protected function isHealthChecksCall($expr): bool
    {
        if (! $expr instanceof MethodCall) {
            return false;
        }

        if (! $expr->var instanceof Variable || $expr->var->name !== 'this') {
            return false;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'healthChecks') {
            return false;
        }

        return true;
    }

    protected function createHealthChecksCall(): Expression
    {
        return new Expression(
            new MethodCall(
                new Variable('this'),
                new Identifier('healthChecks')
            )
        );
    }

    protected function createHealthChecksMethod(): ClassMethod
    {
        $healthChecks = new Array_([
            new ArrayItem($this->createSimpleCheck('CacheCheck')),
            new ArrayItem($this->createCpuLoadCheck()),
            new ArrayItem($this->createSimpleCheck('DatabaseCheck')),
            new ArrayItem($this->createSimpleCheck('DatabaseSizeCheck')),
            new ArrayItem($this->createSimpleCheck('RedisCheck')),
            new ArrayItem($this->createRedisMemoryUsageCheck()),
            new ArrayItem($this->createSimpleCheck('SecurityAdvisoriesCheck')),
        ], ['kind' => Array_::KIND_SHORT]);

        $healthChecksCall = new Expression(
            new StaticCall(
                new Name('Health'),
                new Identifier('checks'),
                [new Arg($healthChecks)]
            )
        );

        return new ClassMethod('healthChecks', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'returnType' => new Identifier('void'),
            'stmts' => [$healthChecksCall],
        ]);
    }

    protected function createSimpleCheck(string $className): StaticCall
    {
        return new StaticCall(
            new Name($className),
            new Identifier('new')
        );
    }

    protected function createCpuLoadCheck(): MethodCall
    {
        return new MethodCall(
            new MethodCall(
                new StaticCall(
                    new Name('CpuLoadCheck'),
                    new Identifier('new')
                ),
                new Identifier('failWhenLoadIsHigherInTheLast5Minutes'),
                [new Arg(new Float_(2.0))]
            ),
            new Identifier('failWhenLoadIsHigherInTheLast15Minutes'),
            [new Arg(new Float_(1.5))]
        );
    }

    protected function createRedisMemoryUsageCheck(): MethodCall
    {
        return new MethodCall(
            new MethodCall(
                new StaticCall(
                    new Name('RedisMemoryUsageCheck'),
                    new Identifier('new')
                ),
                new Identifier('warnWhenAboveMb'),
                [new Arg(new Int_(900))]
            ),
            new Identifier('failWhenAboveMb'),
            [new Arg(new Int_(1000))]
        );
    }
}
