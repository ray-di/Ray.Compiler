<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Compiler;
use Ray\Compiler\FakeNullObjectModule;
use Ray\Compiler\FakeTyreInterface;

$sciptDir = dirname(__DIR__) . '/tmp/null_object';
(new Compiler())->compile($sciptDir, new FakeNullObjectModule());
$injector = new CompiledInjector($sciptDir);

$instance = $injector->getInstance(FakeTyreInterface::class);
