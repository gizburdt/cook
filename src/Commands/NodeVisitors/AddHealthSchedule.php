<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
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
    protected bool $hasScheduleUse = false;

    protected bool $hasHealthCommandUse = false;

    protected bool $hasScheduleCommand = false;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $name = $use->name->toString();

                if ($name === 'Illuminate\Support\Facades\Schedule') {
                    $this->hasScheduleUse = true;
                }

                if ($name === 'Spatie\Health\Commands\RunHealthChecksCommand') {
                    $this->hasHealthCommandUse = true;
                }
            }
        }

        if ($node instanceof Expression && $node->expr instanceof MethodCall) {
            $methodCall = $node->expr;

            if ($methodCall->var instanceof StaticCall) {
                $staticCall = $methodCall->var;

                if ($staticCall->class instanceof Name && $staticCall->class->toString() === 'Schedule') {
                    if ($staticCall->name instanceof Identifier && $staticCall->name->name === 'command') {
                        if (isset($staticCall->args[0]) && $staticCall->args[0]->value instanceof ClassConstFetch) {
                            $classConst = $staticCall->args[0]->value;

                            if ($classConst->class instanceof Name && $classConst->class->toString() === 'RunHealthChecksCommand') {
                                $this->hasScheduleCommand = true;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasScheduleUse && $this->hasHealthCommandUse && $this->hasScheduleCommand) {
            return null;
        }

        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        $insertOffset = 0;

        if (! $this->hasScheduleUse && $lastUseIndex !== null) {
            $useStatement = new Use_([
                new UseItem(new Name('Illuminate\Support\Facades\Schedule')),
            ]);

            array_splice($nodes, $lastUseIndex + 1 + $insertOffset, 0, [$useStatement]);
            $insertOffset++;
        }

        if (! $this->hasHealthCommandUse && $lastUseIndex !== null) {
            $useStatement = new Use_([
                new UseItem(new Name('Spatie\Health\Commands\RunHealthChecksCommand')),
            ]);

            array_splice($nodes, $lastUseIndex + 1 + $insertOffset, 0, [$useStatement]);
        }

        if (! $this->hasScheduleCommand) {
            $scheduleCommand = new Expression(
                new MethodCall(
                    new StaticCall(
                        new Name('Schedule'),
                        'command',
                        [
                            new Arg(
                                new ClassConstFetch(
                                    new Name('RunHealthChecksCommand'),
                                    new Identifier('class')
                                )
                            ),
                        ]
                    ),
                    'everyMinute'
                )
            );

            $nodes[] = new Nop;
            $nodes[] = $scheduleCommand;
        }

        return $nodes;
    }
}
