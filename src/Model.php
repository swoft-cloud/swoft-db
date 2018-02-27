<?php

namespace Swoft\Db;

use Swoft\Core\ResultInterface;

/**
 * The model of activerecord
 */
class Model
{
    /**
     * The data of old
     *
     * @var array
     */
    private $attrs = [];

    /**
     * Insert data to db
     *
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public function save(string $poolId = Pool::MASTER)
    {
        $executor = self::getExecutor($poolId);

        return $executor->save($this);
    }

    /**
     * Delete data from db
     *
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public function delete(string $poolId = Pool::MASTER)
    {
        $executor = self::getExecutor($poolId);

        return $executor->delete($this);
    }

    /**
     * Delete data by id
     *
     * @param mixed  $id ID
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public static function deleteById($id, string $poolId = Pool::MASTER)
    {
        $executor = self::getExecutor($poolId);

        return $executor->deleteById(static::class, $id);
    }

    /**
     * Delete by ids
     *
     * @param array  $ids
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public static function deleteByIds(array $ids, string $poolId = Pool::MASTER)
    {
        $executor = self::getExecutor($poolId);

        return $executor->deleteByIds(static::class, $ids);
    }

    /**
     * Update data
     *
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public function update(string $poolId = Pool::MASTER)
    {
        $executor = self::getExecutor($poolId);

        return $executor->update($this);
    }

    /**
     * Find data from db
     *
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public function find(string $poolId = Pool::SLAVE)
    {
        $executor = self::getExecutor($poolId);

        return $executor->find($this);
    }

    /**
     * Find by id
     *
     * @param mixed  $id
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public static function findById($id, string $poolId = Pool::SLAVE)
    {
        $executor = self::getExecutor($poolId);

        return $executor->findById(static::class, $id);
    }

    /**
     * Find by ids
     *
     * @param array  $ids
     * @param string $poolId
     *
     * @return ResultInterface
     */
    public static function findByIds(array $ids, string $poolId = Pool::SLAVE)
    {
        $executor = self::getExecutor($poolId);

        return $executor->findByIds(static::class, $ids);
    }

    /**
     * Get the QueryBuilder
     *
     * @param string $poolId
     *
     * @return QueryBuilder
     */
    public static function query(string $poolId = Pool::SLAVE): QueryBuilder
    {
        return EntityManager::getQuery(static::class, $poolId, true);
    }


    /**
     * Get the exeutor
     *
     * @param string $poolId
     *
     * @return Executor
     */
    private static function getExecutor(string $poolId = Pool::SLAVE): Executor
    {
        $queryBuilder = EntityManager::getQuery(static::class, $poolId);
        $executor     = new Executor($queryBuilder, $poolId);

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
