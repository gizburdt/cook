<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddHealthSchedule extends NodeVisitorAbstract
{
    /**
     * Health-commando's die elke minuut moeten draaien, in deze volgorde.
     * DispatchQueueCheckJobsCommand zet de testjob op de queue waar
     * QueueCheck op wacht; zonder die schedule faalt QueueCheck altijd.
     *
     * @var array<int, string>
     */
    protected array $commands = [
        'RunHealthChecksCommand',
        'ScheduleCheckHeartbeatCommand',
        'DispatchQueueCheckJobsCommand',
    ];

    protected string $namespace = 'Spatie\Health\Commands';

    /**
     * @var array<string, bool>
     */
    protected array $scheduled = [];

    /**
     * @var array<string, bool>
     */
    protected array $imported = [];

    protected bool $hasScheduleUse = false;

    public function beforeTraverse(array $nodes)
    {
        foreach ($this->commands as $command) {
            $this->scheduled[$command] = $this->scheduleExistsForCommand($nodes, $command);
            $this->imported[$command] = $this->useStatementExists($nodes, "{$this->namespace}\\{$command}");
        }

        $this->hasScheduleUse = $this->useStatementExists($nodes, 'Illuminate\Support\Facades\Schedule');

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        $missing = array_values(array_filter($this->commands, fn (string $command) => ! $this->scheduled[$command]));

        if ($missing === []) {
            return null;
        }

        if (! $this->hasScheduleUse) {
            $nodes = $this->addUseStatement($nodes, 'Illuminate\Support\Facades\Schedule');
        }

        foreach ($missing as $command) {
            if (! $this->imported[$command]) {
                $nodes = $this->addUseStatement($nodes, "{$this->namespace}\\{$command}");
            }
        }

        return $this->addSchedules($nodes, $missing);
    }

    protected function scheduleExistsForCommand(array $nodes, string $commandClass): bool
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Expression) {
                continue;
            }

            if ($this->isScheduleCallForCommand($node->expr, $commandClass)) {
                return true;
            }
        }

        return false;
    }

    protected function isScheduleCallForCommand($expr, string $commandClass): bool
    {
        if ($expr instanceof MethodCall) {
            return $this->isScheduleCallForCommand($expr->var, $commandClass);
        }

        if (! $expr instanceof StaticCall) {
            return false;
        }

        if (! $expr->class instanceof Name || $expr->class->toString() !== 'Schedule') {
            return false;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'command') {
            return false;
        }

        if (isset($expr->args[0]) && $expr->args[0]->value instanceof ClassConstFetch) {
            $classConst = $expr->args[0]->value;

            if ($classConst->class instanceof Name && $classConst->class->toString() === $commandClass) {
                return true;
            }
        }

        return false;
    }

    protected function useStatementExists(array $nodes, string $class): bool
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Use_) {
                continue;
            }

            foreach ($node->uses as $use) {
                if ($use->name->toString() === $class) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function addUseStatement(array $nodes, string $class): array
    {
        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        $useStatement = new Use_([
            new UseItem(new Name($class)),
        ]);

        if ($lastUseIndex !== null) {
            array_splice($nodes, $lastUseIndex + 1, 0, [$useStatement]);
        }

        return $nodes;
    }

    /**
     * @param  array<int, string>  $commands
     */
    protected function addSchedules(array $nodes, array $commands): array
    {
        $nodes[] = new Nop;

        foreach ($commands as $command) {
            $nodes[] = $this->createScheduleExpression($command);
        }

        return $nodes;
    }

    protected function createScheduleExpression(string $commandClass): Expression
    {
        return new Expression(
            new MethodCall(
                new StaticCall(
                    new Name('Schedule'),
                    new Identifier('command'),
                    [
                        new Arg(new ClassConstFetch(
                            new Name($commandClass),
                            new Identifier('class')
                        )),
                    ]
                ),
                new Identifier('everyMinute')
            )
        );
    }
}
