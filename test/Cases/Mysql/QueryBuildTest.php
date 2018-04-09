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
        $result = Query::table(User::class)->where('id', $id)->update(['name' => 'name666'])->getResult();
        $user   = User::findById($id)->getResult();
        $this->assertEquals('name666', $user['name']);
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
            'name'        => 'name',
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
            'name'        => 'name',
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
            'name'        => 'name',
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
            'name'        => 'name',
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

    public function testCondtionAndByF1()
    {
        $age = mt_rand(1, 100);
        $data   = [
            'name'        => 'nameQuery',
            'sex'         => 1,
            'description' => 'this my desc instance',
            'age'         => $age,
        ];
        $userid = Query::table(User::class)->insert($data)->getResult();
        $user = Query::table(User::class)->condition(['name' => 'nameQuery', 'age'=> $age])->limit(1)->get()->getResult();

        $this->assertEquals('nameQuery', $user['name']);
        $this->assertEquals($age, $user['age']);

        $user2 = Query::table(User::class)->where('id', $userid)->condition(['name' => 'nameQuery', 'age'=> $age])->limit(1)->get()->getResult();
        $this->assertEquals('nameQuery', $user2['name']);
        $this->assertEquals($age, $user2['age']);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testCondtion2AndByF1(array $ids)
    {
        $users = Query::table(User::class)->condition(['sex' => 1, 'id' => $ids])->get()->getResult();
        $this->assertCount(2, $users);
    }

    public function testCondtion1AndByF3()
    {
        $age = mt_rand(1, 100);
        $data   = [
            'name'        => 'nameQuery',
            'sex'         => 1,
            'description' => 'this my desc instance',
            'age'         => $age-1,
        ];

        $userid = Query::table(User::class)->insert($data)->getResult();
        $user = Query::table(User::class)->condition(['age', '<',$age])->limit(1)->orderBy('id', 'desc')->get()->getResult();
        $this->assertEquals($userid, $user['id']);
    }

    public function testCondtion2AndByF3()
    {
        $age = mt_rand(1, 100);
        $data   = [
            'name'        => 'nameQuery',
            'sex'         => 1,
            'description' => 'this my desc instance',
            'age'         => $age-1,
        ];

        $userid = Query::table(User::class)->insert($data)->getResult();
        $users = Query::table(User::class)->condition(['age', 'between', $age, $age+1])->orderBy('id', 'desc')->get()->getResult();

        $this->assertTrue(count($users) > 1);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testCondtion3AndByF3(int $id)
    {
        $age = mt_rand(1, 100);
        $data   = [
            'name'        => 'nameQuery',
            'sex'         => 1,
            'description' => 'this my desc instance',
            'age'         => $age-1,
        ];

        $userid = Query::table(User::class)->insert($data)->getResult();
        $users = Query::table(User::class)->condition(['age', 'not between', $age, $age+1])->orderBy('id', 'desc')->get()->getResult();

        $this->assertTrue(count($users) > 1);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testCondtion4AndByF3(array $ids)
    {
        $users = Query::table(User::class)->condition(['id', 'in', $ids])->orderBy('id', 'desc')->get()->getResult();

        $this->assertCount(2, $users);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testCondtion5AndByF3(array $ids)
    {
        $users = Query::table(User::class)->condition(['id', 'not in', $ids])->orderBy('id', 'desc')->get()->getResult();

        $this->assertTrue(count($users) > 2);
    }
}