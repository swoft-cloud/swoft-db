<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Query;
use Swoft\Db\Test\Testing\Entity\User;

/**
 * QueryTest
 */
class QueryTest extends AbstractMysqlCase
{
    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbSelect(int $id)
    {
        $result = Query::table(User::class)->select('*')->where('id', $id)->limit(1)->execute()->getResult();
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbDelete(int $id)
    {
        $result = Query::table(User::class)->delete()->where('id', $id)->execute()->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbUpdate(int $id)
    {
        $result = Query::table(User::class)->update()->set(['name' => 'stelin666'])->where('id', $id)->execute()->getResult();
        $user   = User::findById($id)->getResult();
        $this->assertEquals('stelin666', $user['name']);
    }

    public function testDbInsert()
    {
        $values = [
            'name'        => 'stelin',
            'sex'         => 1,
            'description' => 'this my desc',
            'age'         => 99,
        ];
        $result = Query::table(User::class)->insert()->set($values)->execute()->getResult();
        $user   = User::findById($result)->getResult();
        $this->assertCount(5, $user);
    }
}