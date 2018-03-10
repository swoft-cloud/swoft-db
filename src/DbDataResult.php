<?php

namespace Swoft\Db;

use Swoft\Core\AbstractDataResult;
use Swoft\Db\Helper\EntityHelper;

/**
 * Class DbDataResult
 *
 * @package Swoft\Db
 */
class DbDataResult extends AbstractDataResult
{
    /**
     * @param array ...$params
     * @return mixed
     */
    public function getResult(...$params)
    {
        $className = '';
        $result = $this->data;
        if (! empty($params)) {
            list($className) = $params;
        }

        // Fill data to Entity
        if (\is_array($result) && ! empty($className)) {
            $result = EntityHelper::resultToEntity($result, $className);
        }

        if($this->pool !== null && $this->connection !== null){
            $this->pool->release($this->connection);
        }

        return $result;
    }
}