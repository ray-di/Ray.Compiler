<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 *
 * taken from BuilderAbstract::PhpParser() and modified for object
 */
namespace Ray\Compiler;

final class DependencySaver
{
    private $scriptDir;

    const INSTANCE_FILE = '%s/%s';
    const META_FILE = '%s/metas/%s.json';

    /**
     * @param string $scriptDir
     */
    public function __construct($scriptDir)
    {
        $this->scriptDir = $scriptDir;
        $metasDir = $this->scriptDir . '/metas';
        ! file_exists($metasDir) && mkdir($metasDir);
    }

    /**
     * @param string $dependencyIndex
     * @param Code   $code
     */
    public function __invoke($dependencyIndex, Code $code)
    {
        $pearStyleName = str_replace('\\', '_', $dependencyIndex);
        $instanceScript = sprintf(self::INSTANCE_FILE, $this->scriptDir, $pearStyleName);
        file_put_contents($instanceScript, (string) $code, LOCK_EX);
        $meta = json_encode(['is_singleton' => $code->isSingleton]);
        $metaJson = sprintf(self::META_FILE, $this->scriptDir, $pearStyleName);
        file_put_contents($metaJson, $meta, LOCK_EX);
    }
}
