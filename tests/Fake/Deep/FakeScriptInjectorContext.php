<?php

declare(strict_types=1);

namespace Ray\Compiler\Deep;


use Doctrine\Common\Cache\CacheProvider;
use Ray\Compiler\AbstractInjectorContext;
use Ray\Di\AbstractModule;

final class FakeScriptInjectorContext extends AbstractInjectorContext
{
    public function __invoke(): AbstractModule
    {
        return new FakeDepModule();
    }

    public function getCache(): CacheProvider
    {
       return new class() extends CacheProvider {
           protected function doFetch($id)
           {
           }

           protected function doContains($id)
           {
           }

           protected function doSave($id, $data, $lifeTime = 0)
           {
           }

           protected function doDelete($id)
           {
           }

           protected function doFlush()
           {
           }

           protected function doGetStats()
           {
           }
       };
    }
}
