<?php

namespace Swoft\Db\Test\Cases;

use Swoft\Db\Test\Testing\Entity\User;

/**
 */
class EntityTest extends AbstractMysqlCase
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
            'id'   => 12,
            'name' => 'stelin',
            'sex'  => 1,
            'desc' => 'this my desc',
            'age'  => $age,
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
        $user = User::findById($id)->getResult();
        $data = [];
        foreach ($user as $key => $value) {
            $data[$key] = $value;
        }

        $this->assertEquals($data, $user->toArray());
    }

    public function testArrayAttr()
    {
        $data = [
            'name' => 'stelin',
            'sex'  => 1,
            'desc' => 'desc2',
            'age'  => 100,
        ];

        $user   = new User();
        $result = $user->fill($data)->save()->getResult();

        $resultUser = User::findById($result)->getResult();
        $this->assertEquals('stelin', $resultUser['name']);
        $this->assertEquals(1, $resultUser['sex']);
        $this->assertEquals('desc2', $resultUser['desc']);
        $this->assertEquals(100, $resultUser['age']);


        $user2         = new User();
        $user2['name'] = 'stelin2';
        $user2['sex']  = 1;
        $user2['desc'] = 'this my desc9';
        $user2['age']  = 99;

        $result2     = $user2->save()->getResult();
        $resultUser2 = User::findById($result2)->getResult();

        $this->assertEquals('stelin2', $resultUser2['name']);
        $this->assertEquals(1, $resultUser2['sex']);
        $this->assertEquals('this my desc9', $resultUser2['desc']);
        $this->assertEquals(99, $resultUser2['age']);
    }
}