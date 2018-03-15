<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Core\Coroutine;
use Swoft\Core\RequestContext;
use Swoft\Core\ResultInterface;
use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Exception\DbException;
use Swoft\Helper\PoolHelper;
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
     * @var \Swoft\Db\AbstractDbConnection
     */
    private $connection;

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
     * @var bool
     */
    private $isTransaction = false;

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
        $this->pool       = $pool;
        $this->poolId     = $poolId;
        $this->connection = $pool->getConnection();

        $this->connection->setAutoRelease(false);
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
        $className = self::getQueryClassName($this->connection);

        return new $className($this->pool, $this->connection, $this->poolId, $sql);
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
        $contextTsKey  = PoolHelper::getContextTsKey();
        $contextCntKey = PoolHelper::getContextCntKey();
        $cidPoolId     = PoolHelper::getCidPoolId($poolId);

        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $cidPoolId, new \SplStack());
        if (!$tsStack->isEmpty()) {
            $cntId      = $tsStack->offsetGet(0);
            $connection = RequestContext::getContextDataByChildKey($contextCntKey, $cntId, null);

            return $connection;
        }

        // Get a connection from pool
        $pool       = self::getPool($poolId);
        $connection = $pool->getConnection();

        return $connection;
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
        $this->connection->beginTransaction();
        $this->beginTransactionContext();
        $this->isTransaction = true;
    }

    /**
     * Rollback transaction
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function rollback()
    {
        $this->checkStatus();
        $this->connection->rollback();
        $this->isTransaction = false;
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
        $this->connection->commit();
        $this->isTransaction = false;
        $this->closetTransactionContext();
    }

    /**
     * Close current EntityManager, and release the connection
     */
    public function close()
    {
        if($this->isTransaction){
            $this->rollback();
        }
        if(!$this->connection->isRecv()){
            $this->connection->receive();
        }
        $this->isClose = true;
        $this->pool->release($this->connection);
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
        $cntId        = $this->connection->getConnectionId();
        $contextTsKey = PoolHelper::getContextTsKey();
        $cidPoolId    = PoolHelper::getCidPoolId($this->poolId);

        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $cidPoolId, new \SplStack());
        $tsStack->push($cntId);

        RequestContext::setContextDataByChildKey($contextTsKey, $cidPoolId, $tsStack);
    }

    /**
     * Close transaction context
     */
    private function closetTransactionContext()
    {
        $cid          = Coroutine::id();
        $contextTsKey = PoolHelper::getContextTsKey();
        $cidPoolId    = PoolHelper::getCidPoolId($this->poolId);

        /* @var \SplStack $tsStack */
        $tsStack = RequestContext::getContextDataByChildKey($contextTsKey, $cidPoolId, new \SplStack());
        $tsStack->pop();

        RequestContext::setContextDataByChildKey($contextTsKey, $cidPoolId, $tsStack);
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
