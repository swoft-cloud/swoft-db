<?php

namespace Swoft\Db;

/**
 * Class DbCoResult
 *
 * @package Swoft\Db
 */
class DbCoResult extends DbResult
{

    /**
     * @param array ...$params
     *
     * @return mixed
     */
    public function getResult(...$params)
    {
        list($className) = array_pad($params, 1, '');
        $this->recv(true);
        $result = $this->getResultByClass($className);
        return $result;
    }
}