<?php
/**
 * This file is part of the Ray.Compiler package.
 *
 * @license http://opensource.org/licenses/bsd-license.php MIT
 *
 * taken from BuilderAbstract::PhpParser() and modified for object
 */
namespace Ray\Compiler;

class DependencySaver
{
    private $scriptDir;

    /**
     * @param string $scriptDir
     */
    public function __construct($scriptDir)
    {
        $this->scriptDir = $scriptDir;
    }

    /**
     * @param string $dependencyIndex
     * @param Code   $code
     */
    public function __invoke($dependencyIndex, Code $code)
    {
        $file = sprintf('%s/%s.php', $this->scriptDir, str_replace('\\', '_', $dependencyIndex));
        file_put_contents($file, (string) $code, LOCK_EX);
        $meta = json_encode(['is_singleton' => $code->isSingleton]);
        file_put_contents($file . '.meta.php', $meta, LOCK_EX);
    }
}
