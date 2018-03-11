<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Core\Coroutine;
use Swoft\Core\RequestContext;
use Swoft\Core\ResultInterface;
use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Exception\DbException;
use Swoft\Db\Helper\DbHelper;
use Swoft\Pool\ConnectionInterface;
use Swoft\Pool\PoolInterface;

/**
 * Class EntityManager
 *
 * @package Swoft\Db
 */
class EntityManager implements EntityManagerInterface
{
    /**
     * Context connect
     */
    const CONTEXT_CONNECTS = 'contextConnects';

    /**
     * Db connection
     *
     * @var \Swoft\Pool\AbstractConnection
     */
    private $connect;

    /**
     * Connection pool
     *
     * @var PoolInterface
     */
    private $pool = null;

    /**
     * Is this EntityManager closed ?
     *
     * @var bool
     */
    private $isClose = false;

    /**
     * @var string
     */
    private $poolId;

    /**
     * EntityManager constructor.
     *
     * @param PoolInterface $pool
     * @param string        $poolId
     */
    private function __construct(PoolInterface $pool, string $poolId)
    {
        $this->pool    = $pool;
        $this->poolId  = $poolId;
        $this->connect = $pool->getConnection();
    }

    /**
     * Create a EntityManager
     *
     * @param string $poolId
     *
     * @return EntityManager
     */
    public static function create(string $poolId = Pool::MASTER): EntityManager
    {
        $pool = self::getPool($poolId);

        return new EntityManager($pool, $poolId);
    }

    /**
     * Create a Query Builder
     *
     * @param string $sql
     *
     * @return QueryBuilder
     * @throws \Swoft\Db\Exception\DbException
     */
    public function createQuery(string $sql = ''): QueryBuilder
    {
        $this->checkStatus();
        $className = self::getQueryClassName($this->connect);

        return new $className($this->pool, $this->connect, $this->poolId, $sql);
    }

    /**
     * Create a QueryBuild for ActiveRecord
     *
     * @param string $className Entity class name
     * @param string $poolId    Pool id, master node will be used as defaults
     *
     * @return QueryBuilder
     */
    public static function getQuery(string $className, $poolId): QueryBuilder
    {

        $connect = self::getConnect($poolId);

        $entities       = EntityCollector::getCollector();
        $tableName      = $entities[$className]['table']['name'];
        $queryClassName = self::getQueryClassName($connect);

        // Get connection pool
        $pool = self::getPool($poolId);

        /* @var QueryBuilder $query */
        $query = new $queryClassName($pool, $connect, '');
        $query->from($tableName);

        return $query;
    }

    /**
     * Get a connection
     *
     * @param string $poolId
     *
     * @return \Swoft\Pool\ConnectionInterface
     */
    private static function getConnect(string $poolId): ConnectionInterface
    {
        $cid                   = Coroutine::id();
        $contextTransactionKey = DbHelper::getContextTransactionKey((int)$cid, $poolId);
        $connectKey            = DbHelper::getContextConnectKey((int)$cid, $poolId);

        $contextTransaction   = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());
        $contextConnects      = RequestContext::getContextDataByKey(self::CONTEXT_CONNECTS, []);
        $contextConnect       = $contextConnects[$connectKey] ?? new \SplStack();
        $isContextTransaction = $contextTransaction instanceof \SplStack && !$contextTransaction->isEmpty();
        $isContextConnect     = $contextConnect instanceof \SplStack && !$contextConnect->isEmpty();
        if ($isContextTransaction && $isContextConnect) {
            return $contextConnect->offsetGet(0);
        }

        // Get a connection from pool
        $pool    = self::getPool($poolId);
        $connect = $pool->getConnection();

