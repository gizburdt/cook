<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class AddPasswordRules extends ProviderMethodVisitor
{
    protected array $useStatements = [
        'Illuminate\Validation\Rules\Password',
    ];

    protected function getMethodName(): string
    {
        return 'passwordRules';
    }

    protected function createMethod(): ClassMethod
    {
        $code = <<<'PHP'
<?php
class Temp {
    protected function passwordRules(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });
    }
}
PHP;

        $parser = (new \PhpParser\ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse($code);

        /** @var Class_ $class */
        $class = $ast[0];

        return $class->stmts[0];
    }
}
