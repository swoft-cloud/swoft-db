<?php

namespace Swoft\Db\Bean\Annotation;

use Swoft\Db\Driver\Driver;
use Swoft\Db\Driver\DriverType;

/**
 * The connect annotation
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @uses      Connect
 * @version   2018年01月27日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class Connect
{
    /**
     * @var string
     */
    private $driver = Driver::MYSQL;

    /**
     * @var string
     */
    private $type = DriverType::COR;

    /**
     * Connect constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->driver = $values['value'];
        }
        if (isset($values['name'])) {
            $this->driver = $values['name'];
        }
        if (isset($values['coroutine'])) {
            $this->type = $values['coroutine'];
        }
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}