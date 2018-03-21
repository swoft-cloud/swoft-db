<?php

namespace Swoft\Db\Driver\Pgsql;

use Swoft\App;
use Swoft\Core\ResultInterface;
use Swoft\Db\DbCoResult;
use Swoft\Db\DbDataResult;
use Swoft\Db\Helper\DbHelper;
use Swoft\Db\Helper\EntityHelper;
use Swoft\Helper\JsonHelper;

/**
 * Mysql查询器
 *
 * @uses      QueryBuilder
 * @version   2017年09月01日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class QueryBuilder extends \Swoft\Db\QueryBuilder
{
    /**
     * @var string
     */
    private $profilePrefix = "mysql";

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
     *
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
        App::debug(sprintf("sql execute sqlId=%s, result=%s, sql=%s", $sqlId, JsonHelper::encode($result, JSON_UNESCAPED_UNICODE), $sql));

        $result = $this->transferResult($result);

        if (is_array($result) && !empty($className)) {
            $result = EntityHelper::resultToEntity($result, $className);
        }

        if (!DbHelper::isContextTransaction($this->poolId)) {
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

        App::debug(sprintf("sql execute sqlId=%s, sql=%s", $sqlId, $sql));
        $isUpdateOrDelete = $this->isDelete() || $this->isUpdate();
        $isFindOne        = $this->isSelect() && isset($this->limit['limit']) && $this->limit['limit'] == 1;
        $corResult        = new DbCoResult($this->connect, $profileKey, $this->pool);

        // 结果转换参数
        $corResult->setPoolId($this->poolId);
        $corResult->setIsInsert($this->isInsert());
        $corResult->setIsUpdateOrDelete($isUpdateOrDelete);
        $corResult->setIsFindOne($isFindOne);

        return $corResult;
    }

    /**
     * @param string $sql
     *
     * @return array
     */
    private function getSqlIdAndProfileKey(string $sql)
    {
        $sqlId      = md5($sql);
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
        $isFindOne        = isset($this->limit['limit']) && $this->limit['limit'] === 1;
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
     * insert语句
     *
     * @return string
     */
    protected function getInsertStatement(): string
    {
        $statement = '';
        if (!$this->isInsert()) {
            return $statement;
        }

        // insert语句
        $statement .= $this->getInsertString();

        // set语句
        if ($this->set) {
            foreach ($this->set as $set) {
                $keys[]   = $set['column'];
                $values[] = $this->getQuoteValue($set['value']);
            }
            $statement .= ' (' . implode(',', $keys) . ')' . ' VALUES (' . implode(',', $values) . ');';
        }

        return $statement;
    }

    /**
     * 字符串转换
     *
     * @param $value
     * @return string
     */
    protected function getQuoteValue($value): string
    {
        if (\is_string($value)) {
            $value = "'" . $value . "'";
        }
        return $value;
    }

    /**
     * insert表
     *
     * @return string
     */
    protected function getInsert(): string
    {
        return ' INTO "' . $this->insert . '"';
    }

    /**
     * update表
     *
     * @return mixed
     */
    protected function getUpdate()
    {
        return '"' . $this->update . '"';
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

    /**
     * from表
     *
     * @return string
     */
    protected function getFrom(): string
    {
        $table = $this->from['table']??'';

        return '"' . $table . '"';
    }
}
