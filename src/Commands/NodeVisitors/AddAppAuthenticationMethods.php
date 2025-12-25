<?php

namespace Gizburdt\Cook\Commands\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

class AddAppAuthenticationMethods extends NodeVisitorAbstract
{
    protected bool $hasInterface = false;

    protected bool $hasUseStatement = false;

    protected array $existingMethods = [];

    protected array $requiredMethods = [
        'getAppAuthenticationSecret',
        'saveAppAuthenticationSecret',
        'getAppAuthenticationHolderName',
        'getAppAuthenticationRecoveryCodes',
        'saveAppAuthenticationRecoveryCodes',
    ];

    protected array $hiddenItemsToAdd = [
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    protected array $castsToAdd = [
        'app_authentication_secret' => 'encrypted',
        'app_authentication_recovery_codes' => 'encrypted:array',
    ];

    public function beforeTraverse(array $nodes)
    {
        $this->hasUseStatement = $this->useStatementExists($nodes, 'DutchCodingCompany\FilamentAppAuthentication\Contracts\HasAppAuthentication');

        return null;
    }

    protected function useStatementExists(array $nodes, string $class): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                if ($this->useStatementExists($node->stmts, $class)) {
                    return true;
                }
            }

            if ($node instanceof Use_) {
                foreach ($node->uses as $use) {
                    if ($use->name->toString() === $class) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof ClassMethod) {
                    $this->existingMethods[] = $stmt->name->name;
                }
            }

            foreach ($node->implements as $implement) {
                if ($implement->toString() === 'HasAppAuthentication') {
                    $this->hasInterface = true;
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Property && $node->props[0]->name->name === 'hidden') {
            $this->addItemsToHiddenProperty($node);

            return $node;
        }

        if ($node instanceof ClassMethod && $node->name->name === 'casts') {
            $this->addItemsToCastsMethod($node);

            return $node;
        }

        if ($node instanceof Class_) {
            if (! $this->hasInterface) {
                $node->implements[] = new Name('HasAppAuthentication');
            }

            $this->addMissingMethods($node);

            return $node;
        }

        return null;
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->hasUseStatement) {
            return null;
        }

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $this->addUseStatementToNamespace($node, 'DutchCodingCompany\FilamentAppAuthentication\Contracts\HasAppAuthentication');

                return $nodes;
            }
        }

        return $nodes;
    }

    protected function addUseStatementToNamespace(Node\Stmt\Namespace_ $namespace, string $class): void
    {
        $lastUseIndex = null;

        foreach ($namespace->stmts as $index => $node) {
            if ($node instanceof Use_) {
                $lastUseIndex = $index;
            }
        }

        $useStatement = new Use_([
            new UseItem(new Name($class)),
        ]);

        if ($lastUseIndex !== null) {
            array_splice($namespace->stmts, $lastUseIndex + 1, 0, [$useStatement]);
        }
    }

    protected function addItemsToHiddenProperty(Property $property): void
    {
        $prop = $property->props[0];

        if (! $prop->default instanceof Array_) {
            return;
        }

        $existingItems = [];

        foreach ($prop->default->items as $item) {
            if ($item instanceof ArrayItem && $item->value instanceof String_) {
                $existingItems[] = $item->value->value;
            }
        }

        foreach ($this->hiddenItemsToAdd as $itemToAdd) {
            if (! in_array($itemToAdd, $existingItems)) {
                $prop->default->items[] = new ArrayItem(new String_($itemToAdd));
            }
        }
    }

    protected function addItemsToCastsMethod(ClassMethod $method): void
    {
        foreach ($method->stmts as $stmt) {
            if (! $stmt instanceof Return_) {
                continue;
            }

            if (! $stmt->expr instanceof Array_) {
                continue;
            }

            $existingKeys = [];

            foreach ($stmt->expr->items as $item) {
                if ($item instanceof ArrayItem && $item->key instanceof String_) {
                    $existingKeys[] = $item->key->value;
                }
            }

            foreach ($this->castsToAdd as $key => $value) {
                if (! in_array($key, $existingKeys)) {
                    $stmt->expr->items[] = new ArrayItem(
                        new String_($value),
                        new String_($key)
                    );
                }
            }
        }
    }

    protected function addMissingMethods(Class_ $class): void
    {
        $isFirst = true;

        foreach ($this->requiredMethods as $methodName) {
            if (in_array($methodName, $this->existingMethods)) {
                continue;
            }

            $method = $this->createMethod($methodName);
            $method->setAttribute('blankLineBefore', $isFirst);
            $class->stmts[] = $method;
            $isFirst = false;
        }
    }

    protected function createMethod(string $name): ClassMethod
    {
        return match ($name) {
            'getAppAuthenticationSecret' => $this->createGetAppAuthenticationSecret(),
            'saveAppAuthenticationSecret' => $this->createSaveAppAuthenticationSecret(),
            'getAppAuthenticationHolderName' => $this->createGetAppAuthenticationHolderName(),
            'getAppAuthenticationRecoveryCodes' => $this->createGetAppAuthenticationRecoveryCodes(),
            'saveAppAuthenticationRecoveryCodes' => $this->createSaveAppAuthenticationRecoveryCodes(),
        };
    }

    protected function createGetAppAuthenticationSecret(): ClassMethod
    {
        return new ClassMethod('getAppAuthenticationSecret', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'returnType' => new NullableType(new Identifier('string')),
            'stmts' => [
                new Return_(
                    new PropertyFetch(new Variable('this'), 'app_authentication_secret')
                ),
            ],
        ]);
    }

    protected function createSaveAppAuthenticationSecret(): ClassMethod
    {
        return new ClassMethod('saveAppAuthenticationSecret', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'params' => [
                new Param(
                    new Variable('secret'),
                    null,
                    new NullableType(new Identifier('string'))
                ),
            ],
            'returnType' => new Identifier('void'),
            'stmts' => [
                new Expression(
                    new Assign(
                        new PropertyFetch(new Variable('this'), 'app_authentication_secret'),
                        new Variable('secret')
                    )
                ),
                new Nop,
                new Expression(
                    new Node\Expr\MethodCall(
                        new Variable('this'),
                        new Identifier('save')
                    )
                ),
            ],
        ]);
    }

    protected function createGetAppAuthenticationHolderName(): ClassMethod
    {
        return new ClassMethod('getAppAuthenticationHolderName', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'returnType' => new Identifier('string'),
            'stmts' => [
                new Return_(
                    new PropertyFetch(new Variable('this'), 'email')
                ),
            ],
        ]);
    }

    protected function createGetAppAuthenticationRecoveryCodes(): ClassMethod
    {
        return new ClassMethod('getAppAuthenticationRecoveryCodes', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'returnType' => new NullableType(new Identifier('array')),
            'stmts' => [
                new Return_(
                    new PropertyFetch(new Variable('this'), 'app_authentication_recovery_codes')
                ),
            ],
        ]);
    }

    protected function createSaveAppAuthenticationRecoveryCodes(): ClassMethod
    {
        return new ClassMethod('saveAppAuthenticationRecoveryCodes', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'params' => [
                new Param(
                    new Variable('codes'),
                    null,
                    new NullableType(new Identifier('array'))
                ),
            ],
            'returnType' => new Identifier('void'),
            'stmts' => [
                new Expression(
                    new Assign(
                        new PropertyFetch(new Variable('this'), 'app_authentication_recovery_codes'),
                        new Variable('codes')
                    )
                ),
                new Nop,
                new Expression(
                    new Node\Expr\MethodCall(
                        new Variable('this'),
                        new Identifier('save')
                    )
                ),
            ],
        ]);
    }
}
