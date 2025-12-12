<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class AddFilamentConfiguration extends ProviderMethodVisitor
{
    protected array $useStatements = [
        'Filament\Tables\Table',
        'Filament\Forms\Components\TextInput',
        'Filament\Infolists\Components\TextEntry',
        'Filament\Tables\Columns\TextColumn',
    ];

    protected function getMethodName(): string
    {
        return 'livewire';
    }

    protected function createMethod(): ClassMethod
    {
        $code = <<<'PHP'
<?php
class Temp {
    protected function livewire(): void
    {
        Table::configureUsing(function (Table $table) {
            $table
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(50);
        });

        TextInput::configureUsing(function (TextInput $input) {
            //
        });

        TextEntry::configureUsing(function (TextEntry $entry) {
            $entry->placeholder('-');
        });

        TextColumn::configureUsing(function (TextColumn $column) {
            $column->placeholder('-');
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
