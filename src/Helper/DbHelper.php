<?php

namespace Swoft\Db\Helper;

use Swoft\Core\Coroutine;
use Swoft\Core\RequestContext;

/**
 * Database helper
 */
class DbHelper
{
    /**
     * @param int    $cid
     * @param string $poolId
     * @return string
     */
    public static function getContextTransactionKey(int $cid, string $poolId): string
    {
        return sprintf('%d-%s-transaction-swoft', $cid, $poolId);
    }

    /**
     * @param int    $cid
     * @param string $poolId
     * @return string
     */
    public static function getContextConnectKey(int $cid, string $poolId): string
    {
        return sprintf('%d-%s-connect-swoft', $cid, $poolId);
    }

    /**
     * @param string $poolId
     * @return bool
     */
    public static function isContextTransaction(string $poolId): bool
    {
        $cid = Coroutine::id();
        $contextTransactionKey = self::getContextTransactionKey((int)$cid, $poolId);
        $contextTransaction = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());

        if ($contextTransaction instanceof \SplStack && $contextTransaction->isEmpty()) {
            return false;
        }

        return true;
    }

}