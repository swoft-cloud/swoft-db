<?php

namespace Swoft\Db;

use Swoft\Core\AbstractSyncResult;

/**
 * The sync result of db
 */
class DbSyncResult extends AbstractSyncResult
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