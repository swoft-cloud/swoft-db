<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Db;
use Swoft\Db\Test\Testing\Entity\User;

/**
 * SqlMysqlTest
 */
class SqlMysqlTest extends AbstractMysqlCase
{
    public function testInsert()
    {
        $name   = "swoft insert";
        $result = Db::query('insert into user(name, sex,description, age) values("' . $name . '", 1, "xxxx", 99)')->getResult();
        $user   = User::findById($result)->getResult();

        $this->assertEquals($user['name'], $name);

        $result = Db::query('INSERT into user(name, sex,description, age) values("' . $name . '", 1, "xxxx", 99)')->getResult();
        $user   = User::findById($result)->getResult();
        $this->assertEquals($user['name'], $name);
    }

    public function testInsertByCo()
    {
        go(function () {
            $this->testInsert();
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param $id
     */
    public function testSelect($id)
    {
        $result = Db::query('select * from user where id=' . $id)->getResult();
        $this->assertCount(1, $result);

        $result = Db::query('SELECT * from user where id=' . $id)->getResult();
        $this->assertCount(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param $id
     */
    public function testSelectByCo($id)
    {
        go(function () use ($id) {
            $this->testSelect($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param $id
     */
    public function testDelete($id)
    {
        $result = Db::query('delete from user where id=' . $id)->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param $id
     */
    public function testDeleteByCo($id)
    {
        go(function () use ($id) {
            $this->testDelete($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param $id
     */
    public function testUpdate($id)
    {
        $name   = 'update name1';
        $result = Db::query('update user set name="' . $name . '" where id=' . $id)->getResult();
        $this->assertEquals(1, $result);

        $name   = 'update name 协程框架';
        $result = Db::query('UPDATE user set name="' . $name . '" where id=' . $id)->getResult();
        $this->assertEquals(1, $result);

        $user = User::findById($id)->getResult();
        $this->assertEquals($name, $user['name']);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param $id
     */
    public function testUpdateByCo($id)
    {
        go(function () use ($id) {
            $this->testUpdate($id);
        });
    }
}