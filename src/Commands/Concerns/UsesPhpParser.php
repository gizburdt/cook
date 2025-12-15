<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Gizburdt\Cook\Commands\Support\FormatPreservingPrinter;
use Gizburdt\Cook\Commands\Support\MultilineArrayPrinter;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;

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

        // Only use prettyPrintFile for specific visitors that need it
        // All other visitors should preserve formatting to keep comments and whitespace
        foreach ($visitors as $visitor) {
            $visitorClass = is_string($visitor) ? $visitor : get_class($visitor);

            // AddLocalRoutes needs prettyPrintFile for proper multiline argument formatting
            // AddPasswordRules needs prettyPrintFile for proper multiline method chain formatting
            if (str_contains($visitorClass, 'AddLocalRoutes') || str_contains($visitorClass, 'AddPasswordRules')) {
                return (new MultilineArrayPrinter)->prettyPrintFile($new);
            }
        }

        // For all other cases, use printFormatPreserving to keep comments and formatting
        return (new FormatPreservingPrinter)->printFormatPreserving($new, $old, $tokens);
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
