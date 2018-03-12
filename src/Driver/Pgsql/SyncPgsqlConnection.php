<?php

namespace Swoft\Db\Driver\Pgsql;

use Swoft\App;
use Swoft\Db\AbstractDbConnection;
use Swoft\Db\Bean\Annotation\Connect;
use Swoft\Db\Driver\DriverType;

/**
 * 同步Mysql连接
 *
 * @Connect(type=DriverType::SYNC)
 * @uses      SyncMysqlConnect
 * @version   2017年09月30日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class SyncPgsqlConnection extends AbstractDbConnection
{
    /**
     * Mysql连接
     *
     * @var \PDO
     */
    private $connection;

    /**
     * 预处理
     *
     * @var \PDOStatement
     */
    private $stmt;

    /**
     * SQL语句
     *
     * @var string
     */
    private $sql;

    /**
     * 创建连接
     */
    public function createConnection()
    {
        // 配置信息初始化
        $uri                = $this->pool->getConnectionAddress();
        $options            = $this->parseUri($uri);
        $options['timeout'] = $this->pool->getTimeout();

        $user    = $options['user'];
        $passwd  = $options['password'];
        $host    = $options['host'];
        $port    = $options['port'];
        $dbName  = $options['database'];
        $timeout = $options['timeout'];

        // 组拼$dsn串
        $pdoOptions    = [
            \PDO::ATTR_TIMEOUT    => $timeout,
            \PDO::ATTR_PERSISTENT => true,
        ];
        $dsn           = "pgsql:host=$host;port=$port;dbname=$dbName;";
        $this->connect = new \PDO($dsn, $user, $passwd, $pdoOptions);
    }

    /**
     * 预处理
     *
     * @param string $sql
     */
    public function prepare(string $sql)
    {
        $this->sql  = $sql . " Params:";
        $this->stmt = $this->connect->prepare($sql);
    }

    /**
     * 执行SQL
     *
     * @param array|null $params
     * @return array|bool
     */
    public function execute(array $params = null)
    {
        $this->bindParams($params);
        $this->formatSqlByParams($params);
        $result = $this->stmt->execute();
        if (App::isWorkerStatus()) {
            App::info($this->sql);
        }
        if ($result !== true) {
            if (App::isWorkerStatus()) {
                App::error('数据库执行错误，sql=' . $this->stmt->debugDumpParams());
            }

            return $result;
        }

        return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 绑定参数
     *
     * @param array|null $params
     */
    private function bindParams(array $params = null)
    {
        if (empty($params)) {
            return;
        }

        foreach ($params as $key => $value) {
            $this->stmt->bindValue($key, $value);
        }
    }

    /**
     * @return void
     */
    public function reconnect()
    {
        $this->createConnection();
    }

    /**
     * 开始事务
     */
    public function beginTransaction()
    {
        $this->connect->beginTransaction();
    }

    /**
     * 获取插入ID
     *
     * @return mixed
     */
    public function getInsertId()
    {
        return $this->connect->lastInsertId();
    }

    /**
     * 获取更新影响的行数
     *
     * @return int
     */
    public function getAffectedRows(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->connect->rollBack();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * 销毁SQL
     */
    public function destory()
    {
        $this->sql  = '';
        $this->stmt = null;
    }

    /**
     * SQL语句
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * 格式化参数
     *
     * @param array $params
     */
    private function formatSqlByParams(array $params)
    {
        if (empty($params)) {
            return;
        }
        foreach ($params as $key => $value) {
            $this->sql .= " $key=" . $value;
        }
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        try {
            $this->connection->getAttribute(\PDO::ATTR_SERVER_INFO);
        } catch (\Throwable $e) {
            if ($e->getCode() == 'HY000') {
                return false;
            }
        }

        return true;
    }
}
