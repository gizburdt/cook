<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

class AddAdminPanelProvider extends NodeVisitorAbstract
{
    protected string $providerClass = 'App\Providers\Filament\AdminPanelProvider';

    protected bool $alreadyExists = false;

    public function enterNode(Node $node)
    {
        if (! $node instanceof Return_) {
            return null;
        }

        if (! $node->expr instanceof Array_) {
            return null;
        }

        foreach ($node->expr->items as $item) {
            if (! $item instanceof ArrayItem) {
                continue;
            }

            if ($this->isAdminPanelProvider($item->value)) {
                $this->alreadyExists = true;

                return null;
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($this->alreadyExists) {
            return null;
        }

        if (! $node instanceof Return_) {
            return null;
        }

        if (! $node->expr instanceof Array_) {
            return null;
        }

        $items = [];

        foreach ($node->expr->items as $item) {
            $items[] = $item;
        }

        $newItem = new ArrayItem(
            new ClassConstFetch(
                new Name($this->providerClass),
                new Identifier('class')
            )
        );
        $items[] = $newItem;

        $newArray = new Array_($items, ['kind' => Array_::KIND_SHORT]);
        $newArray->setAttribute('multiline', true);

        $node->expr = $newArray;

        return $node;
    }

    protected function isAdminPanelProvider(Node $node): bool
    {
        if (! $node instanceof ClassConstFetch) {
            return false;
        }

        if (! $node->class instanceof Name) {
            return false;
        }

        return $node->class->toString() === $this->providerClass;
    }
}
