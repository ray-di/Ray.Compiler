<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Compiler;
use Ray\Compiler\FakeNullObjectModule;
use Ray\Compiler\FakeTyreInterface;

$scriptDir = dirname(__DIR__) . '/tmp/null_object';
(new Compiler())->compile(new FakeNullObjectModule(), $scriptDir);
$injector = new CompiledInjector($scriptDir);

$instance = $injector->getInstance(FakeTyreInterface::class);
