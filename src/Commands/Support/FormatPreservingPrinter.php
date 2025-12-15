<?php

namespace Gizburdt\Cook\Commands\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\PrettyPrinter\Standard;

class FormatPreservingPrinter extends Standard
{
    protected function p(Node $node, int $precedence = self::MAX_PRECEDENCE, int $lhsPrecedence = self::MAX_PRECEDENCE, bool $parentFormatPreserved = false): string
    {
        $result = '';

        // Add blank line before nodes marked with needsBlankLine
        if ($node->getAttribute('needsBlankLine') === true) {
            $result .= $this->nl;
        }

        $result .= parent::p($node, $precedence, $lhsPrecedence, $parentFormatPreserved);

        return $result;
    }

    protected function pExpr_Array(Array_ $node): string
    {
        $syntax = $node->getAttribute('kind', Array_::KIND_SHORT);

        // Check if we should format as multiline (more than 3 items)
        if (count($node->items) > 3) {
            if ($syntax === Array_::KIND_SHORT) {
                return '['.$this->pCommaSeparatedMultiline($node->items, true).']';
            }

            return 'array('.$this->pCommaSeparatedMultiline($node->items, true).')';
        }

        // Use default formatting for smaller arrays
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

            // Add extra newline for nodes marked with needsBlankLine
            if ($node->getAttribute('needsBlankLine') === true) {
                $result .= $this->nl;
            }

            $result .= $this->nl.$this->p($node).',';
        }

        $this->outdent();

        return $result.$this->nl;
    }
}
