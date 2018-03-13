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
    public function test(int $id)
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
}