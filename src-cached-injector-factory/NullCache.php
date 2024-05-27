<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Cache\CacheProvider;

final class NullCache extends CacheProvider
{
    /**
     * {@inheritDoc}
     */
    protected function doFetch($id)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doContains($id)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doDelete($id)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doFlush()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doGetStats()
    {
        return [];
    }
}
