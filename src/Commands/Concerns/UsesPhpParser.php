<?php

namespace Gizburdt\Cook\Commands\Concerns;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

trait UsesPhpParser
{
    protected function applyVisitors(string $file, array $visitors): void
    {
        $content = $this->files->get($file);

        $content = $this->parseContent($content, $visitors);

        $this->files->put($file, $content);
    }

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
            $visitor = is_string($visitor) ? new $visitor : $visitor;

            $traverser->addVisitor($visitor);
        }

        return $traverser->traverse($nodes);
    }
}
