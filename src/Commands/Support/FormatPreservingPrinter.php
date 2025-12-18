<?php

namespace Gizburdt\Cook\Commands\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\PrettyPrinter\Standard;

class FormatPreservingPrinter extends Standard
{
    protected array $methodsNeedingBlankLine = [];

    protected bool $hasPasswordRulesChain = false;

    protected bool $hasHealthChecks = false;

    protected bool $hasTableConfigureChain = false;

    public function printFormatPreserving(array $stmts, array $origStmts, array $origTokens): string
    {
        $this->methodsNeedingBlankLine = $this->findMethodsNeedingBlankLine($stmts);
        $this->hasPasswordRulesChain = $this->hasPasswordRulesMethodChain($stmts);
        $this->hasHealthChecks = $this->hasHealthChecksMethod($stmts);
        $this->hasTableConfigureChain = $this->hasTableConfigureChain($stmts);

        $result = parent::printFormatPreserving($stmts, $origStmts, $origTokens);

        foreach ($this->methodsNeedingBlankLine as $methodName) {
            $result = preg_replace(
                '/(\n)([ ]*)(protected function '.preg_quote($methodName).'\()/',
                "$1\n$2$3",
                $result,
                1
            );
        }

        if ($this->hasPasswordRulesChain) {
            $result = $this->formatPasswordRulesChain($result);
        }

        if ($this->hasHealthChecks) {
            $result = $this->formatHealthChecks($result);
        }

        if ($this->hasTableConfigureChain) {
            $result = $this->formatTableConfigureChain($result);
        }

        return $result;
    }

    protected function formatPasswordRulesChain(string $code): string
    {
        // Format Password::min(8)->mixedCase()->numbers()->symbols() with each method on new line
        return preg_replace_callback(
            '/return Password::min\(8\)(->mixedCase\(\))(->numbers\(\))(->symbols\(\))/',
            function ($matches) {
                $indent = '                ';

                return 'return Password::min(8)'."\n".
                    $indent.$matches[1]."\n".
                    $indent.$matches[2]."\n".
                    $indent.$matches[3];
            },
            $code
        );
    }

    protected function hasPasswordRulesMethodChain(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                if ($this->hasPasswordRulesMethodChain($node->stmts)) {
                    return true;
                }
            }

