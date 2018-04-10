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
        swoole_timer_after(2 * 1000, function () {
            swoole_event_exit();
        });
    }

}