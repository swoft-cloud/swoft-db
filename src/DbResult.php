<?php

namespace Swoft\Db;

use Swoft\Core\AbstractResult;
use Swoft\Db\Helper\EntityHelper;

/**
 * DbResult
 */
abstract class DbResult extends AbstractResult
{
    /**
     * Result type
     *
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $className = '';

    /**
     * @param int $type
     */
    public function setType(int $type)
    {
        $this->type = $type;
    }

    /**
     * @param string $className
     */
    public function setClassName(string $className)
    {
        $this->className = $className;
    }

    /**
     * @return mixed
     */
    protected function getResultByClassName()
    {
        $className = $this->className;
        $result = $this->getResultByType();

        if (isset($result[0]) && !empty($className)) {
            return EntityHelper::listToEntity($result, $className);
        }

        if (is_array($result) && !empty($result) && !empty($className)) {
            return EntityHelper::arrayToEntity($result, $className);
        }

        if (!empty($className) && $this->type == Db::RETURN_FETCH && empty($result)) {
            return [];
        }

        if (!empty($className) && $this->type == Db::RETURN_ONE && empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    private function getResultByType()
    {
        /* @var AbstractDbConnection $connection */
        $connection = $this->connection;

        if ($this->type == Db::RETURN_INSERTID) {
            return $this->connection->getInsertId();;
        }

        if ($this->type == Db::RETURN_ROWS) {
            return $connection->getAffectedRows();
        }

        if ($this->type == Db::RETURN_FETCH) {
            return $connection->fetch();
        }

        $result = $connection->fetch();
        $result = $result[0]??[];

        return $result;
    }
}