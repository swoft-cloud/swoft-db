<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Test\Testing\Entity\User;

/**
 * BugMysqlTest
 */
class BugMysqlTest extends DbTestCase
{
    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testResult(int $id)
    {
        $query = User::query()->select('name,id')->where('id', $id)->limit(1)->execute();

        $result = $query->getResult();

        $this->assertCount(2, $result);
        $this->assertFalse(empty(get_last_sql()));
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testCo(int $id)
    {
        go(function () use ($id) {
            $query = User::query()->select('name,id')->where('id', $id)->limit(1)->execute();

            $result = $query->getResult();
            $this->assertCount(2, $result);
            $this->assertFalse(empty(get_last_sql()));
        });
    }

    public function testAttrs()
    {
        $attrs = [
            'name' => 'stelin3',
            'sex'  => 1,
            'desc' => 'this is my desc2',
            'age'  => 99,
        ];
        $user  = new User();
        $user->set($attrs);
        $result = $user->save()->getResult();

        /* @var User $user */
        $user = User::findById($result)->getResult(User::class);

        $this->assertEquals($user->getName(), 'stelin3');
        $this->assertEquals($user->getSex(), 1);
        $this->assertEquals($user->getDesc(), 'this is my desc2');
        $this->assertEquals($user->getAge(), 99);
    }
}