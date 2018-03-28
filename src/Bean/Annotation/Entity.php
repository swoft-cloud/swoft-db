<?php

namespace Swoft\Db\Bean\Annotation;

use Swoft\Db\Pool;

/**
 * 实体注解
 *
 * @Annotation
 * @Target("CLASS")
 */
class Entity
{
    /**
     * @var string
     */
    private $instance = Pool::INSTANCE;

    /**
     * Inject constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->instance = $values['value'];
        }
        if (isset($values['instance'])) {
            $this->instance = $values['instance'];
        }
    }

    /**
     * @return string
     */
    public function getInstance(): string
    {
        return $this->instance;
    }
}
