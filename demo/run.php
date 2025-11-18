<?php

declare(strict_types=1);

use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Demo\GreeterInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$scriptDir = __DIR__ . '/.compiled';

// Use the compiled injector (no Ray.Di runtime overhead)
$injector = new CompiledInjector($scriptDir);

/** @var GreeterInterface $greeter */
$greeter = $injector->getInstance(GreeterInterface::class);

echo $greeter->greet('World') . "\n";
echo $greeter->greet('Ray.Compiler') . "\n";

echo "\n";
echo "✓ Using pre-compiled dependency injection!\n";
echo "✓ No reflection, no dependency resolution at runtime\n";
echo "✓ Check {$scriptDir}/ for generated PHP scripts\n";
