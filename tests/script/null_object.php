<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Ray\Compiler\CompileInjector;
use Ray\Compiler\FakeNullObjectModule;
use Ray\Compiler\FakeTyreInterface;
use Ray\Compiler\LazyModule;

$injector = new CompileInjector(
    dirname(__DIR__) . '/tmp/null_object',
    LazyModule::getInstance(
        static function () {
            return new FakeNullObjectModule();
        }
    )
);
$instance = $injector->getInstance(FakeTyreInterface::class);
