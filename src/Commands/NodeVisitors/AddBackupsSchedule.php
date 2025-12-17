<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddBackupsSchedule extends NodeVisitorAbstract
{
    protected bool $hasClean = false;

    protected bool $hasRun = false;

    protected bool $hasScheduleUse = false;

    public function beforeTraverse(array $nodes)
    {
        $this->hasClean = $this->commandExists($nodes, 'backup:clean');
        $this->hasRun = $this->commandExists($nodes, 'backup:run');
        $this->hasScheduleUse = $this->useStatementExists($nodes, 'Illuminate\Support\Facades\Schedule');

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasClean && $this->hasRun) {
            return null;
        }

        if (! $this->hasScheduleUse) {
            $nodes = $this->addUseStatement($nodes, 'Illuminate\Support\Facades\Schedule');
        }

        $needsBlankLine = true;

        if (! $this->hasClean) {
            if ($needsBlankLine) {
                $nodes[] = new Nop;
                $needsBlankLine = false;
            }

            $nodes[] = $this->createScheduleCommand('backup:clean', '02:00');
        }

        if (! $this->hasRun) {
            if ($needsBlankLine) {
                $nodes[] = new Nop;
            }

            $nodes[] = $this->createScheduleCommand('backup:run', '03:00');
        }

        return $nodes;
    }

    protected function commandExists(array $nodes, string $command): bool
    {
        foreach ($nodes as $node) {
            if (! $node instanceof Expression) {
                continue;
            }

            if ($this->isBackupCommand($node->expr, $command)) {
                return true;
            }
        }

        return false;
    }

    protected function isBackupCommand($expr, string $command): bool
    {
        if ($expr instanceof MethodCall) {
            return $this->isBackupCommand($expr->var, $command);
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

        if (isset($expr->args[0]) && $expr->args[0]->value instanceof String_) {
            if ($expr->args[0]->value->value === $command) {
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

    protected function createScheduleCommand(string $command, string $time): Expression
    {
        return new Expression(
            new MethodCall(
                new MethodCall(
                    new StaticCall(
                        new Name('Schedule'),
                        new Identifier('command'),
                        [new Arg(new String_($command))]
                    ),
                    new Identifier('daily')
                ),
                new Identifier('at'),
                [new Arg(new String_($time))]
            )
        );
    }
}
