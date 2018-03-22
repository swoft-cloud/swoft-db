<?php

namespace Swoft\Db\Driver\Pgsql;

use Swoft\App;
use Swoft\Core\ResultInterface;
use Swoft\Db\AbstractDbConnection;
use Swoft\Db\DbCoResult;
use Swoft\Db\DbDataResult;
use Swoft\Db\Helper\DbHelper;
use Swoft\Db\Helper\EntityHelper;
use Swoft\Helper\JsonHelper;
use Swoft\Db\Bean\Annotation\Builder;
use Swoft\Db\Driver\Driver;


/**
 * Mysql query builder
 *
 * @Builder(driver=Driver::PGSQL)
 */
class QueryBuilder extends \Swoft\Db\QueryBuilder
{
    /**
     * @var string
     */
    private $profilePrefix = "pgsql";

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

        /* @var AbstractDbConnection $connection */
        $connection = $this->selectConnection();
        $connection->prepare($sql);
        $result = $connection->execute($this->parameters);

        App::profileEnd($profileKey);
        App::debug(sprintf('sql execute sqlId=%s, result=%s, sql=%s', $sqlId, JsonHelper::encode($result, JSON_UNESCAPED_UNICODE), $sql));

        $isFindOne = isset($this->limit['limit']) && $this->limit['limit'] === 1;
        if ($this->isInsert()) {
            $result = $connection->getInsertId();
        } elseif ($this->isUpdate() || $this->isDelete()) {
            $result = $connection->getAffectedRows();
        } else {
            $result = $connection->fetch();
        }

        $result = $this->transferResult($connection, $result);

        if (is_array($result) && !empty($className)) {
            $result = EntityHelper::resultToEntity($result, $className);
        }
        $syncData = new DbDataResult($result, $connection);

        return $syncData;
    }

    /**
     * @return ResultInterface
     */
    private function getCorResult()
    {
        $sql = $this->getStatement();
        list($sqlId, $profileKey) = $this->getSqlIdAndProfileKey($sql);

        /* @var AbstractDbConnection $connection */
        $connection = $this->selectConnection();
        $connection->setDefer();
        $connection->prepare($sql);
        $result = $connection->execute($this->parameters);

        App::debug(sprintf('sql execute sqlId=%s, sql=%s', $sqlId, $sql));
        $isUpdateOrDelete = $this->isDelete() || $this->isUpdate();
        $isFindOne        = $this->isSelect() && isset($this->limit['limit']) && $this->limit['limit'] === 1;
        $corResult        = new DbCoResult($connection, $profileKey);

        // 结果转换参数
        $corResult->setInsert($this->isInsert());
        $corResult->setUpdateOrDelete($isUpdateOrDelete);
        $corResult->setFindOne($isFindOne);

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
     * @param AbstractDbConnection $connection
     * @param mixed $result
     *
     * @return mixed
     */
    private function transferResult(AbstractDbConnection $connection, $result)
    {
        $isFindOne        = isset($this->limit['limit']) && $this->limit['limit'] === 1;
        $isUpdateOrDelete = $this->isDelete() || $this->isUpdate();
        if ($result !== false && $this->isInsert()) {
            $result = $connection->getInsertId();
        } elseif ($result !== false && $isUpdateOrDelete) {
            $result = $connection->getAffectedRows();
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

    /**
     * select语句
     *
     * @return string
     */
    protected function getSelectString(): string
    {
        $statement = '';
        if (empty($this->select)) {
            return $statement;
        }

        // 字段组拼
        foreach ($this->select as $column => $alias) {
            $column = explode(',',$column);
            $column =  array_map(function($v){
                return '"'.$v.'"';
            },$column);

            $statement .=  implode(',',$column);
            if ($alias !== null) {
                $statement .= ' AS ' . $alias;
            }
            $statement .= ', ';
        }

        //select组拼
        $statement = substr($statement, 0, -2);
        if (!empty($statement)) {
            $statement = 'SELECT ' . $statement;
        }

        return $statement;
    }
}
