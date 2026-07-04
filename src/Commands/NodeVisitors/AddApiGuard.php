<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class AddApiGuard extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (! $node instanceof ArrayItem
            || ! $node->key instanceof String_
            || $node->key->value !== 'guards'
            || ! $node->value instanceof Array_
        ) {
            return null;
        }

        if ($this->forceExistingApiGuardToPassport($node->value)) {
            return $node;
        }

        $existingItems = $this->getExistingItems($node->value);

        $newArray = new Array_(array_merge($existingItems, [$this->createApiGuardItem()]), ['kind' => Array_::KIND_SHORT]);
        $newArray->setAttribute('multiline', true);

        $node->value = $newArray;

        return $node;
    }

    protected function forceExistingApiGuardToPassport(Array_ $guards): bool
    {
        foreach ($guards->items as $item) {
            if ($item instanceof ArrayItem
                && $item->key instanceof String_
                && $item->key->value === 'api'
                && $item->value instanceof Array_
            ) {
                $this->forceDriverToPassport($item->value);

                return true;
            }
        }

        return false;
    }

    protected function forceDriverToPassport(Array_ $guard): void
    {
        foreach ($guard->items as $item) {
            if ($item instanceof ArrayItem
                && $item->key instanceof String_
                && $item->key->value === 'driver'
            ) {
                $item->value = new String_('passport');
            }
        }
    }

    protected function getExistingItems(Array_ $guards): array
    {
        $items = [];
        $isFirst = true;

        foreach ($guards->items as $item) {
            if ($item === null) {
                continue;
            }

            if (! $isFirst) {
                $item->setAttribute('newlineBefore', true);
            }

            $isFirst = false;
            $items[] = $item;
        }

        return $items;
    }

    protected function createApiGuardItem(): ArrayItem
    {
        $guard = new Array_([
            new ArrayItem(new String_('passport'), new String_('driver')),
            new ArrayItem(new String_('users'), new String_('provider')),
        ], ['kind' => Array_::KIND_SHORT]);

        $guard->setAttribute('multiline', true);

        $item = new ArrayItem($guard, new String_('api'));

        $item->setAttribute('newlineBefore', true);

        return $item;
    }
}
