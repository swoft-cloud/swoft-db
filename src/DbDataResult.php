<?php

namespace Swoft\Db;

use Swoft\Core\AbstractDataResult;
use Swoft\Db\Helper\EntityHelper;

/**
 * The sync result of db
 */
class DbDataResult extends AbstractDataResult
{
    /**
     * @param array ...$params
     *
     * @return mixed
     */
    public function getResult(...$params)
    {
        $className = "";
        $result = $this->data;
        if (!empty($params)) {
            list($className) = $params;
        }

        // fill data to entity
        if (is_array($result) && !empty($className)) {
            $result = EntityHelper::resultToEntity($result, $className);
        }

        return $result;
    }
}