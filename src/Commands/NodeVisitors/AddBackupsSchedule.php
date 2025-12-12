<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
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
    protected bool $hasScheduleUse = false;

    protected bool $hasBackupCleanCommand = false;

    protected bool $hasBackupRunCommand = false;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $name = $use->name->toString();

                if ($name === 'Illuminate\Support\Facades\Schedule') {
                    $this->hasScheduleUse = true;
                }
            }
        }

        if ($node instanceof Expression && $node->expr instanceof MethodCall) {
            $this->checkForBackupCommand($node->expr);
        }

        return null;
    }

    protected function checkForBackupCommand(MethodCall $methodCall): void
    {
        $current = $methodCall;

        while ($current instanceof MethodCall) {
            if ($current->var instanceof StaticCall) {
                $staticCall = $current->var;

                if ($staticCall->class instanceof Name && $staticCall->class->toString() === 'Schedule') {
                    if ($staticCall->name instanceof Identifier && $staticCall->name->name === 'command') {
                        if (isset($staticCall->args[0]) && $staticCall->args[0]->value instanceof String_) {
                            $commandName = $staticCall->args[0]->value->value;

                            if ($commandName === 'backup:clean') {
                                $this->hasBackupCleanCommand = true;
                            }

                            if ($commandName === 'backup:run') {
                                $this->hasBackupRunCommand = true;
                            }
                        }
                    }
                }

                break;
            }

            $current = $current->var;
        }
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasScheduleUse && $this->hasBackupCleanCommand && $this->hasBackupRunCommand) {
            return null;
        }

        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        if (! $this->hasScheduleUse && $lastUseIndex !== null) {
            $useStatement = new Use_([
                new UseItem(new Name('Illuminate\Support\Facades\Schedule')),
            ]);

            array_splice($nodes, $lastUseIndex + 1, 0, [$useStatement]);
        }

        if (! $this->hasBackupCleanCommand || ! $this->hasBackupRunCommand) {
            $nodes[] = new Nop;
        }

        if (! $this->hasBackupCleanCommand) {
            $nodes[] = $this->createBackupCleanCommand();
        }

        if (! $this->hasBackupRunCommand) {
            $nodes[] = new Nop;

            $nodes[] = $this->createBackupRunCommand();
        }

        return $nodes;
    }

    protected function createBackupCleanCommand(): Expression
    {
        return new Expression(
            new MethodCall(
                new MethodCall(
                    new StaticCall(
                        new Name('Schedule'),
                        'command',
                        [
                            new Arg(new String_('backup:clean')),
                        ]
                    ),
                    'daily'
                ),
                'at',
                [
                    new Arg(new String_('02:00')),
                ]
            )
        );
    }

    protected function createBackupRunCommand(): Expression
    {
        return new Expression(
            new MethodCall(
                new MethodCall(
                    new StaticCall(
                        new Name('Schedule'),
                        'command',
                        [
                            new Arg(new String_('backup:run')),
                        ]
                    ),
                    'daily'
                ),
                'at',
                [
                    new Arg(new String_('03:00')),
                ]
            )
        );
    }
}
