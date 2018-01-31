<?php

namespace Swoft\Db\Driver\Mysql;

use Swoft\App;
use Swoft\Core\ResultInterface;
use Swoft\Db\DbCorResult;
use Swoft\Db\DbSyncResult;
use Swoft\Helper\ArrayHelper;
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
    public function getResult()
    {
        if(App::isCorContext()){
            return $this->getCorResult();
        }
        return $this->getSyncResult();
    }

    /**
     * @return DbSyncResult
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
            $result = ArrayHelper::resultToEntity($result, $className);
        }
        $this->pool->release($this->connect);

        $syncData = new DbSyncResult($result);

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
        $corResult        = new DbCorResult($this->connect, $profileKey, $this->pool);

        // 结果转换参数
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
        $profileKey = sprintf('%s.%s', $this->profilePrefix);

        return [$sqlId, $profileKey];
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
        $isFindOne = isset($this->limit['limit']) && $this->limit['limit'] == 1;
        $isUpdateOrDelete = $this->isDelete() || $this->isUpdate();
        if ($this->isInsert() && $result !== false) {
            $result = $this->connect->getInsertId();
        } elseif ($isUpdateOrDelete && $result !== false) {
            $result = $this->connect->getAffectedRows();
        } elseif ($this->isSelect() && $result !== false && $isFindOne) {
            $result = $result[0]?? [];
        }
        return $result;
    }

    /**
     * @param mixed $key
     *
     * @return string
     */
    protected function formatParamsKey($key): string
    {
        if (is_string($key)) {
            return ":" . $key;
        }
        if (App::isWorkerStatus()) {
            return "?" . $key;
        }

        return $key;
    }
}
