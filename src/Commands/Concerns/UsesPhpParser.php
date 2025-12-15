<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Gizburdt\Cook\Commands\Support\MultilineArrayPrinter;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

trait UsesPhpParser
{
    protected function applyPhpVisitors(string $file, array $visitors): void
    {
        $content = $this->files->get($file);

        $content = $this->parsePhpContent($content, $visitors);

        $this->files->put($file, $content);
    }

    protected function parsePhpContent(string $content, array $visitors): string
    {
        $parser = $this->newPhpParser();

        [$old, $tokens] = [
            $parser->parse($content),
            $parser->getTokens(),
        ];

        $new = $this->traversePhpNodes($old, $visitors);

        // Check if using AddLocalRoutes visitor, use MultilineArrayPrinter
        // to ensure proper formatting of method arguments
        foreach ($visitors as $visitor) {
            $visitorClass = is_string($visitor) ? $visitor : get_class($visitor);

            if (str_contains($visitorClass, 'AddLocalRoutes')) {
                return (new MultilineArrayPrinter)->prettyPrintFile($new);
            }
        }

        // Check if new nodes were added (methods, array items, etc.)
        // If so, use MultilineArrayPrinter for better formatting
        // Otherwise use printFormatPreserving to keep comments and formatting
        if ($this->hasNewNodes($old, $new)) {
            return (new MultilineArrayPrinter)->prettyPrintFile($new);
        }

        return (new Standard)->printFormatPreserving($new, $old, $tokens);
    }

    protected function hasNewNodes(array $old, array $new): bool
    {
        // Simple check: serialize and compare
        // New nodes means the structure has changed
        $oldSerialized = serialize($this->getNodeStructure($old));

        $newSerialized = serialize($this->getNodeStructure($new));

        return $oldSerialized !== $newSerialized;
    }

    protected function getNodeStructure(array $nodes): array
    {
        $structure = [];

        foreach ($nodes as $node) {
            $structure[] = $this->getNodeInfo($node);
        }

        return $structure;
    }

    protected function getNodeInfo($node): array
    {
        if (! $node instanceof \PhpParser\Node) {
            return [];
        }

        $info = [
            'type' => get_class($node),
        ];

        // Count class members
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $info['member_count'] = count($node->stmts);
        }

        // Count array items
        if ($node instanceof \PhpParser\Node\Expr\Array_) {
            $info['item_count'] = count($node->items);
        }

        // Recursively check children
        foreach ($node as $child) {
            if ($child instanceof \PhpParser\Node) {
                $info['children'][] = $this->getNodeInfo($child);
            } elseif (is_array($child)) {
                foreach ($child as $item) {
                    if ($item instanceof \PhpParser\Node) {
                        $info['children'][] = $this->getNodeInfo($item);
                    }
                }
            }
        }

        return $info;
    }

    protected function newPhpParser(): Parser
    {
        return (new ParserFactory)->createForNewestSupportedVersion();
    }

    protected function traversePhpNodes(array $nodes, array $visitors): array
    {
        $traverser = new NodeTraverser;

        // Preserve original nodes
        $traverser->addVisitor(new CloningVisitor);

        foreach ($visitors as $visitor) {
            $visitor = is_string($visitor) ? new $visitor : $visitor;

            $traverser->addVisitor($visitor);
        }

        return $traverser->traverse($nodes);
    }
}
