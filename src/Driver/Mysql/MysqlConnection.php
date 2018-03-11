<?php

namespace Swoft\Db\Driver\Mysql;

use Swoft\App;
use Swoft\Db\Bean\Annotation\Connect;
use Swoft\Db\AbstractDbConnection;
use Swoft\Db\Exception\MysqlException;
use Swoole\Coroutine\Mysql;

/**
 * Mysql connection
 *
 * @Connect()
 */
class MysqlConnection extends AbstractDbConnection
{
    /**
     * @var Mysql
     */
    private $connection = null;

    /**
     * @var string
     */
    private $sql = '';

    /**
     * Prepare
     *
     * @param string $sql
     */
    public function prepare(string $sql)
    {
        $this->sql  = $sql;
    }

    /**
     * Execute
     *
     * @param array|null $params
     *
     * @return array|bool
     */
    public function execute(array $params = [])
    {
        $this->formatSqlByParams($params);
        $result = $this->connection->query($this->sql);
        if ($result === false) {
            App::error('Mysql execute error，connectError=' . $this->connection->connect_error . ' error=' . $this->connection->error);
        }

        return $result;
    }

    /**
     * @return array|bool
     */
    public function recv()
    {
        return $this->connection->recv();
    }

    /**
     * @return mixed
     */
    public function getInsertId()
    {
        return $this->connection->insert_id;
    }

    /**
     * @return int
     */
    public function getAffectedRows(): int
    {
        return $this->connection->affected_rows;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $this->connection->query('begin;');
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        $this->connection->query('rollback;');
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        $this->connection->query('commit;');
    }

    /**
     * Set defer
     *
     * @param bool $defer
     */
    public function setDefer($defer = true)
    {
        $this->connection->setDefer($defer);
    }

    /**
     * Create connection
     *
     * @throws \InvalidArgumentException
     */
    public function createConnection()
    {
        $uri                = $this->pool->getConnectionAddress();
        $options            = $this->parseUri($uri);
        $options['timeout'] = $this->pool->getTimeout();

        // init
        $mysql = new MySQL();
        $mysql->connect([
            'host'     => $options['host'],
            'port'     => $options['port'],
            'user'     => $options['user'],
            'password' => $options['password'],
            'database' => $options['database'],
            'timeout'  => $options['timeout'],
            'charset'  => $options['charset'],
        ]);

        // error
        if ($mysql->connected === false) {
            throw new MysqlException('Database connection error，error=' . $mysql->connect_error);
        }
        $this->connection = $mysql;
    }


    /**
     * @return void
     */
    public function reconnect()
    {
        $this->createConnection();
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        return $this->connection->connected;
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Destory sql
     */
    public function destory()
    {
        $this->sql = '';
    }

    /**
     * 格式化sql参数
     *
     * @param array|null $params
     */
    private function formatSqlByParams(array $params = null)
    {
        if (empty($params)) {
            return;
        }

        foreach ($params as $key => &$value){
            $value = "'{$value}'";
        }

        // ?方式传递参数
        if (strpos($this->sql, '?') !== false) {
            $this->transferQuestionMark();
        }
        $this->sql = strtr($this->sql, $params);
    }
    /**
     * 格式化?标记
     */
    private function transferQuestionMark()
    {
        $sqlAry = explode('?', $this->sql);
        $sql = '';
        $maxBlock = \count($sqlAry);
        for ($i = 0; $i < $maxBlock; $i++) {
            $n = $i;
            $sql .= $sqlAry[$i];
            if ($maxBlock > $i + 1) {
                $sql .= '?' . $n . ' ';
            }
        }
        $this->sql = $sql;
    }
}
