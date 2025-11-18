<?php

declare(strict_types=1);

use Ray\Compiler\Compiler;
use Ray\Compiler\Demo\AppModule;

require dirname(__DIR__) . '/vendor/autoload.php';

$scriptDir = __DIR__ . '/.compiled';
$module = new AppModule();

// Compile the dependency injection container
$compiler = new Compiler();
$compiler->compile($module, $scriptDir);

echo "✓ Compilation complete! Scripts saved to: {$scriptDir}\n";
echo "✓ Run 'php demo/run.php' to execute the compiled application\n";
