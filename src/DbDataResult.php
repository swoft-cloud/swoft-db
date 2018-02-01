<?php

namespace Swoft\Db;

use Swoft\Core\AbstractDataResult;

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
        return $this->getResult();
    }
}