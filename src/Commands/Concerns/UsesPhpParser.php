<?php

namespace Gizburdt\Cook\Commands\Concerns;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

trait UsesPhpParser
{
    protected function parseContent(string $content, array $visitors): string
    {
        $parser = $this->newParser();

        [$old, $tokens] = [
            $parser->parse($content),
            $parser->getTokens(),
        ];

        $new = $this->traverse($old, $visitors);

        return (new Standard)->printFormatPreserving($new, $old, $tokens);
    }

    protected function newParser(): \PhpParser\Parser
    {
        return (new ParserFactory)->createForNewestSupportedVersion();
    }

    protected function traverse(array $nodes, array $visitors): array
    {
        $traverser = new NodeTraverser;

        // Preserve original nodes
        $traverser->addVisitor(new CloningVisitor);

        foreach ($visitors as $visitor) {
            $traverser->addVisitor(is_string($visitor) ? new $visitor : $visitor);
        }

        return $traverser->traverse($nodes);
    }
}
