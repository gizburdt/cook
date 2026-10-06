<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenFinalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenPrivateMethods;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use NunoMaduro\PhpInsights\Domain\Metrics\Architecture\Classes;
use NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ScopeClosingBraceSniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Classes\ClassInstantiationSniff;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\Operator\NewWithBracesFixer;
use SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff;
use SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

/**
 * https://github.com/nunomaduro/phpinsights/blob/master/stubs/config.php
 */
return [

    'preset' => 'laravel',

    'ide' => 'phpstorm',

    'exclude' => [],

    'add' => [
        Classes::class => [
            ForbiddenFinalClasses::class,
        ],
    ],

    'remove' => [
        AlphabeticallySortedUsesSniff::class,
        AssignmentInConditionSniff::class,
        ClassInstantiationSniff::class,
        CyclomaticComplexityIsHigh::class,
        DeclareStrictTypesSniff::class,
        DisallowMixedTypeHintSniff::class,
        DocCommentSpacingSniff::class,
        ForbiddenDefineFunctions::class,
        ForbiddenNormalClasses::class,
        ForbiddenPublicPropertySniff::class,
        ForbiddenSetterSniff::class,
        ForbiddenTraits::class,
        FunctionLengthSniff::class,
        NewWithBracesFixer::class,
        NoEmptyCommentFixer::class,
        OrderedClassElementsFixer::class,
        ParameterTypeHintSniff::class,
        PropertyTypeHintSniff::class,
        ScopeClosingBraceSniff::class,
        SuperfluousExceptionNamingSniff::class,
        UnusedParameterSniff::class,
        ReturnTypeHintSniff::class,
        UselessFunctionDocCommentSniff::class,
        UselessParenthesesSniff::class,
    ],

    'config' => [
        ForbiddenPrivateMethods::class => [
            'title' => 'The usage of private methods is not idiomatic in Laravel.',
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'routes/',
                'rector.php',
            ],
            'lineLimit' => 100,
            'absoluteLineLimit' => 120,
            'ignoreComments' => true,
        ],
        MethodArgumentSpaceFixer::class => [
            'keep_multiple_spaces_after_comma' => false,
            'on_multiline' => 'ignore',
        ],
    ],

    'requirements' => [
        'min-quality' => 90,
        'min-complexity' => 90,
        'min-architecture' => 90,
        'min-style' => 90,
        'disable-security-check' => false,
    ],

    'threads' => null,

    'timeout' => 60,

];
