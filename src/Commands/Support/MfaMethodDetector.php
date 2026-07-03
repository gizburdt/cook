<?php

namespace Gizburdt\Cook\Commands\Support;

use Gizburdt\Cook\Enums\MfaMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

class MfaMethodDetector
{
    /**
     * @return array<int, MfaMethod>
     */
    public static function fromContent(string $content): array
    {
        $ast = (new ParserFactory)->createForNewestSupportedVersion()->parse($content) ?? [];

        $class = (new NodeFinder)->findFirstInstanceOf($ast, Class_::class);

        if ($class === null) {
            return [];
        }

        $interfaces = array_map(fn ($name): string => $name->toString(), $class->implements);

        return MfaMethod::detect($interfaces);
    }
}
