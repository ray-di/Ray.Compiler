<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;

/**
 * @deprecated This class is deprecated. Use InjectorFactory directly instead.
 *             The cache functionality has been removed as doctrine/cache is abandoned.
 *
 * @psalm-immutable
 */
final class ContextInjector
{
    public static function getInstance(AbstractInjectorContext $injectorContext): InjectorInterface
    {
        /** @psalm-suppress DeprecatedMethod */
        return CachedInjectorFactory::getInstance(
            $injectorContext::class,
            $injectorContext->tmpDir,
            $injectorContext,
            $injectorContext->getCache(),
            $injectorContext->getSavedSingleton(),
        );
    }

    public static function getOverrideInstance(
        AbstractInjectorContext $injectorContext,
        AbstractModule $overrideModule
    ): InjectorInterface {
        return CachedInjectorFactory::getOverrideInstance(
            $injectorContext->tmpDir,
            $injectorContext,
            $overrideModule,
            $injectorContext->getSavedSingleton()
        );
    }
}
