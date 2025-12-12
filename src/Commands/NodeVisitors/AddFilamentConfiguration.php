<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddFilamentConfiguration extends NodeVisitorAbstract
{
    protected array $useStatements = [
        'Filament\Tables\Table',
        'Filament\Forms\Components\TextInput',
        'Filament\Infolists\Components\TextEntry',
        'Filament\Tables\Columns\TextColumn',
    ];

    protected array $existingUseStatements = [];

    protected bool $hasLivewireMethod = false;

    protected bool $hasLivewireCall = false;

    public function enterNode(Node $node)
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->existingUseStatements[] = $use->name->toString();
            }
        }

        if ($node instanceof ClassMethod && $node->name->name === 'livewire') {
            $this->hasLivewireMethod = true;
        }

        if ($node instanceof ClassMethod && $node->name->name === 'boot') {
            foreach ($node->stmts ?? [] as $stmt) {
                if ($stmt instanceof Expression && $stmt->expr instanceof MethodCall) {
                    $call = $stmt->expr;

                    if ($call->var instanceof Variable && $call->var->name === 'this') {
                        if ($call->name instanceof Identifier && $call->name->name === 'livewire') {
                            $this->hasLivewireCall = true;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name->name === 'boot' && ! $this->hasLivewireCall) {
            $livewireCall = new Expression(
                new MethodCall(
                    new Variable('this'),
                    'livewire'
                )
            );

            $node->stmts = array_merge([$livewireCall], $node->stmts ?? []);

            return $node;
        }

        if ($node instanceof Class_ && ! $this->hasLivewireMethod) {
            $node->stmts[] = new Nop;

            $node->stmts[] = $this->createLivewireMethod();

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        $missingUseStatements = array_diff($this->useStatements, $this->existingUseStatements);

        if (empty($missingUseStatements)) {
            return null;
        }

        $newUseStatements = [];

        foreach ($missingUseStatements as $useStatement) {
            $newUseStatements[] = new Use_([
                new UseItem(new Name($useStatement)),
            ]);
        }

        // Handle namespaced files where use statements are inside the Namespace node
        if (isset($nodes[0]) && $nodes[0] instanceof Namespace_) {
            $lastUseIndex = $this->findLastUseIndex($nodes[0]->stmts);

            if ($lastUseIndex === null) {
                return null;
            }

            array_splice($nodes[0]->stmts, $lastUseIndex + 1, 0, $newUseStatements);

            return $nodes;
        }

        // Handle non-namespaced files
        $lastUseIndex = $this->findLastUseIndex($nodes);

        if ($lastUseIndex === null) {
            return null;
        }

        array_splice($nodes, $lastUseIndex + 1, 0, $newUseStatements);

        return $nodes;
    }

    protected function findLastUseIndex(array $nodes): ?int
    {
        $lastUseIndex = null;

        foreach ($nodes as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        return $lastUseIndex;
    }

    protected function createLivewireMethod(): ClassMethod
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
