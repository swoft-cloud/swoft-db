<?php

namespace Swoft\Db\Test\Cases;

use PHPUnit\Framework\TestCase;
use Swoft\Db\EntityManager;
use Swoft\Db\Pool;
use Swoft\Db\QueryBuilder;
use Swoft\Db\Test\Testing\Entity\User;
use Swoft\Db\Types;

/**
 * DbTestCache
 */
class DbTestCase extends TestCase
{
    public function arSave(string $poolId = Pool::MASTER)
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));

        $id     = $user->save($poolId)->getResult();
        $reuslt = $id > 0;
        $this->assertTrue($reuslt);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function arDelete(int $id, string $poolId = Pool::MASTER)
    {
        /* @var User $user */
        $user   = User::findById($id, $poolId)->getResult(User::class);
        $result = $user->delete($poolId)->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function arDeleteById(int $id, string $poolId = Pool::MASTER)
    {
        $result = User::deleteById($id, $poolId)->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @param array $ids
     * @param string $poolId
     */
    public function arDeleteByIds(array $ids, string $poolId = Pool::MASTER)
    {
        $result = User::deleteByIds($ids, $poolId)->getResult();
        $this->assertEquals($result, 2);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function arUpdate(int $id, string $poolId = Pool::MASTER)
    {
        $newName = 'swoft framewrok';

        /* @var User $user */
        $user = User::findById($id, $poolId)->getResult(User::class);
        $user->setName($newName);
        $user->update($poolId)->getResult();

        /* @var User $newUser */
        $newUser = User::findById($id, $poolId)->getResult(User::class);
        $this->assertEquals($newName, $newUser->getName());
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function arFindById(int $id, string $poolId = Pool::SLAVE)
    {
        $user = User::findById($id, $poolId)->getResult();
        $this->assertEquals($id, $user['id']);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function arFindByIdClass(int $id, string $poolId = Pool::SLAVE)
    {
        /* @var User $user */
        $user = User::findById($id, $poolId)->getResult(User::class);
        $this->assertEquals($id, $user->getId());
    }

    /**
     * @param array $ids
     * @param string $poolId
     */
    public function arFindByIds(array $ids, string $poolId = Pool::SLAVE)
    {
        $users = User::findByIds($ids, $poolId)->getResult();

        $resultIds = [];
        foreach ($users as $user) {
            $resultIds[] = $user['id'];
        }
        $this->assertEquals(sort($resultIds), sort($ids));
    }

    /**
     * @param array  $ids
     * @param string $poolId
     */
    public function arFindByIdsByClass(array $ids, string $poolId = Pool::SLAVE)
    {
        $users = User::findByIds($ids, $poolId)->getResult(User::class);

        $resultIds = [];
        /* @var User $user */
        foreach ($users as $user) {
            $resultIds[] = $user->getId();
        }
        $this->assertEquals(sort($resultIds), sort($ids));
    }

    public function arQuery(array $ids, string $poolId = Pool::MASTER)
    {
        $result = User::query($poolId)->select('*')->orderBy('id', QueryBuilder::ORDER_BY_DESC)->limit(2)->execute()->getResult();
        $this->assertEquals(2, count($result));
    }

    public function emSave(string $poolId = Pool::MASTER)
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));

        $em = EntityManager::create($poolId);
        $id = $em->save($user)->getResult();
        $em->close();

        $reuslt = $id > 0;
        $this->assertTrue($reuslt);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function emDelete(int $id, string $poolId = Pool::MASTER)
    {

        /* @var User $user */
        $user   = User::findById($id, $poolId)->getResult(User::class);
        $em = EntityManager::create($poolId);
        $result = $em->delete($user)->getResult();
        $em->close();

        $this->assertEquals(1, $result);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function emDeleteById(int $id, string $poolId = Pool::MASTER)
    {
        $em = EntityManager::create($poolId);
        $result = $em->deleteById(User::class, $id)->getResult();
        $em->close();

        $this->assertEquals(1, $result);
    }

    /**
     * @param array $ids
     * @param string $poolId
     */
    public function emDeleteByIds(array $ids, string $poolId = Pool::MASTER)
    {
        $em = EntityManager::create($poolId);
        $result = $em->deleteByIds(User::class, $ids)->getResult();
        $em->close();

        $this->assertEquals($result, 2);
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function emUpdate(int $id, string $poolId = Pool::MASTER)
    {
        $newName = 'swoft framewrok';

        /* @var User $user */
        $user = User::findById($id, $poolId)->getResult(User::class);
        $user->setName($newName);
        $user->update($poolId)->getResult();

        $em = EntityManager::create($poolId);
        $em->update($user);
        $em->close();

        /* @var User $newUser */
        $newUser = User::findById($id, $poolId)->getResult(User::class);
        $this->assertEquals($newName, $newUser->getName());
    }

    /**
     * @param int $id
     * @param string $poolId
     */
    public function emFindById(int $id, string $poolId = Pool::SLAVE)
    {
        $em = EntityManager::create($poolId);
        $user = $em->findById(User::class, $id)->getResult();
        $em->close();

        $this->assertEquals($id, $user['id']);
    }

    /**
     * @param array $ids
     * @param string $poolId
     */
    public function emFindByIds(array $ids, string $poolId = Pool::SLAVE)
    {
        $em = EntityManager::create($poolId);
        $users = $em->findByIds(User::class, $ids)->getResult();
        $em->close();

        $resultIds = [];
        foreach ($users as $user) {
            $resultIds[] = $user['id'];
        }
        $this->assertEquals(sort($resultIds), sort($ids));
    }


    public function emQuery(array $ids, string $poolId = Pool::MASTER)
    {
        $em = EntityManager::create($poolId);
        $result = $em->createQuery()->select('*')->from(User::class)->orderBy('id', QueryBuilder::ORDER_BY_DESC)->limit(2)->execute()->getResult();
        $em->close();

        $this->assertEquals(2, count($result));
    }

    public function emSql(array $ids, string $poolId = Pool::MASTER){
        $em = EntityManager::create($poolId);
        $result = $em->createQuery('select * from user where id in(?, ?) and name = ? order by id desc limit 2')
            ->setParameter(0, $ids[0])
            ->setParameter(1, $ids[1])
            ->setParameter(2, 'stelin')
            ->execute()->getResult();
        $em->close();

        $em = EntityManager::create($poolId);
        $result2 = $em->createQuery('select * from user where id in(?, ?) and name = ? order by id desc limit 2')
            ->setParameter(0, $ids[0])
            ->setParameter(1, $ids[1])
            ->setParameter(2, 'stelin', Types::STRING)
            ->execute()->getResult();
        $em->close();

        $em = EntityManager::create($poolId);
        $result3 = $em->createQuery('select * from user where id in(?, ?) and name = ? order by id desc limit 2')
            ->setParameters([$ids[0], $ids[1], 'stelin'])
            ->execute()->getResult();
        $em->close();

        $em = EntityManager::create($poolId);
        $result4 = $em->createQuery('select * from user where id in(:id1, :id2) and name = :name order by id desc limit 2')
            ->setParameter(':id1', $ids[0])
            ->setParameter('id2', $ids[1])
            ->setParameter('name', 'stelin')
            ->execute()->getResult();
        $em->close();

        $em = EntityManager::create($poolId);
        $result5 = $em->createQuery('select * from user where id in(:id1, :id2) and name = :name order by id desc limit 2')
            ->setParameters([
                'id1' => $ids[0],
                ':id2' => $ids[1],
                'name' => 'stelin'
            ])
            ->execute()->getResult();
        $em->close();


        $em = EntityManager::create($poolId);
        $result6 = $em->createQuery('select * from user where id in(:id1, :id2) and name = :name order by id desc limit 2')
            ->setParameters([
                ['id1', $ids[0]],
                [':id2', $ids[1], Types::INT],
                ['name', 'stelin', Types::STRING],
            ])
            ->execute()->getResult();
        $em->close();

        $this->assertEquals(2, count($result));
        $this->assertEquals(2, count($result2));
        $this->assertEquals(2, count($result3));
        $this->assertEquals(2, count($result4));
        $this->assertEquals(2, count($result5));
        $this->assertEquals(2, count($result6));
    }

    public function addUsers(string $poolId = Pool::MASTER)
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));
        $id  = $user->save($poolId)->getResult();
        $id2 = $user->save($poolId)->getResult();

        return [
            [[$id, $id2]],
        ];
    }

    public function addUser(string $poolId = Pool::MASTER)
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));
        $id = $user->save($poolId)->getResult();

        return [
            [$id],
        ];
    }

    public function mysqlProviders()
    {
        return $this->addUsers();
    }

    public function mysqlProvider()
    {
        return $this->addUser();
    }
}