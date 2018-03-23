<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Db;
use Swoft\Db\Test\Testing\Entity\User;

/**
 * SqlMysqlTest
 */
class SqlMysqlTest extends AbstractDbTestCase
{
    /**
     * @dataProvider mysqlProvider
     * @param int $id
     */
    public function testUpdate(int $id)
    {
        $this->update($id);
    }

    /**
     * @dataProvider mysqlProvider
     * @param int $id
     */
    public function testDelete(int $id)
    {
        $this->delete($id);
    }

    /**
     * @dataProvider mysqlProvider
     * @param int $id
     */
    public function testCoDelete(int $id)
    {
        go(function () use ($id) {
            $result = Db::query('DELETE from user where id=' . $id)->execute()->getResult();
            $this->assertEquals(1, $result);
        });
    }


    /**
     * @dataProvider mysqlProvider
     * @param int $id
     */
    public function testCoUpdate(int $id)
    {
        go(function () use ($id) {
            $this->update($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     * @param int $id
     */
    public function testSelect(int $id)
    {
        $this->select($id);
    }

    /**
     * @dataProvider mysqlProvider
     * @param int $id
     */
    public function testCoSelect(int $id)
    {
        go(function () use ($id) {
            $this->select($id);
        });
    }

    public function testInsert()
    {
        $this->insert();
    }

    public function testCoInsert()
    {
        go(function () {
            $this->insert();
        });
    }

    private function insert()
    {
        $name = "swoft insert";
        $result = Db::query('insert into user(name, sex,description, age) values("' . $name . '", 1, "xxxx", 99)')
                    ->execute()
                    ->getResult();
        $user = User::findById($result)->getResult();

        $this->assertEquals($user['name'], $name);

        $result = Db::query('INSERT into user(name, sex,description, age) values("' . $name . '", 1, "xxxx", 99)')
                    ->execute()
                    ->getResult();
        $user = User::findById($result)->getResult();
        $this->assertEquals($user['name'], $name);
    }

    private function select($id)
    {
        $result = Db::query('select * from user where id=' . $id)->execute()->getResult();
        $this->assertCount(1, $result);

        $result = Db::query('SELECT * from user where id=' . $id)->execute()->getResult();
        $this->assertCount(1, $result);
    }

    private function delete($id)
    {
        $result = Db::query('delete from user where id=' . $id)->execute()->getResult();
        $this->assertEquals(1, $result);
    }

    private function update($id)
    {
        $name = 'update name1';
        $result = Db::query('update user set name="' . $name . '" where id=' . $id)->execute()->getResult();
        $this->assertEquals(1, $result);

        $name = 'update name 协程框架';
        $result = Db::query('UPDATE user set name="' . $name . '" where id=' . $id)->execute()->getResult();
        $this->assertEquals(1, $result);

        $user = User::findById($id)->getResult();
        $this->assertEquals($name, $user['name']);
    }
}