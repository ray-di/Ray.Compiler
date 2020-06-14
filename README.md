# Ray.Compiler

[![Build Status](https://travis-ci.org/ray-di/Ray.Compiler.svg?branch=1.x)](https://travis-ci.org/ray-di/Ray.Compiler)

### Script injector

`DiCompiler` generates raw factory code for better performance and to clarify how the instance is created.

```php
<?php

use Ray\Compiler\DiCompiler;
use Ray\Compiler\ScriptInjector;
use Ray\Di\AbstractModule;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;
use Ray\Di\Scope;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

interface ListerInterface{}
class Lister implements ListerInterface{}

class Consumer
{
    public $lister;
    public $msg;

    /**
     * @Inject
     * @Named("msg")
     */
    public function setMessage($msg)
    {
        $this->msg = $msg;
    }

    public function __construct(ListerInterface $lister)
    {
        $this->lister = $lister;
    }
}

class Module extends AbstractModule
{
    protected function configure()
    {
        $this->bind(ListerInterface::class)->to(Lister::class);
        $this->bind()->annotatedWith('msg')->toInstance('hello world');
        $this->bind(Consumer::class)->in(Scope::SINGLETON);
    }
}
$tmpDir = __DIR__ . '/tmp';
$compiler = new DiCompiler(new Module, $tmpDir);
$compiler->compile();
var_dump(file_get_contents($tmpDir . '/__Consumer-.php'));
```

*generated code for `Consumer` class*
```php
<?php

namespace Ray\Di\Compiler;

$instance = new \Consumer($prototype('ListerInterface-*'));
$instance->setMessage('hello world');
return $instance;
```

*generated code for `ListerInterface` inteface*
```php
<?php

namespace Ray\Di\Compiler;

$instance = new \Lister();
return $instance;
```

`ScriptInjector` use these generated PHP scripts to inject dependency. It is almost as fast as if there was no injector.

```php
use Ray\Compiler\ScriptInjector;

$injector = new ScriptInjector($tmpDir);
$instance = $injector->getInstance(Consumer::class);
var_dump($instance);

//class Consumer#41 (2) {
//  public $lister =>
//  class Lister#17 (0) {
//  }
//  public $msg =>
//  string(11) "hello world"
//}

```
## Compile on demand

Lazy modules bindings allows compilation on demand.

```php
$injector = new ScriptInjector(
    $tmpDir,
    function () {
        return new CarModule;
    }
);
$car = $injector->getInstance(FakeCar::class);

```

## Object graph visualization

Object graph can be visualize with `dumpGraph()`.
Graph HTML files will be output at `graph` folder under `$tmpDir`.

```php
$compiler = new DiCompiler(new Module, $tmpDir);
$compiler->compile();
$compiler->dumpGraph();
```

```
open tmp/graph/Ray_Compiler_FakeCarInterface-.html
```

## Performance

The `CachedInejctorFactory` gives you the best performance in both development (x2) and production (x10) by switching two injector. 
See [CachedInjectorFactory](https://github.com/ray-di/Ray.Compiler/issues/75).

## Ray.Di
This **Ray.Compiler** package is a sub component package for [Ray.Di](https://github.com/ray-di/Ray.Di)
