<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Core\RequestContext;
use Swoft\Core\ResultInterface;
use Swoft\Db\Helper\DbHelper;
use Swoft\Helper\PoolHelper;
use Swoft\Pool\ConnectionInterface;
use Swoft\Db\Pool\Config\DbPoolProperties;
use Swoft\Db\Pool\DbPool;

/**
 * Db
 */
class Db
{
    const RESULT_ONE = 1;
    const RESULT_ROWS = 2;
    const RESULT_FETCH = 3;
    const RESULT_INSERTID = 4;

    public static function query(string $sql, array $params = [], string $instance = Pool::INSTANCE): ResultInterface
    {
        $type     = self::getOperation($sql);
        $instance = explode('.', $instance);
        list($instance, $node, $db) = array_pad($instance, 3, '');

        list($instance, $node) = self::getInstanceAndNodeByType($instance, $node, $type);
        /* @var AbstractDbConnection $connection */
        $connection = self::getConnection($instance, $node);
        /* @var DbPool $pool */
        $pool = $connection->getPool();
        /* @var DbPoolProperties $poolConfig */
        $poolConfig = $pool->getPoolConfig();
        $profileKey = $poolConfig->getDriver();

        if (App::isCoContext()) {
            $connection->setDefer();
        }
        $connection->prepare($sql);
        $result = $connection->execute($params);

        $dbResult = self::getResult($result, $connection, $profileKey);
        $dbResult->setType($type);

        return $dbResult;
    }


    /**
     * @param string $instance
     * @param string $node
     * @param int    $type
     *
     * @return array
     */
    private static function getInstanceAndNodeByType(string $instance, string $node, int $type): array
    {
        if (!empty($node)) {
            return [$instance, $node];
        }

        if ($type === Db::RESULT_ROWS || $type == Db::RESULT_INSERTID) {
            return [$instance, Pool::MASTER];
        }

        return [$instance, Pool::SLAVE];
    }

    public static function beginTransaction()
    {

    }

    public static function rollback()
    {

    }

    public static function commit()
    {

    }

    /**
     * @return ConnectionInterface
     */
    private static function getConnection(string $instance, string $node): ConnectionInterface
    {
        $contextTsKey  = PoolHelper::getContextTsKey();
        $contextCntKey = PoolHelper::getContextCntKey();
        $instanceKey   = PoolHelper::getTsInstanceKey($instance);

        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $instanceKey, new \SplStack());
        if (!$tsStack->isEmpty()) {
            $cntId      = $tsStack->offsetGet(0);
            $connection = RequestContext::getContextDataByChildKey($contextCntKey, $cntId, null);

            return $connection;
        }

        $pool = DbHelper::getPool($instance, $node);

        return $pool->getConnection();
    }

    /**
     * @param string $sql
     *
     * @return string
     */
    private static function getOperation(string $sql): string
    {
        $sql = trim($sql);
        $sql = strtoupper($sql);

        if (strpos($sql, 'INSERT') === 0) {
            return self::RESULT_INSERTID;
        }

        if (strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0) {
            return self::RESULT_ROWS;
        }

        if (strpos($sql, 'SELECT') === 0 && strpos($sql, 'LIMIT 0,1')) {
            return self::RESULT_ONE;
        }

        return self::RESULT_FETCH;
    }

    /**
     * @param mixed               $result
     * @param ConnectionInterface $connection
     * @param string              $profileKey
     *
     * @return \Swoft\Db\DbResult
     */
    private static function getResult($result, ConnectionInterface $connection = null, string $profileKey = '')
    {
        if (App::isCoContext()) {
            return new DbCoResult($result, $connection, $profileKey);
        }

        return new DbDataResult($result, $connection, $profileKey);
    }

}