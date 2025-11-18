<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/src-function',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/src-deprecated',
        __DIR__ . '/tests/Fake',
    ])
    ->withPhpSets(
        php82: true
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: false
    )
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        ReadOnlyPropertyRector::class,
    ]);