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
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddHealthChecks extends NodeVisitorAbstract
{
    protected array $useStatements = [
        'Spatie\CpuLoadHealthCheck\CpuLoadCheck',
        'Spatie\Health\Checks\Checks\CacheCheck',
        'Spatie\Health\Checks\Checks\DatabaseCheck',
        'Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck',
        'Spatie\Health\Checks\Checks\DatabaseSizeCheck',
        'Spatie\Health\Checks\Checks\DebugModeCheck',
        'Spatie\Health\Checks\Checks\EnvironmentCheck',
        'Spatie\Health\Checks\Checks\HorizonCheck',
        'Spatie\Health\Checks\Checks\OptimizedAppCheck',
        'Spatie\Health\Checks\Checks\QueueCheck',
        'Spatie\Health\Checks\Checks\RedisCheck',
        'Spatie\Health\Checks\Checks\RedisMemoryUsageCheck',
        'Spatie\Health\Checks\Checks\ScheduleCheck',
        'Spatie\Health\Checks\Checks\UsedDiskSpaceCheck',
        'Spatie\Health\Facades\Health',
        'Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck',
    ];

    protected array $existingUseStatements = [];

    protected bool $hasHealthChecksMethod = false;

    protected bool $hasHealthChecksCall = false;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->existingUseStatements[] = $use->name->toString();
            }
        }

        if ($node instanceof ClassMethod && $node->name->name === 'healthChecks') {
            $this->hasHealthChecksMethod = true;
        }

        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            foreach ($node->stmts ?? [] as $stmt) {
                if ($stmt instanceof Expression && $stmt->expr instanceof MethodCall) {
                    $call = $stmt->expr;

                    if ($call->var instanceof Variable && $call->var->name === 'this') {
                        if ($call->name instanceof Identifier && $call->name->name === 'healthChecks') {
                            $this->hasHealthChecksCall = true;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot' && ! $this->hasHealthChecksCall) {
            $healthChecksCall = new Expression(
                new MethodCall(
                    new Variable('this'),
                    'healthChecks'
                )
            );

            $node->stmts = array_merge([$healthChecksCall], $node->stmts ?? []);

            return $node;
        }

        if ($node instanceof Class_ && ! $this->hasHealthChecksMethod) {
            $node->stmts[] = new Nop;

            $node->stmts[] = $this->createHealthChecksMethod();

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

        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        if ($lastUseIndex === null) {
            return null;
        }

        $newUseStatements = [];

        foreach ($missingUseStatements as $useStatement) {
            $newUseStatements[] = new Use_([
                new UseItem(new Name($useStatement)),
            ]);
        }

        array_splice($nodes, $lastUseIndex + 1, 0, $newUseStatements);

        return $nodes;
    }

    protected function createHealthChecksMethod(): ClassMethod
    {
        $code = <<<'PHP'
<?php
class Temp {
    protected function healthChecks(): void
    {
        Health::checks([
            CacheCheck::new(),
            CpuLoadCheck::new()
                ->failWhenLoadIsHigherInTheLast5Minutes(2.0)
                ->failWhenLoadIsHigherInTheLast15Minutes(1.5),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            DatabaseCheck::new(),
            DatabaseSizeCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            HorizonCheck::new(),
            OptimizedAppCheck::new(),
            QueueCheck::new(),
            RedisCheck::new(),
            RedisMemoryUsageCheck::new()
                ->warnWhenAboveMb(900)
                ->failWhenAboveMb(1000),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
            SecurityAdvisoriesCheck::new(),
            UsedDiskSpaceCheck::new(),
        ]);
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
