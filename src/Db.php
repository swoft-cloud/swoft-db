<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Core\RequestContext;
use Swoft\Core\ResultInterface;
use Swoft\Db\Exception\DbException;
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
    /**
     * Return one
     */
    const RETURN_ONE = 1;

    /**
     * Return rows
     */
    const RETURN_ROWS = 2;

    /**
     * Return fetch
     */
    const RETURN_FETCH = 3;

    /**
     * Return insertid
     */
    const RETURN_INSERTID = 4;

    /**
     * Query by sql
     *
     * @param string $sql
     * @param array  $params
     * @param string $instance
     * @param string $className
     *
     * @return ResultInterface
     */
    public static function query(string $sql, array $params = [], string $instance = Pool::INSTANCE, string $className = ''): ResultInterface
    {
        $type     = self::getOperation($sql);
        $instance = explode('.', $instance);
        list($instance, $node, $db) = array_pad($instance, 3, '');

        list($instance, $node) = self::getInstanceAndNodeByType($instance, $node, $type);
        /* @var AbstractDbConnection $connection */
        $connection = self::getConnection($instance, $node);

        if(!empty($db)){
            $connection->selectDb($db);
        }

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
        $dbResult->setClassName($className);

        return $dbResult;
    }

    /**
     * @param string $instance
     */
    public static function beginTransaction(string $instance = Pool::INSTANCE)
    {
        /* @var AbstractDbConnection $connection */
        $connection = self::getConnection($instance, Pool::MASTER, 'ts');
        $connection->setAutoRelease(false);
        $connection->beginTransaction();

        self::beginTransactionContext($connection, $instance);
    }

    /**
     * @param string $instance
     *
     * @throws DbException
     */
    public static function rollback(string $instance = Pool::INSTANCE)
    {
        /* @var AbstractDbConnection $connection */
        $connection = self::getTransactionConnection($instance);
        if ($connection === null) {
            throw new DbException('No transaction needs to be rollbacked');
        }

        $connection->rollback();
        self::closetTransactionContext($connection, $instance);
    }

    /**
     * @param string $instance
     *
     * @throws DbException
     */
    public static function commit(string $instance = Pool::INSTANCE)
    {
        /* @var AbstractDbConnection $connection */
        $connection = self::getTransactionConnection($instance);
        if ($connection === null) {
            throw new DbException('No transaction needs to be committed');
        }

        $connection->commit();
        self::closetTransactionContext($connection, $instance);
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

        if ($type === Db::RETURN_ROWS || $type == Db::RETURN_INSERTID) {
            return [$instance, Pool::MASTER];
        }

        return [$instance, Pool::SLAVE];
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
            return self::RETURN_INSERTID;
        }

        if (strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0) {
            return self::RETURN_ROWS;
        }

        if (strpos($sql, 'SELECT') === 0 && strpos($sql, 'LIMIT 0,1')) {
            return self::RETURN_ONE;
        }

        return self::RETURN_FETCH;
    }
    /**
     * @param string $instance
     * @param string $node
     *
     * @return ConnectionInterface
     */
    private static function getConnection(string $instance, string $node, $ts = 'query'): ConnectionInterface
    {
        $transactionConnection = self::getTransactionConnection($instance);
        if ($transactionConnection !== null) {
            return $transactionConnection;
        }

        $pool = DbHelper::getPool($instance, $node);

        return $pool->getConnection();
    }

    /**
     * @param string $instance
     *
     * @return mixed
     */
    private static function getTransactionConnection(string $instance)
    {
        $contextTsKey  = DbHelper::getContextTsKey();
        $contextCntKey = PoolHelper::getContextCntKey();
        $instanceKey   = DbHelper::getTsInstanceKey($instance);
        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $instanceKey, new \SplStack());
        if ($tsStack->isEmpty()) {
            return null;
        }
        $cntId      = $tsStack->offsetGet(0);
        $connection = RequestContext::getContextDataByChildKey($contextCntKey, $cntId, null);
        return $connection;
    }

    /**
     * @param ConnectionInterface $connection
     * @param string              $instance
     */
    private static function beginTransactionContext(ConnectionInterface $connection, string $instance = Pool::INSTANCE)
    {
        $cntId        = $connection->getConnectionId();
        $contextTsKey = DbHelper::getContextTsKey();
        $instanceKey  = DbHelper::getTsInstanceKey($instance);

        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $instanceKey, new \SplStack());
        $tsStack->push($cntId);
        RequestContext::setContextDataByChildKey($contextTsKey, $instanceKey, $tsStack);
    }

    /**
     * @param AbstractDbConnection $connection
     * @param string               $instance
     */
    private static function closetTransactionContext(AbstractDbConnection $connection, string $instance = Pool::INSTANCE)
    {
        $contextTsKey = DbHelper::getContextTsKey();
        $instanceKey  = DbHelper::getTsInstanceKey($instance);

        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $instanceKey, new \SplStack());
        if (!$tsStack->isEmpty()) {
            $tsStack->pop();
        }
        RequestContext::setContextDataByChildKey($contextTsKey, $instanceKey, $tsStack);
        $connection->release(true);
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