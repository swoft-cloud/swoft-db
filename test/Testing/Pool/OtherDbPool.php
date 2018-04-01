<?php

namespace Swoft\Db\Test\Testing\Pool;

use Swoft\Bean\Annotation\Inject;
use Swoft\Bean\Annotation\Pool;
use Swoft\Db\Pool\DbPool;

/**
 * OtherDbPool
 *
 * @Pool("other.master")
 */
class OtherDbPool extends DbPool
{
    /**
     * @Inject()
     * @var OtherDbConfig
     */
    protected $poolConfig;
}