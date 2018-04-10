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
     * @return mixed
     */
    public function getResult(...$params)
    {
        $this->recv(true, false);
        $result = $this->getResultByClassName();
        $this->release();

        foreach ($this->decorators ?? [] as $decorator) {
            $result = value($decorator($result));
        }
        return $result;
    }
}