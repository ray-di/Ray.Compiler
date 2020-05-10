<?php

declare(strict_types=1);

namespace Ray\Compiler;

final class DependencySaver
{
    /**
     * @var string
     */
    private $scriptDir;

    public function __construct(string $scriptDir)
    {
        $this->scriptDir = $scriptDir;
    }

    public function __invoke($dependencyIndex, Code $code)
    {
        $pearStyleName = \str_replace('\\', '_', $dependencyIndex);
        $instanceScript = \sprintf(ScriptInjector::INSTANCE, $this->scriptDir, $pearStyleName);
        $tmp = $instanceScript . getmypid();
        \file_put_contents($tmp, (string) $code . PHP_EOL);
        rename($tmp, $instanceScript);
        if ($code->qualifiers) {
            $this->saveQualifier($code->qualifiers);
        }
    }

    private function saveQualifier(IpQualifier $qualifer)
    {
        $qualifier = $this->scriptDir . '/qualifer';
        ! \file_exists($qualifier) && \mkdir($qualifier);
        $class = $qualifer->param->getDeclaringClass();
        if (! $class instanceof \ReflectionClass) {
            throw new \LogicException; // @codeCoverageIgnore
        }
        $fileName = \sprintf(
            ScriptInjector::QUALIFIER,
            $this->scriptDir,
            \str_replace('\\', '_', $class->name),
            $qualifer->param->getDeclaringFunction()->name,
            $qualifer->param->name
        );
        \file_put_contents($fileName, \serialize($qualifer->qualifier) . PHP_EOL, LOCK_EX);
    }
}
