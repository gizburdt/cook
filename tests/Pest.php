<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Symfony\Component\Process\Process;

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

function commandSource(string $name): string
{
    return file_get_contents(dirname(__DIR__)."/src/Commands/{$name}.php");
}

/**
 * @return array{exit: int, decision: string, output: string, errors: string}
 */
function runHook(string $name, array $input, array $env = []): array
{
    $process = new Process(
        ['php', dirname(__DIR__)."/publish/ai/.claude/hooks/{$name}.php"],
        env: $env,
        input: json_encode($input),
    );

    $process->run();

    $output = json_decode($process->getOutput(), true);

    return [
        'exit' => $process->getExitCode(),
        'decision' => $output['hookSpecificOutput']['permissionDecision'] ?? 'allow',
        'output' => $process->getOutput(),
        'errors' => $process->getErrorOutput(),
    ];
}
