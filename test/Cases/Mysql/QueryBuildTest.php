<?php

namespace Swoft\Db\Test\Cases\Mysql;

use Swoft\Db\Query;
use Swoft\Db\Test\Testing\Entity\OtherUser;
use Swoft\Db\Test\Testing\Entity\User;
use Swoft\Db\Test\Cases\AbstractMysqlCase;

/**
 * QueryTest
 */
class QueryBuildTest extends AbstractMysqlCase
{
    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbSelect(int $id)
    {
        $result = Query::table(User::class)->where('id', $id)->limit(1)->get()->getResult();
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbSelectByCo(int $id)
    {
        go(function () use ($id) {
            $this->testDbSelect($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbDelete(int $id)
    {
        $result = Query::table(User::class)->where('id', $id)->delete()->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbDeleteByCo(int $id)
    {
        go(function () use ($id) {
            $this->testDbDelete($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbUpdate(int $id)
    {
        $result = Query::table(User::class)->where('id', $id)->update(['name' => 'stelin666'])->getResult();
        $user   = User::findById($id)->getResult();
        $this->assertEquals('stelin666', $user['name']);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbUpdateByCo(int $id)
    {
        go(function () use ($id) {
            $this->testDbUpdate($id);
        });
    }

    public function testDbInsert()
    {
        $values = [
            'name'        => 'stelin',
            'sex'         => 1,
            'description' => 'this my desc',
            'age'         => 99,
        ];
        $result = Query::table(User::class)->insert($values)->getResult();
        $user   = User::findById($result)->getResult();
        $this->assertCount(5, $user);
    }


    public function testDbInsertByCo()
    {
        go(function () {
            $this->testDbInsert();
        });
    }

    public function testSelectDb()
    {
        $data   = [
            'name'        => 'stelin',
            'sex'         => 1,
            'description' => 'this my desc table',
            'age'         => mt_rand(1, 100),
        ];
        $userid = Query::table(User::class)->selectDb('test2')->insert($data)->getResult();

        $user  = User::findById($userid)->getResult();
        $user2 = Query::table(User::class)->selectDb('test2')->where('id', $userid)->limit(1)->get()->getResult();

        $this->assertEquals($user2['description'], 'this my desc table');
        $this->assertEquals($user2['id'], $userid);
    }

    public function testSelectDbByCo()
    {
        go(function () {
            $this->testSelectDb();
        });
    }

    public function testSelectTable()
    {
        $data   = [
            'name'        => 'stelin',
            'sex'         => 1,
            'description' => 'this my desc',
            'age'         => mt_rand(1, 100),
        ];
        $result = Query::table('user2')->insert($data)->getResult();
        $user2 = Query::table('user2')->where('id', $result)->limit(1)->get()->getResult();
        $this->assertEquals($user2['id'], $result);
    }

    public function testSelectTableByCo()
    {
        go(function () {
            $this->testSelectTable();
        });
    }

    public function testSelectinstance()
    {
        $data   = [
            'name'        => 'stelin',
            'sex'         => 1,
            'description' => 'this my desc instance',
            'age'         => mt_rand(1, 100),
        ];
        $userid = Query::table(User::class)->selectInstance('other')->insert($data)->getResult();

        $user  = OtherUser::findById($userid)->getResult();
        $user2 = Query::table(User::class)->selectInstance('other')->where('id', $userid)->limit(1)->get()->getResult();
        $this->assertEquals($user2['description'], 'this my desc instance');
        $this->assertEquals($user2['id'], $userid);
    }
}