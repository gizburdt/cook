<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

class AddMailgunService extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (! $node instanceof Return_ || ! $node->expr instanceof Array_) {
            return null;
        }

        if ($this->hasMailgunService($node->expr)) {
            return null;
        }

        $items = $this->getExistingItems($node->expr);

        $mailgun = $this->createMailgunItem();

        $this->moveLeadingComments($items[0] ?? null, $mailgun);

        array_unshift($items, $mailgun);

        $newArray = new Array_($items, ['kind' => Array_::KIND_SHORT]);
        $newArray->setAttribute('multiline', true);
        $newArray->setAttribute('paddedMultiline', true);

        $node->expr = $newArray;

        return $node;
    }

    /**
     * @return array<int, ArrayItem>
     */
    protected function getExistingItems(Array_ $services): array
    {
        $items = [];

        foreach ($services->items as $item) {
            if ($item === null) {
                continue;
            }

            $item->setAttribute('newlineBefore', true);

            $items[] = $item;
        }

        return $items;
    }

    protected function hasMailgunService(Array_ $services): bool
    {
        foreach ($services->items as $item) {
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
     * The file's docblock hangs on the first item, so it has to travel along
     * when another item is put in front of it.
     */
    protected function moveLeadingComments(?ArrayItem $first, ArrayItem $item): void
    {
        if ($first === null || $first->getComments() === []) {
            return;
        }

        $item->setAttribute('comments', $first->getComments());

        $first->setAttribute('comments', []);
    }

    protected function createMailgunItem(): ArrayItem
    {
        $service = new Array_([
            new ArrayItem($this->env('MAILGUN_DOMAIN'), new String_('domain')),
            new ArrayItem($this->env('MAILGUN_SECRET'), new String_('secret')),
            new ArrayItem($this->env('MAILGUN_ENDPOINT', 'api.mailgun.net'), new String_('endpoint')),
            new ArrayItem(new String_('https'), new String_('scheme')),
        ], ['kind' => Array_::KIND_SHORT]);

        $service->setAttribute('multiline', true);

        return new ArrayItem($service, new String_('mailgun'));
    }

    protected function env(string $key, ?string $default = null): FuncCall
    {
        $arguments = [new Arg(new String_($key))];

        if ($default !== null) {
            $arguments[] = new Arg(new String_($default));
        }

        return new FuncCall(new Name('env'), $arguments);
    }
}
