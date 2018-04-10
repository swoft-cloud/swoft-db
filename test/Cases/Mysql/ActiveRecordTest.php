<?php

namespace Swoft\Db\Test\Cases\Mysql;

use Swoft\Db\Query;
use Swoft\Db\QueryBuilder;
use Swoft\Db\Test\Testing\Entity\User;
use Swoft\Db\Test\Cases\AbstractMysqlCase;

/**
 * MysqlTest
 */
class ActiveRecordTest extends AbstractMysqlCase
{
    public function testSave()
    {
        $user = new User();
        $user->setName('name');
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


    public function testBatchInsert()
    {
        $values = [
            [
                'name'        => 'name',
                'sex'         => 1,
                'description' => 'this my desc',
                'age'         => 99,
            ],
            [
                'name'        => 'name2',
                'sex'         => 1,
                'description' => 'this my desc2',
                'age'         => 100,
            ]
        ];

        $result = User::batchInsert($values)->getResult();
        $this->assertTrue($result > 0);
    }

    public function testBatchInsertByCo()
    {
        go(function (){
            $this->testBatchInsert();
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
        $user   = User::findById($id)->getResult();
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
        $user = User::findById($id)->getResult();
        $user->setName($newName);
        $updateResult = $user->update()->getResult();
        $this->assertGreaterThanOrEqual(1, $updateResult);

        /* @var User $newUser */
        $newUser = User::findById($id)->getResult();
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
        $this->assertEquals($userEmpty, null);
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
        $user      = User::findById($id)->getResult();
        $userEmpty = User::findById(99999999999)->getResult();
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
        $users     = User::findByIds($ids)->getResult();
        $userEmpty = User::findByIds([999999999999])->getResult();

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

    public function testDeleteOne()
    {
        $user         = new User();
        $user['name'] = 'name2testDeleteOne';
        $user['sex']  = 1;
        $user['desc'] = 'this my desc9';
        $user['age']  = 99;

        $uid = $user->save()->getResult();
        $result = User::deleteOne(['name' => 'name2testDeleteOne', 'age' => 99, 'id' => $uid])->getResult();
        $this->assertEquals($result, 1);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testDeleteAll(array $ids)
    {
        $result = User::deleteAll(['name' => 'name', 'id' => $ids])->getResult();
        $this->assertEquals(2,$result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testUpdateOne(int $id)
    {
        $result = User::updateOne(['name' => 'testUpdateOne'], ['id' => $id])->getResult();
        $user = User::findById($id)->getResult();
        $this->assertEquals(1, $result);
        $this->assertEquals($user['name'], 'testUpdateOne');
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testUpdateAll(array $ids)
    {
        $result = User::updateAll(['name' => 'testUpdateAll'], ['id' => $ids])->getResult();
        $count = User::findAll(['name' => 'testUpdateAll', 'id' => $ids])->getResult();
        $this->assertEquals(2,$result);
        $this->assertCount(2,$count);
    }


    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testFindOne(int $id)
    {
        $user = User::findOne(['id' => $id, 'name' => 'name'], ['id'=> 'desc', 'age' => 'desc'])->getResult();
        $this->assertEquals($id, $user['id']);
    }

    /**
     * @dataProvider mysqlProviders
     *
     * @param array $ids
     */
    public function testFindAll(array $ids)
    {
        $result = User::findAll(['name' => 'name', 'id' => $ids], ['id' => 'desc'], 2, 0)->getResult();
        $this->assertCount(2,$result);
    }
}