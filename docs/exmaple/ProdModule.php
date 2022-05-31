<?php

declare(strict_types=1);

use Ray\Di\AbstractModule;

final class ProdModule extends AbstractModule
{
    protected function configure()
    {
        // / Binding of production, e.g., cache or database
    }
}
