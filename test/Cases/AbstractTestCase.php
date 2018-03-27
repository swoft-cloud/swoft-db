<?php

namespace Swoft\Db\Test\Cases;

use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase
 *
 * @package Swoft\Db\Test\Cases
 */
abstract class AbstractTestCase extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
//        swoole_event_exit();
    }

}