<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Core\Coroutine;
use Swoft\Core\RequestContext;
use Swoft\Core\ResultInterface;
use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Exception\DbException;
use Swoft\Db\Helper\DbHelper;
use Swoft\Pool\ConnectInterface;
use Swoft\Pool\ConnectPool;

/**
 * The entity manager of db
 */
class EntityManager implements EntityManagerInterface
{
    /**
     * Context connect
     */
    const CONTEXT_CONNECTS = 'contextConnects';

    /**
     * 数据库连接
     *
     * @var \Swoft\Pool\AbstractConnect
     */
    private $connect;

    /**
     * 连接池
     *
     * @var ConnectPool
     */
    private $pool = null;

    /**
     * 当前EM是否关闭
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
     * @param ConnectPool $pool
     * @param string      $poolId
     */
    private function __construct(ConnectPool $pool, string $poolId)
    {
        // 初始化连接信息
        $this->pool    = $pool;
        $this->poolId  = $poolId;
        $this->connect = $pool->getConnect();
    }

    /**
     * 实例化一个实体管理器
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
     * 创建一个查询器
     *
     * @param string $sql sql语句，默认为空
     *
     * @return QueryBuilder
     * @throws \Swoft\Db\Exception\DbException
     */
    public function createQuery(string $sql = ''): QueryBuilder
    {
        $this->checkStatus();
        $className = self::getQueryClassName($this->connect);

        return new $className($this->pool, $this->connect, $sql);
    }

    /**
     * 创建一个查询器用于ActiveRecord操作
     *
     * @param string $className 实体类名称
     * @param string $poolId    是否主节点，默认从节点
     *
     * @return QueryBuilder
     */
    public static function getQuery(string $className, $poolId): QueryBuilder
    {

        $connect = self::getConnect($poolId);

        // 驱动查询器
        $entities       = EntityCollector::getCollector();
        $tableName      = $entities[$className]['table']['name'];
        $queryClassName = self::getQueryClassName($connect);

        // 获取连接
        $pool = self::getPool($poolId);

        /* @var QueryBuilder $query */
        $query = new $queryClassName($pool, $connect, '');
        $query->from($tableName);

        return $query;
    }

    /**
     * @param string $poolId
     *
     * @return \Swoft\Pool\ConnectInterface
     */
    private static function getConnect(string $poolId): ConnectInterface
    {
        $cid           = Coroutine::id();
        $contextTransactionKey = DbHelper::getContextTransactionKey((int)$cid, $poolId);
        $connectKey    = DbHelper::getContextConnectKey((int)$cid, $poolId);

        $contextTransaction   = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());
        $contextConnects     = RequestContext::getContextDataByKey(self::CONTEXT_CONNECTS, []);
        $contextConnect      = $contextConnects[$connectKey]?? new \SplStack();
        $isContextTransaction = $contextTransaction instanceof \SplStack && !$contextTransaction->isEmpty();
        $isContextConnect    = $contextConnect instanceof \SplStack && !$contextConnect->isEmpty();
        if ($isContextTransaction && $isContextConnect) {
            return $contextConnect->offsetGet(0);
        }

        // 获取连接
        $pool    = self::getPool($poolId);
        $connect = $pool->getConnect();
        return $connect;
    }

    /**
     * insert实体数据
     *
     * @param object $entity 实体
     *
     * @return ResultInterface
     */
    public function save($entity)
    {
        $this->checkStatus();
        $executor = $this->getExecutor();

        return $executor->save($entity);
    }

    /**
     * 按实体信息删除数据
     *
     * @param object $entity 实体
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
     * 根据ID删除数据
     *
     * @param string $className 实体类名
     * @param mixed  $id        删除ID
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
     * 根据ID删除数据
     *
     * @param string $className 实体类名
     * @param array  $ids       ID集合
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
     * 按实体信息查找
     *
     * @param object $entity 实体实例
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
     * 根据ID查找
     *
     * @param string $className 实体类名
     * @param mixed  $id        ID
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
     * 根据ids查找
     *
     * @param string $className 类名
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
     * 开始事务
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function beginTransaction()
    {
        $this->checkStatus();
        $this->connect->beginTransaction();
        $this->beginContextTransaction();
    }

    /**
     * 回滚事务
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function rollback()
    {
        $this->checkStatus();
        $this->connect->rollback();
        $this->closetContextTransaction();
    }

    /**
     * 提交事务
     *
     * @throws \Swoft\Db\Exception\DbException
     */
    public function commit()
    {
        $this->checkStatus();
        $this->connect->commit();
        $this->closetContextTransaction();
    }

    /**
     * 关闭当前实体管理器
     */
    public function close()
    {
        $this->isClose = true;
        $this->pool->release($this->connect);
    }

    /**
     * 检查当前实体管理器状态是否正取
     *
     * @throws DbException
     */
    private function checkStatus()
    {
        if ($this->isClose) {
            throw new DbException('entity manager已经关闭，不能再操作');
        }
    }

    /**
     * 获取连接池
     *
     * @param string $poolId
     *
     * @return ConnectPool
     */
    private static function getPool(string $poolId): ConnectPool
    {
        if($poolId == Pool::SLAVE && self::hasSalvePool() == false){
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
     * 获取执行器
     *
     * @return Executor
     * @throws \Swoft\Db\Exception\DbException
     */
    private function getExecutor(): Executor
    {
        // 初始化实体执行器
        $query = $this->createQuery();

        return new Executor($query, $this->poolId);
    }

    /**
     * Begin context transaction
     */
    private function beginContextTransaction()
    {

        $cid           = Coroutine::id();
        $contextTransactionKey = DbHelper::getContextTransactionKey((int)$cid, $this->poolId);
        $connectKey    = DbHelper::getContextConnectKey((int)$cid, $this->poolId);

        $contextTransaction = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());
        $contextConnects   = RequestContext::getContextDataByKey(self::CONTEXT_CONNECTS, []);
        $contextConnect    = $contextConnects[$connectKey]?? new \SplStack();

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
     * Close context transaction
     */
    private function closetContextTransaction()
    {

        $cid           = Coroutine::id();
        $contextTransactionKey = DbHelper::getContextTransactionKey((int)$cid, $this->poolId);
        $connectKey    = DbHelper::getContextConnectKey((int)$cid, $this->poolId);

        $contextTransaction = RequestContext::getContextDataByKey($contextTransactionKey, new \SplStack());
        $contextConnects   = RequestContext::getContextDataByKey(self::CONTEXT_CONNECTS, []);
        $contextConnect    = $contextConnects[$connectKey]?? new \SplStack();

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
     * Get the class of queryBuilder
     *
     * @param ConnectInterface $connect
     *
     * @return string
     */
    private static function getQueryClassName(ConnectInterface $connect): string
    {
        $connectClassName = get_class($connect);
        $classNameTmp     = str_replace('\\', '/', $connectClassName);
        $namespaceDir     = dirname($classNameTmp);
        $namespace        = str_replace('/', '\\', $namespaceDir);
        $namespace        = sprintf('%s\\QueryBuilder', $namespace);

        return $namespace;
    }
}
