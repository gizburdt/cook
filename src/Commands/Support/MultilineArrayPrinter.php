<?php

namespace Gizburdt\Cook\Commands\Support;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\If_;
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
        // Check if this method has multiple named arguments
        if ($this->hasMultipleNamedArgs($node->args)) {
            return $this->pMethodCallWithMultilineArgs($node);
        }

        // Check if this is a chained method call with more than one method
        if ($this->hasMultipleMethodCalls($node)) {
            return $this->pMethodCallMultiline($node);
        }

        return parent::pExpr_MethodCall($node);
    }

    protected function pExpr_StaticCall(StaticCall $node): string
    {
        // Check if this static call has multiple named arguments
        if ($this->hasMultipleNamedArgs($node->args)) {
            return $this->pStaticCallWithMultilineArgs($node);
        }

        return parent::pExpr_StaticCall($node);
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
                // Check if we should use multiline formatting for args
                if ($this->hasMultipleNamedArgs($call['args'])) {
                    $result .= '(';

                    $this->indent();

                    foreach ($call['args'] as $arg) {
                        $result .= $this->nl;

                        if ($arg->name !== null) {
                            $result .= $this->p($arg->name).': ';
                        }

                        $result .= $this->p($arg->value).',';
                    }

                    $this->outdent();

                    $result .= $this->nl.')';
                } else {
                    $result .= '('.$this->pMaybeMultiline($call['args']).')';
                }
            } else {
                $result .= '()';
            }
        }

        $this->outdent();

        return $result;
    }

    protected function pExpr_Closure(Closure $node): string
    {
        $result = 'function';

        if ($node->static) {
            $result = 'static '.$result;
        }

        if ($node->byRef) {
            $result .= '&';
        }

        $result .= '(';

        if (! empty($node->params)) {
            $result .= $this->pCommaSeparated($node->params);
        }

        $result .= ')';

        if (! empty($node->uses)) {
            $result .= ' use('.$this->pCommaSeparated($node->uses).')';
        }

        if ($node->returnType !== null) {
            $result .= ': '.$this->p($node->returnType);
        }

        $result .= ' {';

        $this->indent();

        foreach ($node->stmts as $stmt) {
            $result .= $this->nl.$this->p($stmt);
        }

        $this->outdent();

        $result .= $this->nl.'}';

        return $result;
    }

    protected function pStmt_If(If_ $node): string
    {
        $result = 'if ('.$this->p($node->cond).') {';

        $this->indent();

        foreach ($node->stmts as $stmt) {
            $result .= $this->nl.$this->p($stmt);
        }

        $this->outdent();

        $result .= $this->nl.'}';

        if (! empty($node->elseifs)) {
            foreach ($node->elseifs as $elseif) {
                $result .= ' '.$this->p($elseif);
            }
        }

        if ($node->else !== null) {
            $result .= ' '.$this->p($node->else);
        }

        return $result;
    }

    protected function hasMultipleNamedArgs(array $args): bool
    {
        if (count($args) <= 1) {
            return false;
        }

        // Check if any argument has a name (named argument)
        $hasNamedArgs = false;

        foreach ($args as $arg) {
            if ($arg->name !== null) {
                $hasNamedArgs = true;

                break;
            }
        }

        return $hasNamedArgs;
    }

    protected function pMethodCallWithMultilineArgs(MethodCall $node): string
    {
        $result = $this->p($node->var).'->'.$this->p($node->name).'(';

        if (! empty($node->args)) {
            $this->indent();

            foreach ($node->args as $arg) {
                $result .= $this->nl;

                if ($arg->name !== null) {
                    $result .= $this->p($arg->name).': ';
                }

                $result .= $this->p($arg->value).',';
            }

            $this->outdent();

            $result .= $this->nl;
        }

        $result .= ')';

        return $result;
    }

    protected function pStaticCallWithMultilineArgs(StaticCall $node): string
    {
        $result = $this->p($node->class).'::'.$this->p($node->name).'(';

        if (! empty($node->args)) {
            $this->indent();

            foreach ($node->args as $arg) {
                $result .= $this->nl;

                if ($arg->name !== null) {
                    $result .= $this->p($arg->name).': ';
                }

                $result .= $this->p($arg->value).',';
            }

            $this->outdent();

            $result .= $this->nl;
        }

        $result .= ')';

        return $result;
    }
}
