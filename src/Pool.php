<?php

namespace Swoft\Db;

use Swoft\Db\Pool\DbPool;
use Swoft\Db\Pool\DbSlavePool;

/**
 * The type of pool
 */
class Pool
{
    /**
     * The master
     */
    const MASTER = DbPool::class;

    /**
     * The slave
     */
    const SLAVE = DbSlavePool::class;
}