<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Db;
use Swoft\Db\Test\Testing\Entity\User;

/**
 */
class EntityTest extends AbstractDbTestCase
{
    public function testToArray()
    {
        $age  = mt_rand(1, 100);
        $user = new User();
        $user->setId(12);
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge($age);

        $array = $user->toArray();

        $data = [
            'id'          => 12,
            'name'        => 'stelin',
            'sex'         => 1,
            'desc' => 'this my desc',
            'age'         => $age,
        ];
        $this->assertEquals($data, $array);
    }

    public function testToJson()
    {
        $age  = mt_rand(1, 100);
        $user = new User();
        $user->setId(12);
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');
        $user->setAge($age);

        $json   = $user->toJson();
        $string = $user->__toString();
        $data   = '{"id":12,"name":"stelin","age":' . $age . ',"sex":1,"desc":"this my desc"}';
        $this->assertEquals($data, $json);
        $this->assertEquals($data, $string);
    }

    public function testArrayAccess()
    {
        $age  = mt_rand(1, 100);
        $user = new User();
        $user->setId(12);
        $user->setName('stelin');
        $user->setSex(1);
        $user->setDesc('this my desc');

        $user['age'] = $age;

        $this->assertEquals('stelin', $user['name']);
        $this->assertEquals($age, $user['age']);
        $this->assertTrue(isset($user['sex']));
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testIterator($id)
    {
        $user = User::findById($id)->getResult(User::class);
        $data = [];
        foreach ($user as $key => $value){
            $data[$key] = $value;
        }

        $this->assertEquals($data, $user->toArray());
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbSelect(int $id)
    {
        $result = Db::select('*')->from(User::class)->where('id', $id)->limit(1)->execute()->getResult();
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbDelete(int $id)
    {
        $result = Db::delete()->from(User::class)->where('id', $id)->execute()->getResult();
        $this->assertEquals(1, $result);
    }

    /**
     * @dataProvider mysqlProvider
     *
     * @param int $id
     */
    public function testDbUpdate(int $id)
    {
        $result = Db::update(User::class)->set(['name' => 'stelin666'])->where('id', $id)->execute()->getResult();
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

        $result = Db::insert(User::class)->set($values)->execute()->getResult();
        $user   = User::findById($result)->getResult();
        $this->assertCount(5, $user);
    }

}