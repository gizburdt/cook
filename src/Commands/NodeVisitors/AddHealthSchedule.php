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
    protected bool $hasHealthSchedule = false;

    protected bool $hasHeartbeatSchedule = false;

    protected bool $hasScheduleUse = false;

    protected bool $hasHealthCommandUse = false;

    protected bool $hasHeartbeatCommandUse = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasHealthSchedule = $this->healthScheduleExists($nodes);
        $this->hasHeartbeatSchedule = $this->heartbeatScheduleExists($nodes);
        $this->hasScheduleUse = $this->useStatementExists($nodes, 'Illuminate\Support\Facades\Schedule');
        $this->hasHealthCommandUse = $this->useStatementExists($nodes, 'Spatie\Health\Commands\RunHealthChecksCommand');
        $this->hasHeartbeatCommandUse = $this->useStatementExists($nodes, 'Spatie\Health\Commands\ScheduleCheckHeartbeatCommand');

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasHealthSchedule && $this->hasHeartbeatSchedule) {
            return null;
        }

        if (! $this->hasScheduleUse) {
            $nodes = $this->addUseStatement($nodes, 'Illuminate\Support\Facades\Schedule');
        }

        if (! $this->hasHealthCommandUse && ! $this->hasHealthSchedule) {
            $nodes = $this->addUseStatement($nodes, 'Spatie\Health\Commands\RunHealthChecksCommand');
        }

        if (! $this->hasHeartbeatCommandUse && ! $this->hasHeartbeatSchedule) {
            $nodes = $this->addUseStatement($nodes, 'Spatie\Health\Commands\ScheduleCheckHeartbeatCommand');
        }

        $nodes = $this->addSchedules($nodes);

        return $nodes;
    }

    protected function healthScheduleExists(array $nodes): bool
    {
        return $this->scheduleExistsForCommand($nodes, 'RunHealthChecksCommand');
    }

    protected function heartbeatScheduleExists(array $nodes): bool
    {
        return $this->scheduleExistsForCommand($nodes, 'ScheduleCheckHeartbeatCommand');
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

    protected function addSchedules(array $nodes): array
    {
        $addedAny = false;

        if (! $this->hasHealthSchedule) {
            $nodes[] = new Nop;
            $nodes[] = $this->createScheduleExpression('RunHealthChecksCommand');
            $addedAny = true;
        }

        if (! $this->hasHeartbeatSchedule) {
            if (! $addedAny) {
                $nodes[] = new Nop;
            }

            $nodes[] = $this->createScheduleExpression('ScheduleCheckHeartbeatCommand');
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
