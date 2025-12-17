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
    protected bool $hasSchedule = false;

    protected bool $hasScheduleUse = false;

    protected bool $hasCommandUse = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasSchedule = $this->scheduleExists($nodes);
        $this->hasScheduleUse = $this->useStatementExists($nodes, 'Illuminate\Support\Facades\Schedule');
        $this->hasCommandUse = $this->useStatementExists($nodes, 'Spatie\Health\Commands\RunHealthChecksCommand');

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasSchedule) {
            return null;
        }

        if (! $this->hasScheduleUse) {
            $nodes = $this->addUseStatement($nodes, 'Illuminate\Support\Facades\Schedule');
        }

        if (! $this->hasCommandUse) {
            $nodes = $this->addUseStatement($nodes, 'Spatie\Health\Commands\RunHealthChecksCommand');
        }

        $nodes = $this->addSchedule($nodes);

        return $nodes;
    }

    protected function scheduleExists(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Expression) {
                continue;
            }

            if ($this->isHealthScheduleCall($node->expr)) {
                return true;
            }
        }

        return false;
    }

    protected function isHealthScheduleCall($expr): bool
    {
        if ($expr instanceof MethodCall) {
            return $this->isHealthScheduleCall($expr->var);
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

            if ($classConst->class instanceof Name && $classConst->class->toString() === 'RunHealthChecksCommand') {
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

    protected function addSchedule(array $nodes): array
    {
        $schedule = new Expression(
            new MethodCall(
                new StaticCall(
                    new Name('Schedule'),
                    new Identifier('command'),
                    [
                        new Arg(new ClassConstFetch(
                            new Name('RunHealthChecksCommand'),
                            new Identifier('class')
                        )),
                    ]
                ),
                new Identifier('everyMinute')
            )
        );

        $nodes[] = new Nop;
        $nodes[] = $schedule;

        return $nodes;
    }
}
