<?php

declare(strict_types=1);

namespace Ray\Compiler;

use PHPUnit\Framework\TestCase;
use Ray\Aop\MethodInvocation;
use Ray\Compiler\Exception\SingletonRequiresInjectionPoint;
use Ray\Compiler\Exception\SingletonsFileNotFound;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

use function assert;
use function file_get_contents;
use function is_array;
use function is_dir;
use function json_decode;
use function mkdir;
use function spl_object_hash;

use const JSON_THROW_ON_ERROR;

class WarmupTest extends TestCase
{
    /** @var non-empty-string */
    private string $scriptDir;

    protected function setUp(): void
    {
        $this->scriptDir = __DIR__ . '/tmp/warmup';
        deleteFiles($this->scriptDir);
        FakeWarmupCounter::$count = 0;
    }

    /** @return list<string> */
    private function getSingletonIndexes(): array
    {
        $indexes = json_decode((string) file_get_contents($this->scriptDir . '/singletons.json'), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($indexes));
        /** @var list<string> $indexes */

        return $indexes;
    }

    public function testCompileWritesSingletonsJson(): void
    {
        (new Compiler())->compile(new FakeToBindSingletonModule(), $this->scriptDir);
        $this->assertFileExists($this->scriptDir . '/singletons.json');
        $indexes = $this->getSingletonIndexes();
        $this->assertContains(FakeRobotInterface::class . '-', $indexes);
        $this->assertContains(FakeDependSingleton::class . '-', $indexes);
    }

    public function testWarmupInstantiatesSingletonsEagerly(): void
    {
        $module = new class () extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeWarmupCounter::class)->in(Scope::SINGLETON);
            }
        };
        (new Compiler())->compile($module, $this->scriptDir);
        $this->assertNotContains(MethodInvocation::class . '-', $this->getSingletonIndexes());
        $injector = new CompiledInjector($this->scriptDir);
        $injector->warmup();
        $this->assertSame(1, FakeWarmupCounter::$count);
        // the warmed instance is the cached one
        $instance = $injector->getInstance(FakeWarmupCounter::class);
        $this->assertSame(spl_object_hash($instance), spl_object_hash($injector->getInstance(FakeWarmupCounter::class)));
        $this->assertSame(1, FakeWarmupCounter::$count);
    }

    public function testInjectionPointSingletonFailsCompilationBeforeConsumerCanCacheIt(): void
    {
        $module = new class () extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeLoggerInterface::class)->annotatedWith(FakeLoggerInject::class)->toProvider(FakeLoggerPointProvider::class)->in(Scope::SINGLETON);
                $this->bind(FakeLoggerConsumer::class)->in(Scope::SINGLETON);
            }
        };
        $this->expectException(SingletonRequiresInjectionPoint::class);
        $this->expectExceptionMessage(FakeLoggerInterface::class . '-' . FakeLoggerInject::class);
        (new Compiler())->compile($module, $this->scriptDir);
    }

    public function testIpLiteralInDefaultStringDoesNotExcludeSingleton(): void
    {
        $module = new class () extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeIpLiteralDefaultSingleton::class)->in(Scope::SINGLETON);
            }
        };
        (new Compiler())->compile($module, $this->scriptDir);
        // the default string merely looks like the $ip call; the singleton must still be warmable
        $this->assertContains(FakeIpLiteralDefaultSingleton::class . '-', $this->getSingletonIndexes());
    }

    public function testWarmupWithoutSingletonsJson(): void
    {
        $emptyDir = __DIR__ . '/tmp/warmup-empty';
        deleteFiles($emptyDir);
        if (! is_dir($emptyDir)) {
            mkdir($emptyDir, 0777, true);
        }

        $injector = new CompiledInjector($emptyDir);
        $this->expectException(SingletonsFileNotFound::class);
        $injector->warmup();
    }
}
