<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
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
        $metasDir = $this->scriptDir . '/metas';
        ! \file_exists($metasDir) && \mkdir($metasDir);
        $qualifier = $this->scriptDir . '/qualifer';
        ! \file_exists($qualifier) && \mkdir($qualifier);
    }

    public function __invoke($dependencyIndex, Code $code)
    {
        $pearStyleName = \str_replace('\\', '_', $dependencyIndex);
        $instanceScript = \sprintf(ScriptInjector::INSTANCE_FILE, $this->scriptDir, $pearStyleName);
        \file_put_contents($instanceScript, (string) $code . PHP_EOL, LOCK_EX);
        $meta = \json_encode(['is_singleton' => $code->isSingleton]) . PHP_EOL;
        $metaJson = \sprintf(ScriptInjector::META_FILE, $this->scriptDir, $pearStyleName);
        \file_put_contents($metaJson, $meta, LOCK_EX);
        if ($code->qualifiers) {
            $this->saveQualifier($code->qualifiers);
        }
    }

    private function saveQualifier(IpQualifier $qualifer)
    {
        $fileName = \sprintf(
            ScriptInjector::QUALIFIER_FILE,
            $this->scriptDir,
            \str_replace('\\', '_', $qualifer->param->getDeclaringClass()->name),
            $qualifer->param->getDeclaringFunction()->name,
            $qualifer->param->name
        );
        \file_put_contents($fileName, \serialize($qualifer->qualifier) . PHP_EOL);
    }
}
