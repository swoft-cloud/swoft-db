<?php

namespace Swoft\Db;

/**
 * Database interface
 */
interface DbConnectInterface
{
    /**
     * @param string $sql
     */
    public function prepare(string $sql);

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function execute(array $params = []);

    /**
     * Begin transaction
     */
    public function beginTransaction();

    /**
     * @return mixed
     */
    public function getInsertId();

    /**
     * @return int
     */
    public function getAffectedRows();

    /**
     * @return mixed
     */
    public function fetch();

    /**
     * Rollback transaction
     */
    public function rollback();

    /**
     * Commit transaction
     */
    public function commit();

    /**
     * @return array|bool
     */
    public function recv();

    /**
     * @param bool $defer
     */
    public function setDefer($defer = true);

    /**
     * Destory
     */
    public function destory();

    /**
     * @return string
     */
    public function getSql();
}
