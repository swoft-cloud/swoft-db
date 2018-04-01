<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\QueryBuilder;
use Swoft\Db\Test\Testing\Entity\User;

/**
 * MysqlTest
 */
class MysqlTest extends AbstractMysqlCase
{
    public function testSave()
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));

        $id     = $user->save()->getResult();
        $reuslt = $id > 0;
        $this->assertTrue($reuslt);
    }

    public function testSaveByCo()
    {
        go(function () {
            $this->testSave();
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDelete(int $id)
    {
        /* @var User $user */
        $user   = User::findById($id)->getResult(User::class);
        $result = $user->delete()->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDeleteByCo(int $id)
    {
        go(function () use ($id) {
            $this->testDelete($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDeleteById(int $id)
    {
        $result = User::deleteById($id)->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDeleteByIdByCo(int $id)
    {
        go(function () use ($id) {
            $this->testDeleteById($id);
        });
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testDeleteByIds(array $ids)
    {
        $result = User::deleteByIds($ids)->getResult();
        $this->assertEquals($result, 2);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testDeleteByIdsByCo(array $ids)
    {
        go(function () use ($ids) {
            $this->testDeleteByIds($ids);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testUpdate(int $id)
    {
        $newName = 'swoft framewrok';

        /* @var User $user */
        $user = User::findById($id)->getResult(User::class);
        $user->setName($newName);
        $user->update()->getResult();

        /* @var User $newUser */
        $newUser = User::findById($id)->getResult(User::class);
        $this->assertEquals($newName, $newUser->getName());
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testUpdateByCo(int $id)
    {
        go(function () use ($id) {
            $this->testUpdate($id);
        });
    }


    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testFindById(int $id)
    {
        $user      = User::findById($id)->getResult();
        $userEmpty = User::findById(99999999999)->getResult();
        $this->assertEquals($id, $user['id']);
        $this->assertEquals($userEmpty, []);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testFindByIdByCo(int $id)
    {
        go(function () use ($id) {
            $this->testFindById($id);
        });
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testFindByIdClass(int $id)
    {
        /* @var User $user */
        $user      = User::findById($id)->getResult(User::class);
        $userEmpty = User::findById(99999999999)->getResult(User::class);
        $this->assertEquals($id, $user->getId());
        $this->assertEquals($userEmpty, null);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testFindByIdClassByCo(int $id)
    {
        go(function () use ($id) {
            $this->testFindByIdClass($id);
        });
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testFindByIds(array $ids)
    {
        $users     = User::findByIds($ids)->getResult();
        $userEmpty = User::findByIds([999999999999])->getResult();

        $resultIds = [];
        foreach ($users as $user) {
            $resultIds[] = $user['id'];
        }
        $this->assertEquals(sort($resultIds), sort($ids));
        $this->assertEquals($userEmpty, []);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testFindByIdsByCo(array $ids)
    {
        go(function () use ($ids) {
            $this->testFindByIds($ids);
        });
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testFindByIdsByClass(array $ids)
    {
        $users     = User::findByIds($ids)->getResult(User::class);
        $userEmpty = User::findByIds([999999999999])->getResult(User::class);

        $resultIds = [];
        /* @var User $user */
        foreach ($users as $user) {
            $resultIds[] = $user->getId();
        }
        $this->assertEquals(sort($resultIds), sort($ids));
        $this->assertEquals($userEmpty, []);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testFindByIdsByClassByCo(array $ids)
    {
        go(function () use ($ids) {
            $this->testFindByIdsByClass($ids);
        });
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testQuery(array $ids)
    {
        $result = User::query()->orderBy('id', QueryBuilder::ORDER_BY_DESC)->limit(2)->get()->getResult();
        $this->assertCount(2, $result);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testQueryByCo(array $ids)
    {
        go(function () use ($ids) {
            $this->testQuery($ids);
        });
    }
}