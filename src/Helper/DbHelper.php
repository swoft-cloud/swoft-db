<?php

namespace Swoft\Db\Helper;

use Swoft\App;
use Swoft\Db\Pool;
use Swoft\Pool\PoolInterface;

/**
 * DbHelper
 */
class DbHelper
{
    /**
     * @return string
     */
    public static function getContextSqlKey(): string
    {
        return 'swoft-sql';
    }

    /**
     * @param string $group
     * @param string $node
     *
     * @return \Swoft\Pool\PoolInterface
     */
    public static function getPool(string $group, string $node): PoolInterface
    {
        $poolName = self::getPoolName($group, $node);
        if ($node == Pool::SLAVE && !App::hasPool($poolName)) {
            $poolName = self::getPoolName($group, Pool::MASTER);
        }

        return App::getPool($poolName);
    }

    /**
     * @param string $group
     * @param string $node
     *
     * @return string
     */
    private static function getPoolName(string $group, string $node): string
    {
        return sprintf('%s.%s', $group, $node);
    }
}