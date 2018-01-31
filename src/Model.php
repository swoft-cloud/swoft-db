<?php

namespace Swoft\Db;
use Swoft\Core\ResultInterface;

/**
 * The model of activerecord
 */
class Model
{
    /**
     * 记录旧数据，用于更新数据对比
     *
     * @var array
     */
    private $attrs = [];


    /**
     * 插入数据
     *
     * @return ResultInterface
     */
    public function save()
    {
        $executor = self::getExecutor();
        return $executor->save($this);
    }

    /**
     * 删除数据
     *
     * @return ResultInterface
     */
    public function delete()
    {
        $executor = self::getExecutor(true);
        return $executor->delete($this);
    }

    /**
     * 根据ID删除数据
     *
     * @param mixed $id    ID
     *
     * @return ResultInterface
     */
    public static function deleteById($id)
    {
        $executor = self::getExecutor(true);
        return $executor->deleteById(static::class, $id);
    }

    /**
     * 删除IDS集合数据
     *
     * @param array $ids   ID集合
     *
     * @return ResultInterface
     */
    public static function deleteByIds(array $ids)
    {
        $executor = self::getExecutor(true);
        return $executor->deleteByIds(static::class, $ids);
    }

    /**
     * 更新数据
     *
     * @return ResultInterface
     */
    public function update()
    {
        $executor = self::getExecutor(true);
        return $executor->update($this);
    }

    /**
     * 实体查询
     *
     * @param bool $isMaster 是否主节点查询
     *
     * @return ResultInterface
     */
    public function find($isMaster = false)
    {
        $executor = self::getExecutor($isMaster);
        return $executor->find($this);
    }

    /**
     * ID查找
     *
     * @param mixed $id       id值
     * @param bool  $isMaster 是否是主节点，默认从节点
     *
     * @return ResultInterface
     */
    public static function findById($id, $isMaster = false)
    {
        $executor = self::getExecutor($isMaster);
        return $executor->findById(static::class, $id);
    }

    /**
     * ID集合查询
     *
     * @param array $ids      ID集合
     * @param bool  $isMaster 是否主节点查询
     *
     * @return ResultInterface
     */
    public static function findByIds(array $ids, $isMaster = false)
    {
        $executor = self::getExecutor($isMaster);
        return $executor->findByIds(static::class, $ids);
    }

    /**
     * 返回查询器，自定义查询
     *
     * @param bool $isMaster 是否主节点
     *
     * @return QueryBuilder
     */
    public static function query($isMaster = false)
    {
        return EntityManager::getQuery(static::class, $isMaster, true);
    }


    /**
     * 返回数据执行器
     *
     * @param bool $isMaster 是否主节点
     *
     * @return Executor
     */
    private static function getExecutor($isMaster = false)
    {
        $queryBuilder = EntityManager::getQuery(static::class, $isMaster, true);
        $executor = new Executor($queryBuilder, static::class);
        return $executor;
    }

    /**
     * @return array
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * @param array $attrs
     */
    public function setAttrs(array $attrs)
    {
        $this->attrs = $attrs;
    }
}
