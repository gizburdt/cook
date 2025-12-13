<?php

namespace Gizburdt\Cook\Commands\Support;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\PrettyPrinter\Standard;

class MultilineArrayPrinter extends Standard
{
    protected function pExpr_Array(Array_ $node): string
    {
        $syntax = $node->getAttribute('kind', Array_::KIND_SHORT);

        // Check if we should format as multiline (more than 1 item)
        if (count($node->items) > 1) {
            if ($syntax === Array_::KIND_SHORT) {
                return '['.$this->pCommaSeparatedMultiline($node->items, true).']';
            }

            return 'array('.$this->pCommaSeparatedMultiline($node->items, true).')';
        }

        // Single item or empty - use default formatting
        return parent::pExpr_Array($node);
    }

    protected function pCommaSeparatedMultiline(array $nodes, bool $trailingComma): string
    {
        if (empty($nodes)) {
            return '';
        }

        $this->indent();

        $result = '';

        foreach ($nodes as $node) {
            if ($node === null) {
                continue;
            }

            $result .= $this->nl.$this->p($node).',';
        }

        $this->outdent();

        return $result.$this->nl;
    }

    protected function pExpr_MethodCall(MethodCall $node): string
    {
        // Check if this is a chained method call with more than one method
        if ($this->hasMultipleMethodCalls($node)) {
            return $this->pMethodCallMultiline($node);
        }

        return parent::pExpr_MethodCall($node);
    }

    protected function hasMultipleMethodCalls(MethodCall $node): bool
    {
        $count = 0;
        $current = $node;

        while ($current instanceof MethodCall) {
            $count++;
            $current = $current->var;
        }

        return $count > 1;
    }

    protected function pMethodCallMultiline(MethodCall $node): string
    {
        // Collect all method calls in the chain
        $calls = [];
        $current = $node;

        while ($current instanceof MethodCall) {
            $calls[] = [
                'name' => $current->name,
                'args' => $current->args,
            ];
            $current = $current->var;
        }

        // Reverse to get the correct order (base -> chained methods)
        $calls = array_reverse($calls);

        // Start with the base (could be StaticCall or Variable)
        $result = $this->p($current);

        // Add each method call on a new line with extra indent
        $this->indent();

        foreach ($calls as $call) {
            $result .= $this->nl.'->'.$this->p($call['name']);

            if (! empty($call['args'])) {
                $result .= '('.$this->pMaybeMultiline($call['args']).')';
            } else {
                $result .= '()';
            }
        }

        $this->outdent();

        return $result;
    }
}
