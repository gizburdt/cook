<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses()->group('cook')->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createPhpParserHelper(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors, ?string $file = null): string
        {
            return $this->parsePhpContent($content, $visitors, $file);
        }
    };
}
