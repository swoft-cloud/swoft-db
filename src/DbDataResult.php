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
        $result = $this->getResultByClassName();
        $this->release();

        return $result;
    }
}