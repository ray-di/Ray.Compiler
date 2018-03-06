<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Ray\Compiler;

final class DependencySaver
{
    const INSTANCE_FILE = '%s/%s.php';
    const META_FILE = '%s/metas/%s.json';
    const QUALIFIER_FILE = '%s/qualifer/%s-%s-%s';
    private $scriptDir;

    /**
     * @param string $scriptDir
     */
    public function __construct($scriptDir)
    {
        $this->scriptDir = $scriptDir;
        $metasDir = $this->scriptDir . '/metas';
        ! \file_exists($metasDir) && \mkdir($metasDir);
        $qualifier = $this->scriptDir . '/qualifer';
        ! \file_exists($qualifier) && \mkdir($qualifier);
    }

    /**
     * @param string $dependencyIndex
     * @param Code   $code
     */
    public function __invoke($dependencyIndex, Code $code)
    {
        $pearStyleName = \str_replace('\\', '_', $dependencyIndex);
        $instanceScript = \sprintf(self::INSTANCE_FILE, $this->scriptDir, $pearStyleName);
        \file_put_contents($instanceScript, (string) $code, LOCK_EX);
        $meta = \json_encode(['is_singleton' => $code->isSingleton]);
        $metaJson = \sprintf(self::META_FILE, $this->scriptDir, $pearStyleName);
        \file_put_contents($metaJson, $meta, LOCK_EX);
        if ($code->qualifiers) {
            $this->saveQualifier($code->qualifiers);
        }
    }

    private function saveQualifier(IpQualifier $qualifer)
    {
        $fileName = \sprintf(
            self::QUALIFIER_FILE,
            $this->scriptDir,
            \str_replace('\\', '_', $qualifer->param->getDeclaringClass()->name),
            $qualifer->param->getDeclaringFunction()->name,
            $qualifer->param->name
        );
        \file_put_contents($fileName, \serialize($qualifer->qualifier));
    }
}
