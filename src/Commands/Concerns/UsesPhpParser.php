<?php

namespace Gizburdt\Cook\Commands\Concerns;

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

        return (new Standard)->printFormatPreserving($new, $old, $tokens);
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
