<?php

namespace Swoft\Db;

/**
 * DbDataResult
 */
class DbDataResult extends DbResult
{
    /**
     * @param array ...$params
     * @return mixed
     */
    public function getResult(...$params)
    {
        $result = $this->getResultByClassName();
        $this->release();
        foreach ($this->decorators ?? [] as $decorator) {
            $result = value($decorator($result));
        }
        return $result;
    }
}