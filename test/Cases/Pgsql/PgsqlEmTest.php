<?php

namespace Swoft\Db\Test\Cases\Pgsql;

/**
 * SyncpgsqlEmTest
 */
class PgsqlEmTest extends DbTestCase
{
    public function testSave()
    {
        $this->emSave();
    }

    public function testCoSave()
    {
        go(function () {
            $this->emSave();
        });
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testDelete(int $id)
    {
        $this->emDelete($id);
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testCoDelete(int $id)
    {
        go(function () use ($id) {
            $this->emDelete($id);
        });
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testDeleteById(int $id)
    {
        $this->emDeleteById($id);
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testCoDeleteById(int $id)
    {
        go(function () use ($id) {
            $this->emDeleteById($id);
        });
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testDeleteByIds(array $ids)
    {
        $this->emDeleteByIds($ids);
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testCoDeleteByIds(array $ids)
    {
        go(function () use ($ids) {
            $this->emDeleteByIds($ids);
        });
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testUpdate(int $id)
    {
        $this->emUpdate($id);
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testCoUpdate(int $id)
    {
        go(function () use ($id) {
            $this->emUpdate($id);
        });
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testFindById(int $id)
    {
        $this->emFindById($id);
    }

    /**
     * @dataProvider pgsqlProvider
     *
     * @param int $id
     */
    public function testCoFindById(int $id)
    {
        go(function () use ($id) {
            $this->emFindById($id);
        });
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testFindByIds(array $ids)
    {
        $this->emFindByIds($ids);
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testCoFindByIds(array $ids)
    {
        go(function () use ($ids) {
            $this->emFindByIds($ids);
        });
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testQuery(array $ids)
    {
        $this->emQuery($ids);
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testSql(array $ids)
    {
        $this->emSql($ids);
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testCoSql(array $ids)
    {
        go(function () use ($ids){
            $this->emSql($ids);
        });
    }

    /**
     * @dataProvider pgsqlProviders
     *
     * @param array $ids
     */
    public function testCoQuery(array $ids)
    {
        go(function () use ($ids) {
            $this->emQuery($ids);
        });
    }
}