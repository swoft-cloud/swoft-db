<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Exception\DbException;
use Swoft\Db\Pool\DbPool;
use Swoft\Pool\ConnectInterface;
use Swoft\Pool\ConnectPool;
use Swoft\Core\ResultInterface;

/**
 * The entity manager of db
 */
class EntityManager implements EntityManagerInterface
{
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
     * EntityManager constructor.
     *
     * @param ConnectPool $pool
     */
    private function __construct(ConnectPool $pool)
    {
        // 初始化连接信息
        $this->pool    = $pool;
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

        return new EntityManager($pool);
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
        // 获取连接
        $pool    = self::getPool($poolId);
        $connect = $pool->getConnect();

        // 驱动查询器
        $entities       = EntityCollector::getCollector();
        $tableName      = $entities[$className]['table']['name'];
        $queryClassName = self::getQueryClassName($connect);

        /* @var QueryBuilder $query */
        $query = new $queryClassName($pool, $connect, '');
        $query->from($tableName);

        return $query;
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
        /* @var DbPool $dbPool */
        $pool = App::getBean($poolId);

        return $pool;
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

        return new Executor($query);
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
