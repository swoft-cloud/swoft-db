<?php

namespace Swoft\Db;

use Swoft\Db\Helper\DbHelper;

/**
 * Db
 */
class Db
{
    /**
     * @param string $sql
     * @param string $group
     *
     * @return \Swoft\Db\QueryBuilder
     */
    public static function query(string $sql = '', string $group = Pool::GROUP): QueryBuilder
    {
        $queryBuilderClassName = DbHelper::getQueryClassNameByGroup($group);

        return new $queryBuilderClassName($group, $sql);
    }

    /**
     * @param string $group
     *
     * @return \Swoft\Db\QueryBuilder
     */
    public static function delete(string $group = Pool::GROUP)
    {
        $queryBuilderClassName = DbHelper::getQueryClassNameByGroup($group);
        /* @var \Swoft\Db\QueryBuilder $queryBuidler */
        $queryBuidler = new $queryBuilderClassName($group);
        $queryBuidler->delete();

        return $queryBuidler;
    }

    /**
     * @param string $tableName
     * @param string $group
     *
     * @return \Swoft\Db\QueryBuilder
     */
    public static function insert(string $tableName, string $group = Pool::GROUP)
    {
        $queryBuilderClassName = DbHelper::getQueryClassNameByGroup($group);
        /* @var \Swoft\Db\QueryBuilder $queryBuidler */
        $queryBuidler = new $queryBuilderClassName($group);
        $queryBuidler->insert($tableName);

        return $queryBuidler;
    }

    /**
     * @param string $tableName
     * @param string $group
     *
     * @return \Swoft\Db\QueryBuilder
     */
    public static function update(string $tableName, string $group = Pool::GROUP)
    {
        $queryBuilderClassName = DbHelper::getQueryClassNameByGroup($group);
        /* @var \Swoft\Db\QueryBuilder $queryBuidler */
        $queryBuidler = new $queryBuilderClassName($group);
        $queryBuidler->update($tableName);

        return $queryBuidler;
    }

    /**
     * @param mixed       $column
     * @param string|null $alias
     * @param string      $group
     *
     * @return \Swoft\Db\QueryBuilder
     */
    public static function select($column, string $alias = null, string $group = Pool::GROUP)
    {
        $queryBuilderClassName = DbHelper::getQueryClassNameByGroup($group);
        /* @var \Swoft\Db\QueryBuilder $queryBuidler */
        $queryBuidler = new $queryBuilderClassName($group);
        $queryBuidler->select($column, $alias);

        return $queryBuidler;
    }
}