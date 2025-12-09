<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\NodeVisitorAbstract;

it('parses and preserves php content', function () {
    $parser = createPhpParser();

    $content = <<<'PHP'
<?php

echo 'Hello World';
PHP;

    $result = $parser->testParseContent($content, []);

    expect($result)
        ->toContain('echo')
        ->toContain('Hello World');
});

it('applies visitor to modify content', function () {
    $parser = createPhpParser();

    $content = <<<'PHP'
<?php

echo 'original';
PHP;

    $result = $parser->testParseContent($content, [
        ReplaceEchoVisitor::class,
    ]);

    expect($result)
        ->toContain('modified')
        ->not->toContain('original');
});

it('applies multiple visitors in order', function () {
    $parser = createPhpParser();

    $content = <<<'PHP'
<?php

echo 'start';
PHP;

    $result = $parser->testParseContent($content, [
        ReplaceEchoVisitor::class,
    ]);

    $result = $parser->testParseContent($result, [
        UppercaseEchoVisitor::class,
    ]);

    expect($result)
        ->toContain('MODIFIED');
});

it('accepts visitor instances instead of class names', function () {
    $parser = createPhpParser();

    $content = <<<'PHP'
<?php

echo 'original';
PHP;

    $result = $parser->testParseContent($content, [
        new ReplaceEchoVisitor,
    ]);

    expect($result)
        ->toContain('modified');
});

it('creates a valid parser instance', function () {
    $parser = createPhpParser();

    expect($parser->testNewParser())
        ->toBeInstanceOf(\PhpParser\Parser::class);
});

it('preserves formatting when no changes are made', function () {
    $parser = createPhpParser();

    $content = <<<'PHP'
<?php

$foo = 'bar';

$baz = 'qux';
PHP;

    $result = $parser->testParseContent($content, []);

    expect($result)
        ->toContain('$foo')
        ->toContain('$baz');
});

it('handles complex php structures', function () {
    $parser = createPhpParser();

    $content = <<<'PHP'
<?php

namespace App;

class Test
{
    public function handle(): void
    {
        echo 'test';
    }
}
PHP;

    $result = $parser->testParseContent($content, []);

    expect($result)
        ->toContain('namespace App')
        ->toContain('class Test')
        ->toContain('public function handle');
});

function createPhpParser(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors): string
        {
            return $this->parseContent($content, $visitors);
        }

        public function testNewParser(): \PhpParser\Parser
        {
            return $this->newParser();
        }
    };
}

class ReplaceEchoVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Echo_) {
            $node->exprs[0] = new \PhpParser\Node\Scalar\String_('modified');
        }

        return $node;
    }
}

class UppercaseEchoVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Echo_ && $node->exprs[0] instanceof \PhpParser\Node\Scalar\String_) {
            $node->exprs[0]->value = strtoupper($node->exprs[0]->value);
        }

        return $node;
    }
}
