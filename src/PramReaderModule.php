<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Doctrine\Common\Annotations\Reader;
use Koriym\ParamReader\ParamReader;
use Koriym\ParamReader\ParamReaderInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class PramReaderModule extends AbstractModule
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->bind(ParamReaderInterface::class)->to(ParamReader::class);
        $this->bind(Reader::class)->toProvider(ReaderProvider::class)->in(Scope::SINGLETON);
    }
}
