<?php

namespace Swoft\Db;

/**
 * Query
 */
class Query
{
    /**
     * @param string $tableName
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public static function table(string $tableName, string $alias = null)
    {
        $query = new QueryBuilder();
        $query = $query->table($tableName, $alias);

        return $query;
    }

}