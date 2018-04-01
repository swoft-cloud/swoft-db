<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Test\Testing\Entity\User;

/**
 * DbTestCache
 */
abstract class AbstractMysqlCase extends AbstractTestCase
{
    public function addUsers()
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));
        $id  = $user->save()->getResult();
        $id2 = $user->save()->getResult();

        return [
            [[$id, $id2]],
        ];
    }

    public function addUser()
    {
        $user = new User();
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge(mt_rand(1, 100));
        $id = $user->save()->getResult();

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
