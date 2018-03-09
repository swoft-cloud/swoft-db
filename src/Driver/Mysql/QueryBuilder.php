<?php

namespace Swoft\Db\Driver\Mysql;

use Swoft\App;
use Swoft\Core\ResultInterface;
use Swoft\Db\DbCoResult;
use Swoft\Db\DbDataResult;
use Swoft\Db\Helper\DbHelper;
use Swoft\Db\Helper\EntityHelper;
use Swoft\Helper\JsonHelper;

/**
 * Mysql query builder
 */
class QueryBuilder extends \Swoft\Db\QueryBuilder
{
    /**
     * @var string
     */
    private $profilePrefix = 'mysql';

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        if (App::isCoContext()) {
            return $this->getCorResult();
        }
        return $this->getSyncResult();
    }

    /**
     * @return DbDataResult
     */
    private function getSyncResult()
    {
        $sql = $this->getStatement();
        list($sqlId, $profileKey) = $this->getSqlIdAndProfileKey($sql);

        App::profileStart($profileKey);

        $this->connect->prepare($sql);
        $result = $this->connect->execute($this->parameters);

        App::profileEnd($profileKey);
        App::debug(sprintf('sql execute sqlId=%s, result=%s, sql=%s', $sqlId, JsonHelper::encode($result, JSON_UNESCAPED_UNICODE), $sql));

        $result = $this->transferResult($result);

        if (is_array($result) && ! empty($className)) {
            $result = EntityHelper::resultToEntity($result, $className);
        }

        if (! DbHelper::isContextTransaction($this->poolId)) {
            $this->pool->release($this->connect);
        }

        $syncData = new DbDataResult($result);

        return $syncData;
    }

    /**
     * @return ResultInterface
     */
    private function getCorResult()
    {
        $sql = $this->getStatement();
        list($sqlId, $profileKey) = $this->getSqlIdAndProfileKey($sql);

        $this->connect->setDefer();
        $this->connect->prepare($sql);
        $result = $this->connect->execute($this->parameters);

        App::debug(sprintf('sql execute sqlId=%s, sql=%s', $sqlId, $sql));
        $isUpdateOrDelete = $this->isDelete() || $this->isUpdate();
        $isFindOne = $this->isSelect() && isset($this->limit['limit']) && $this->limit['limit'] === 1;
        $corResult = new DbCoResult($this->connect, $profileKey, $this->pool);

        // 结果转换参数
        $corResult->setPoolId($this->poolId);
        $corResult->setInsert($this->isInsert());
        $corResult->setUpdateOrDelete($isUpdateOrDelete);
        $corResult->setFindOne($isFindOne);

        return $corResult;
    }

    /**
     * @param string $sql
     * @return array
     */
    private function getSqlIdAndProfileKey(string $sql)
    {
        $sqlId = md5($sql);
        $profileKey = sprintf('%s.%s', $sqlId, $this->profilePrefix);

        return [$sqlId, $profileKey];
    }

    /**
     * 转换结果
     *
     * @param mixed $result 查询结果
     * @return mixed
     */
    private function transferResult($result)
    {
        $isFindOne = isset($this->limit['limit']) && $this->limit['limit'] === 1;
        $isUpdateOrDelete = $this->isDelete() || $this->isUpdate();
        if ($result !== false && $this->isInsert()) {
            $result = $this->connect->getInsertId();
        } elseif ($result !== false && $isUpdateOrDelete) {
            $result = $this->connect->getAffectedRows();
        } elseif ($isFindOne && $result !== false && $this->isSelect()) {
            $result = $result[0] ?? [];
        }
        return $result;
    }

    /**
     * @param mixed $key
     * @return string
     */
    protected function formatParamsKey($key): string
    {
        if (\is_string($key)) {
            return ':' . $key;
        }
        if (App::isWorkerStatus()) {
            return '?' . $key;
        }

        return $key;
    }
}