            if ($node instanceof Node\Stmt\Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod && $stmt->name->name === 'passwordRules') {
                        if ($stmt->getAttribute('formatChain')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function hasHealthChecksMethod(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                if ($this->hasHealthChecksMethod($node->stmts)) {
                    return true;
                }
            }

            if ($node instanceof Node\Stmt\Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod && $stmt->name->name === 'healthChecks') {
                        if ($stmt->getAttribute('formatHealthChecks')) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function hasTableConfigureChain(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                if ($this->hasTableConfigureChain($node->stmts)) {
                    return true;
                }
            }

            if ($node instanceof Node\Stmt\Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod && $stmt->name->name === 'filament') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function formatTableConfigureChain(string $code): string
    {
        return preg_replace(
            '/(->paginationPageOptions\(\[10, 25, 50, 100\]\))(->defaultPaginationPageOption\(50\))/',
            "$1\n                $2",
            $code
        );
    }

    protected function formatHealthChecks(string $code): string
    {
        // Format Health::checks([...]) with each check on new line and proper indentation
        return preg_replace_callback(
            '/Health::checks\(\[([^\]]+)\]\)/',
            function ($matches) {
                $checks = $matches[1];

                // All check classes that need to be on their own line
                $checkClasses = [
                    'CacheCheck',
                    'CpuLoadCheck',
                    'DatabaseConnectionCountCheck',
                    'DatabaseCheck',
                    'DatabaseSizeCheck',
                    'DebugModeCheck',
                    'EnvironmentCheck',
                    'HorizonCheck',
                    'OptimizedAppCheck',
                    'QueueCheck',
                    'RedisCheck',
                    'RedisMemoryUsageCheck',
                    'ScheduleCheck',
                    'SecurityAdvisoriesCheck',
                    'UsedDiskSpaceCheck',
                ];

                // Put first check on new line
                $checks = preg_replace(
                    '/('.$checkClasses[0].'::new\(\))/',
                    "\n            $1",
                    $checks,
                    1
                );

                // Put remaining checks on new lines
                foreach ($checkClasses as $checkClass) {
                    $checks = preg_replace(
                        '/, ('.$checkClass.'::new\(\))/',
                        ",\n            $1",
                        $checks
                    );
                }

                // Format CpuLoadCheck method chain on separate lines
                $checks = preg_replace(
                    '/(CpuLoadCheck::new\(\))(->failWhenLoadIsHigherInTheLast5Minutes\([^)]+\))(->failWhenLoadIsHigherInTheLast15Minutes\([^)]+\))/',
                    "$1\n                $2\n                $3",
                    $checks
                );

                // Format DatabaseConnectionCountCheck method chain on separate lines
                $checks = preg_replace(
                    '/(DatabaseConnectionCountCheck::new\(\))(->warnWhenMoreConnectionsThan\([^)]+\))(->failWhenMoreConnectionsThan\([^)]+\))/',
                    "$1\n                $2\n                $3",
                    $checks
                );

                // Format RedisMemoryUsageCheck method chain on separate lines
                $checks = preg_replace(
                    '/(RedisMemoryUsageCheck::new\(\))(->warnWhenAboveMb\([^)]+\))(->failWhenAboveMb\([^)]+\))/',
                    "$1\n                $2\n                $3",
                    $checks
                );

                // Format ScheduleCheck method chain on separate lines
                $checks = preg_replace(
                    '/(ScheduleCheck::new\(\))(->heartbeatMaxAgeInMinutes\([^)]+\))/',
                    "$1\n                $2",
                    $checks
                );

                // Add trailing comma after last item
                $checks = rtrim($checks, ', ');
                $checks .= ',';

                return 'Health::checks(['.$checks."\n        ])";
            },
            $code
        );
    }

    protected function findMethodsNeedingBlankLine(array $nodes): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $methods = array_merge($methods, $this->findMethodsNeedingBlankLine($node->stmts));
            }

            if ($node instanceof Node\Stmt\Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod && $stmt->getAttribute('blankLineBefore')) {
                        $methods[] = $stmt->name->name;
                    }
                }
            }
        }

        return $methods;
    }

    protected function pExpr_Array(Array_ $node): string
    {
        if ($node->getAttribute('multiline')) {
            return $this->pArrayMultiline($node);
        }

        return parent::pExpr_Array($node);
    }

    protected function pArrayMultiline(Array_ $node): string
    {
        $items = [];
        $newlineBefore = [];
        $isPadded = $node->getAttribute('paddedMultiline', false);

        $this->indent();

        foreach ($node->items as $index => $item) {
            if ($item === null) {
                $items[] = '';
                $newlineBefore[$index] = false;

                continue;
            }

            $items[] = $this->pArrayItem($item);
            $newlineBefore[$index] = $item->getAttribute('newlineBefore', false);
        }

        $result = '[';

        if ($isPadded) {
            $result .= $this->nl;
        }

        $result .= $this->pCommaSeparatedMultiline($items, true, $newlineBefore);

        $this->outdent();

        if ($isPadded) {
            return $result.$this->nl.$this->nl.']';
        }

        return $result.$this->nl.']';
    }

    protected function pArrayItem(ArrayItem $node): string
    {
        $result = '';

        if ($node->key !== null) {
            $result .= $this->p($node->key).' => ';
        }

        if ($node->byRef) {
            $result .= '&';
        }

        if ($node->unpack) {
            $result .= '...';
        }

        $result .= $this->p($node->value);

        return $result;
    }

    protected function pCommaSeparatedMultiline(array $items, bool $trailingComma, array $newlineBefore = []): string
    {
        $result = '';

        foreach ($items as $index => $item) {
            if ($item === '') {
                continue;
            }

            if (! empty($newlineBefore[$index])) {
                $result .= $this->nl;
            }

            $result .= $this->nl.$item;

            if ($index < count($items) - 1 || $trailingComma) {
                $result .= ',';
            }
        }

        return $result;
    }
}
