<?php

declare(strict_types=1);

namespace Ray\Compiler;

use Attribute;
use Doctrine\Common\Annotations\Annotation\Enum;
use Ray\Di\Di\InjectInterface;
use Ray\Di\Di\Qualifier;

/**
 * @Annotation
 * @Target("METHOD")
 * @Qualifier
 */
#[Attribute(Attribute::TARGET_METHOD)]
#[Qualifier]
final class FakeLoggerInject implements InjectInterface
{
    /** @Enum({"MEMORY", "FILE", "DB"}) */
    public $type;

    /**
     * @param string|array<string, string>|null $type
     */
    public function __construct($type = null)
    {
        if (is_array($type) && isset($type['type'])) {
            // Doctrine Annotations format: @FakeLoggerInject(type="MEMORY")
            $this->type = $type['type'];
        } elseif (is_string($type)) {
            // Attribute format: #[FakeLoggerInject(type: 'MEMORY')]
            $this->type = $type;
        } elseif (is_array($type) && isset($type['value'])) {
            // Doctrine Annotations format: @FakeLoggerInject("MEMORY")
            $this->type = $type['value'];
        } else {
            $this->type = $type ?? 'MEMORY';
        }
    }

    public function isOptional()
    {
        return true;
    }
}
