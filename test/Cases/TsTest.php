<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\EntityManager;
use Swoft\Db\Test\Testing\Entity\User;

class TsTest extends DbTestCase
{
    public function testTsRollback()
    {
        $this->rollback();
        go(function () {
            $this->rollback();
        });
    }

    public function rollback()
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));

        $em = EntityManager::create();
        $em->beginTransaction();
        $uid  = $em->save($user)->getResult();
        $uid2 = $user->save()->getResult();
        $em->rollback();

        $user1 = User::findById($uid);
        $user2 = User::findById($uid2);

        $user1 = $user1->getResult();
        $user2 = $user2->getResult();

        $this->assertTrue(empty($user1));
        $this->assertTrue(empty($user2));
    }

    public function testTsCommit()
    {
        $this->commit();
        go(function () {
            $this->commit();
        });
    }

    public function commit()
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));

        $em = EntityManager::create();
        $em->beginTransaction();
        $uid  = $em->save($user)->getResult();
        $uid2 = $user->save()->getResult();
        $em->commit();

        $user1 = User::findById($uid);
        $user2 = User::findById($uid2);

        $user1Id = $user1->getResult();
        $user2Id = $user2->getResult();

        $this->assertEquals($uid, $user1Id['id']);
        $this->assertEquals($uid2, $user2Id['id']);
    }
}