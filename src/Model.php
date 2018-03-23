<?php

namespace Swoft\Db;

use Swoft\Core\ResultInterface;
use Swoft\Db\Bean\Collector\EntityCollector;

/**
 * The model of activerecord
 */
class Model implements \ArrayAccess, \Iterator
{
    /**
     * The data of old
     *
     * @var array
     */
    private $attrs = [];

    /**
     * Model constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Insert data to db
     *
     * @param string $group
     *
     * @return ResultInterface
     */
    public function save(string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->save($this);
    }

    /**
     * Delete data from db
     *
     * @param string $group
     *
     * @return ResultInterface
     */
    public function delete(string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->delete($this);
    }

    /**
     * Delete data by id
     *
     * @param mixed  $id ID
     * @param string $group
     *
     * @return ResultInterface
     */
    public static function deleteById($id, string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->deleteById(static::class, $id);
    }

    /**
     * Delete by ids
     *
     * @param array  $ids
     * @param string $group
     *
     * @return ResultInterface
     */
    public static function deleteByIds(array $ids, string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->deleteByIds(static::class, $ids);
    }

    /**
     * Update data
     *
     * @param string $group
     *
     * @return ResultInterface
     */
    public function update(string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->update($this);
    }

    /**
     * Find data from db
     *
     * @param string $group
     *
     * @return ResultInterface
     */
    public function find(string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->find($this);
    }

    /**
     * Find by id
     *
     * @param mixed  $id
     * @param string $group
     *
     * @return ResultInterface
     */
    public static function findById($id, string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->findById(static::class, $id);
    }

    /**
     * Find by ids
     *
     * @param array  $ids
     * @param string $group
     *
     * @return ResultInterface
     */
    public static function findByIds(array $ids, string $group = Pool::GROUP)
    {
        $executor = self::getExecutor($group);

        return $executor->findByIds(static::class, $ids);
    }

    /**
     * Get the QueryBuilder
     *
     * @param string $group
     *
     * @return QueryBuilder
     */
    public static function query(string $group = Pool::GROUP): QueryBuilder
    {
        return EntityManager::getQuery(static::class, $group);
    }

    /**
     * Get the exeutor
     *
     * @param string $group
     *
     * @return Executor
     */
    private static function getExecutor(string $group = Pool::GROUP): Executor
    {
        $queryBuilder = EntityManager::getQuery(static::class, $group);
        $executor     = new Executor($queryBuilder, $group);

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

    /**
     * @param array $attributes
     *
     * $attributes = [
     *     'name' => $value
     * ]
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $methodName = sprintf('set%s', ucfirst($name));
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $entities = EntityCollector::getCollector();
        $columns  = $entities[static::class]['field'];

        $data = [];
        foreach ($columns as $propertyName => $column) {
            $methodName = sprintf('get%s', ucfirst($propertyName));
            if (!method_exists($this, $methodName) || !isset($column['column'])) {
                continue;
            }

            $data[$propertyName] = $this->$methodName();
        }

        return $data;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Whether a offset exists
     *
     * @param mixed $offset
     *
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        $data = $this->toArray();

        return isset($data[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        $data  = $this->toArray();
        $value = $data[$offset]??null;

        return $value;
    }

    /**
     * Offset to set
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->fill([$offset => $value]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {

    }

    /**
     * Return the current element
     *
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->attrs);
    }

    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->attrs);
    }

    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->attrs);
    }

    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return ($this->current() !== false);
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->attrs);
    }
}
