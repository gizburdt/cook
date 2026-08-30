<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class AddMailgunMailer extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (! $node instanceof ArrayItem
            || ! $node->key instanceof String_
            || $node->key->value !== 'mailers'
            || ! $node->value instanceof Array_
            || ! $this->isMailerList($node->value)
        ) {
            return null;
        }

        if ($this->hasMailgunMailer($node->value)) {
            return null;
        }

        $items = $this->getExistingItems($node->value);

        array_splice($items, $this->position($items), 0, [$this->createMailgunItem()]);

        $newArray = new Array_($items, ['kind' => Array_::KIND_SHORT]);
        $newArray->setAttribute('multiline', true);
        $newArray->setAttribute('paddedMultiline', true);

        $node->value = $newArray;

        return $node;
    }

    /**
     * The "failover" and "roundrobin" mailers hold a "mailers" key as well, but
     * theirs is a plain list of mailer names instead of a map of definitions.
     */
    protected function isMailerList(Array_ $mailers): bool
    {
        foreach ($mailers->items as $item) {
            if (! $item instanceof ArrayItem
                || ! $item->key instanceof String_
                || ! $item->value instanceof Array_
            ) {
                return false;
            }
        }

        return $mailers->items !== [];
    }

    protected function hasMailgunMailer(Array_ $mailers): bool
    {
        foreach ($mailers->items as $item) {
            if ($item instanceof ArrayItem
                && $item->key instanceof String_
                && $item->key->value === 'mailgun'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, ArrayItem>
     */
    protected function getExistingItems(Array_ $mailers): array
    {
        $items = [];
        $isFirst = true;

        foreach ($mailers->items as $item) {
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

    /**
     * @param  array<int, ArrayItem>  $items
     */
    protected function position(array $items): int
    {
        foreach ($items as $index => $item) {
            if ($item->key instanceof String_ && $item->key->value === 'ses') {
                return $index;
            }
        }

        return count($items);
    }

    protected function createMailgunItem(): ArrayItem
    {
        $mailer = new Array_([
            new ArrayItem(new String_('mailgun'), new String_('transport')),
        ], ['kind' => Array_::KIND_SHORT]);

        $mailer->setAttribute('multiline', true);

        $item = new ArrayItem($mailer, new String_('mailgun'));

        $item->setAttribute('newlineBefore', true);

        return $item;
    }
}
