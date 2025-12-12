<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class AddHealthChecks extends ProviderMethodVisitor
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

    protected function getMethodName(): string
    {
        return 'healthChecks';
    }

    protected function createMethod(): ClassMethod
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
