<?php

namespace Swoft\Db;

/**
 * DbDataResult
 */
class DbDataResult extends DbResult
{
    /**
     * @param array ...$params
     *
     * @return mixed
     */
    public function getResult(...$params)
    {
        list($className) = array_pad($params, 1, '');
        $result = $this->getResultByClass($className);

        $this->release();

        return $result;
    }
}