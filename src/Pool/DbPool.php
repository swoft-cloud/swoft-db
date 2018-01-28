<?php

namespace Swoft\Db\Pool;

use Swoft\App;
use Swoft\Bean\Annotation\Inject;
use Swoft\Bean\Annotation\Pool;
use Swoft\Db\Bean\Collector\ConnectCollector;
use Swoft\Db\Driver\DriverType;
use Swoft\Db\Exception\DbException;
use Swoft\Db\Pool\Config\DbPoolConfig;
use Swoft\Pool\ConnectPoolInterface;
use Swoft\Db\AbstractDbConnectInterface;

/**
 * The pool of data
 *
 * @Pool()
 * @uses      DbPool
 * @version   2017年09月01日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class DbPool extends ConnectPoolInterface
{
    /**
     * The config of pool
     *
     * @Inject()
     *
     * @var DbPoolConfig
     */
    protected $poolConfig;

    /**
     * Create connect by driver
     *
     * @return AbstractDbConnectInterface
     */
    public function createConnect()
    {
        $driver    = $this->poolConfig->getDriver();
        $collector = ConnectCollector::getCollector();

        if (App::isWorkerStatus()) {
            $connectClassName = $this->getCorConnectClassName($collector, $driver);
        } else {
            $connectClassName = $this->getSyncConnectClassName($collector, $driver);
        }

        return new $connectClassName($this);
    }

    public function reConnect($client)
    {
    }

    /**
     * @param array  $collector
     * @param string $driver
     *
     * @return string
     * @throws \Swoft\Db\Exception\DbException
     */
    private function getCorConnectClassName(array $collector, string $driver): string
    {
        if (!isset($collector[$driver][DriverType::COR])) {
            throw new DbException('The coroutine driver of ' . $driver . ' is not exist!');
        }

        return $collector[$driver][DriverType::COR];
    }

    /**
     * @param array  $collector
     * @param string $driver
     *
     * @return string
     * @throws \Swoft\Db\Exception\DbException
     */
    private function getSyncConnectClassName(array $collector, string $driver): string
    {
        if (!isset($collector[$driver][DriverType::SYNC])) {
            throw new DbException('The synchronous driver of ' . $driver . ' is not exist!');
        }

        return $collector[$driver][DriverType::SYNC];
    }
}
