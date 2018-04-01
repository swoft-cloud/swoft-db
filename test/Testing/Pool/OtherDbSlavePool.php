<?php

namespace Swoft\Db\Test\Testing\Pool;

use Swoft\Bean\Annotation\Inject;
use Swoft\Bean\Annotation\Pool;
use Swoft\Db\Pool\DbPool;

/**
 * OtherDbSlavePool
 *
 * @Pool("other.slave")
 */
class OtherDbSlavePool extends DbPool
{
    /**
     * @Inject()
     *
     * @var OtherDbSlaveConfig
     */
    protected $poolConfig;
}