        return $connect;
    }

    /**
     * Save Entity
     *
     * @param object $entity
     *
     * @return ResultInterface
     * @throws \Swoft\Db\Exception\DbException
     */
    public function save($entity)
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->save($entity);
    }

    /**
     * Delete Entity
     *
     * @param object $entity
     *
     * @return ResultInterface
     */
    public function delete($entity)
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->delete($entity);
    }

    /**
     * @param $entity
     *
     * @return ResultInterface
     */
    public function update($entity): ResultInterface
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->update($entity);
    }

    /**
     * Delete Entity by ID
     *
     * @param string $className Entity class nane
     * @param mixed  $id
     *
     * @return ResultInterface
     */
    public function deleteById($className, $id)
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->deleteById($className, $id);
    }

    /**
     * Delete Entities by Ids
     *
     * @param string $className Entity class name
     * @param array  $ids       ID collection
     *
     * @return ResultInterface
     */
    public function deleteByIds($className, array $ids)
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->deleteByIds($className, $ids);
    }

    /**
     * Find by Entity
     *
     * @param object $entity
     *
     * @return ResultInterface
     */
    public function find($entity): ResultInterface
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->find($entity);
    }

    /**
     * Find Entity by ID
     *
     * @param string $className Entity class name
     * @param mixed  $id
     *
     * @return ResultInterface
     */
    public function findById($className, $id): ResultInterface
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->findById($className, $id);
    }

    /**
     * Find Entites by IDs
     *
     * @param string $className transaction
     * @param array  $ids
     *
     * @return ResultInterface
     */
    public function findByIds($className, array $ids): ResultInterface
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->findByIds($className, $ids);
    }

    /**
     * Begin transaction
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function beginTransaction()
    {
        $this->checkStatus();
        $this->connect->beginTransaction();
        $this->beginTransactionContext();
    }

    /**
     * Rollback transaction
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function rollback()
    {
        $this->checkStatus();
        $this->connect->rollback();
        $this->closetTransactionContext();
    }

    /**
     * Commit transaction
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function commit()
    {
        $this->checkStatus();
        $this->connect->commit();
        $this->closetTransactionContext();
    }

    /**
     * Close current EntityManager, and release the connection
     */
    public function close()
    {
        $this->isClose = true;
        $this->pool->release($this->connect);
    }

    /**
     * Check the EntityManager status
     *
     * @throws DbException
     */
    private function checkStatus()
    {
        if ($this->isClose) {
            throw new DbException('EntityManager was closed, no operation anymore');
        }
    }

    /**
     * Get connetion pool by pool ID
     *
     * @param string $poolId
     *
     * @return PoolInterface
     */
    private static function getPool(string $poolId): PoolInterface
    {
        if ($poolId === Pool::SLAVE && self::hasSalvePool() === false) {
            $poolId = Pool::MASTER;
        }

        $pool = App::getPool($poolId);

        return $pool;
    }

    /**
     * @return bool
     */
    private static function hasSalvePool()
    {
        $properties = App::getProperties();
        $hasConfig  = isset($properties['db']['slave']['uri']) && !empty($properties['db']['slave']['uri']);
        $hasEnv     = !empty(env('DB_SLAVE_URI'));

        if ($hasConfig || $hasEnv) {
            return true;
        }

        return false;
    }

    /**
     * Get an Executor
     *
     * @return Executor
     * @throws \Swoft\Db\Exception\DbException
     */
    private function getExecutor(): Executor
    {
        $query = $this->createQuery();

        return new Executor($query, $this->poolId);
    }

    /**
     * Begin transaction context
     */
    private function beginTransactionContext()
    {
        $cid                   = Coroutine::id();
        $contextTransactionKey = DbHelper::getContextTransactionKey((int)$cid, $this->poolId);
        $connectKey            = DbHelper::getContextConnectKey((int)$cid, $this->poolId);

        $contextTransaction = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());
        $contextConnects    = RequestContext::getContextDataByKey(self::CONTEXT_CONNECTS, []);
        $contextConnect     = $contextConnects[$connectKey] ?? new \SplStack();

        if ($contextTransaction instanceof \SplStack) {
            $contextTransaction->push(true);
        }
        if ($contextConnect instanceof \SplStack) {
            $contextConnect->push($this->connect);
            $contextConnects[$connectKey] = $contextConnect;
        }
        RequestContext::setContextDataByKey($contextTransactionKey, $contextTransaction);
        RequestContext::setContextDataByKey(self::CONTEXT_CONNECTS, $contextConnects);
    }

    /**
     * Close transaction context
     */
    private function closetTransactionContext()
    {
        $cid                   = Coroutine::id();
        $contextTransactionKey = DbHelper::getContextTransactionKey((int)$cid, $this->poolId);
        $connectKey            = DbHelper::getContextConnectKey((int)$cid, $this->poolId);

        $contextTransaction = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());
        $contextConnects    = RequestContext::getContextDataByKey(self::CONTEXT_CONNECTS, []);
        $contextConnect     = $contextConnects[$connectKey] ?? new \SplStack();

        if ($contextTransaction instanceof \SplStack) {
            $contextTransaction->pop();
        }

        if ($contextConnect instanceof \SplStack) {
            $contextConnect->pop();
            $contextConnects[$connectKey] = $contextConnect;
        };

        RequestContext::setContextDataByKey($contextTransactionKey, $contextTransaction);
        RequestContext::setContextDataByKey(self::CONTEXT_CONNECTS, $contextConnects);
    }

    /**
     * Get the class name of QueryBuilder
     *
     * @param ConnectionInterface $connect
     *
     * @return string
     */
    private static function getQueryClassName(ConnectionInterface $connect): string
    {
        $connectClassName = \get_class($connect);
        $classNameTmp     = str_replace('\\', '/', $connectClassName);
        $namespaceDir     = \dirname($classNameTmp);
        $namespace        = str_replace('/', '\\', $namespaceDir);
        $namespace        = sprintf('%s\\QueryBuilder', $namespace);

        return $namespace;
    }
}
