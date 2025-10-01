<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Filesystem\Filesystem;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

abstract class Command extends ConsoleCommand
{
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    protected function createParentDirectory(string $directory): void
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    protected function parseContent(string $content, array $visitors): string
    {
        $parser = $this->newParser();

        [$old, $tokens] = [
            $parser->parse($content),
            $parser->getTokens(),
        ];

        $new = $this->traverse($old, $visitors);

        return (new PrettyPrinter\Standard)->printFormatPreserving($new, $old, $tokens);
    }

    protected function newParser(): \PhpParser\Parser
    {
        return (new ParserFactory)->createForNewestSupportedVersion();
    }

    protected function traverse(array $nodes, array $visitors): array
    {
        $traverser = new NodeTraverser;

        $traverser->addVisitor(new CloningVisitor);

        foreach ($visitors as $visitor) {
            $traverser->addVisitor(new $visitor);
        }

        return $traverser->traverse($nodes);
    }

    abstract public function handle();
}
