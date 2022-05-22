<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Ray\Di\InjectorInterface;

use function get_class;

/**
 * @psalm-immutable
 */
final class ContextInjector
{
    private function __construct()
    {
    }

    public static function getInstance(AbstractInjectorContext $injectorContext): InjectorInterface
    {
        return CachedInjectorFactory::getInstance(
            get_class($injectorContext),
            $injectorContext->tmpDir,
            static function () use ($injectorContext) {
                return $injectorContext->getModule();
            },
            $injectorContext->getCache()
        );
    }
}
