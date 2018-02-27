<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Core\AbstractCoResult;
use Swoft\Db\Helper\DbHelper;
use Swoft\Db\Helper\EntityHelper;

/**
 * The cor result of db
 */
class DbCoResult extends AbstractCoResult
{
    /**
     * 是否是插入操作
     *
     * @var bool
     */
    private $isInsert = false;

    /**
     * 是否是更新或删除操作
     *
     * @var bool
     */
    private $isUpdateOrDelete = false;

    /**
     * 是否查找一条数据
     *
     * @var bool
     */
    private $isFindOne = false;

    /**
     * @var string
     */
    private $poolId;

    /**
     * @param array ...$params
     *
     * @return mixed
     */
    public function getResult(...$params)
    {
        $className = "";
        if (!empty($params)) {
            list($className) = $params;
        }

        $result = $this->recv(true);
        $result = $this->transferResult($result);

        // 日志记录处理
        list(, $sqlId) = explode('.', $this->profileKey);
        App::debug("SQL语句执行结果(defer) sqlId=$sqlId result=" . json_encode($result));

        // fill data to entity
        if (is_array($result) && !empty($className)) {
            $result = EntityHelper::resultToEntity($result, $className);
        }

        return $result;
    }

    /**
     * @param bool $defer
     *
     * @return mixed
     */
    public function recv($defer = false)
    {
        $result = $this->client->recv();

        // 重置延迟设置
        if ($defer) {
            $this->client->setDefer(false);
        }

        $isSqlSession = DbHelper::isContextTransaction($this->poolId);
        if ($this->connectPool !== null && $isSqlSession == false) {
            $this->connectPool->release($this->client);
        }

        return $result;
    }

    /**
     * @param bool $isInsert
     */
    public function setIsInsert(bool $isInsert)
    {
        $this->isInsert = $isInsert;
    }

    /**
     * @param bool $isUpdateOrDelete
     */
    public function setIsUpdateOrDelete(bool $isUpdateOrDelete)
    {
        $this->isUpdateOrDelete = $isUpdateOrDelete;
    }

    /**
     * @param bool $isFindOne
     */
    public function setIsFindOne(bool $isFindOne)
    {
        $this->isFindOne = $isFindOne;
    }

    /**
     * @param string $poolId
     */
    public function setPoolId(string $poolId)
    {
        $this->poolId = $poolId;
    }

    /**
     * 转换结果
     *
     * @param mixed $result 查询结果
     *
     * @return mixed
     */
    private function transferResult($result)
    {
        if ($this->isInsert && $result !== false) {
            $result = $this->client->getInsertId();
        } elseif ($this->isUpdateOrDelete && $result !== false) {
            $result = $this->client->getAffectedRows();
        } elseif ($this->isFindOne && $result != false) {
            $result = $result[0] ?? [];
        }
        return $result;
    }
}