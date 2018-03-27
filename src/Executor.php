<?php

namespace Swoft\Db;

use Swoft\App;
use Swoft\Bean\BeanFactory;
use Swoft\Core\ResultInterface;
use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Validator\ValidatorInterface;
use Swoft\Exception\ValidatorException;

/**
 * The executor of db
 */
class Executor
{
    /**
     * @param object $entity
     *
     * @return ResultInterface
     */
    public static function save($entity): ResultInterface
    {
        list($table, , , $fields) = self::getFields($entity, 1);

        $query = Query::table($table)->insert();
        foreach ($fields ?? [] as $column => $value) {
            $query = $query->set($column, $value);
        }

        return $query->execute();
    }

    /**
     * @param object $entity
     *
     * @return ResultInterface
     */
    public static function delete($entity): ResultInterface
    {
        list($table, , , $fields) = self::getFields($entity, 3);

        $query = Query::table($table)->delete();
        foreach ($fields ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        return $query->execute();
    }

    /**
     * @param string $className
     * @param mixed  $id
     *
     * @return ResultInterface
     */
    public static function deleteById($className, $id): ResultInterface
    {
        list($table, , $idColumn) = self::getTable($className);
        $query = Query::table($table)->delete()->where($idColumn, $id);

        return $query->execute();
    }

    /**
     * @param string $className
     * @param array  $ids
     *
     * @return ResultInterface
     */
    public static function deleteByIds($className, array $ids): ResultInterface
    {
        list($table, , $idColumn) = self::getTable($className);
        $query = Query::table($table)->delete()->whereIn($idColumn, $ids);

        return $query->execute();
    }

    /**
     * @param object $entity
     *
     * @return ResultInterface
     */
    public static function update($entity): ResultInterface
    {
        // 实体映射数据
        list($table, $idColumn, $idValue, $fields) = self::getFields($entity, 2);

        if (empty($fields)) {
            return new DbDataResult(0);
        }
        // 构建update查询器
        $query = Query::table($table)->update()->where($idColumn, $idValue);
        foreach ($fields ?? [] as $column => $value) {
            $query->set($column, $value);
        }

        return $query->execute();
    }

    /**
     * @param object $entity
     *
     * @return ResultInterface
     */
    public static function find($entity): ResultInterface
    {
        list($tableName, , , $fields) = self::getFields($entity, 3);

        $query = Query::table($tableName)->select('*');
        foreach ($fields ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        return $query->execute();
    }

    /**
     * @param string $className
     * @param mixed  $id
     *
     * @return ResultInterface
     */
    public static function findById($className, $id): ResultInterface
    {
        list($tableName, , $columnId) = self::getTable($className);
        $query = Query::table($tableName)->select("*")->where($columnId, $id)->limit(1);

        return $query->execute();
    }

    /**
     * @param string $className
     * @param array  $ids
     *
     * @return ResultInterface
     */
    public static function findByIds($className, array $ids): ResultInterface
    {
        list($tableName, , $columnId) = self::getTable($className);
        $query = Query::table($tableName)->select("*")->whereIn($columnId, $ids);

        return $query->execute();
    }

    /**
     * @param object $entity 实体对象
     * @param int    $type   类型，1=insert 3=delete|find 2=update
     *
     * @return array
     * @throws \Swoft\Exception\ValidatorException
     */
    private static function getFields($entity, $type = 1): array
    {
        $changeFields = [];

        // 实体表结构信息
        list($table, $id, $idColumn, $fields) = self::getClassMetaData($entity);

        // 实体映射字段、值处理以及验证处理
        $idValue = null;
        foreach ($fields as $proName => $proAry) {
            $column  = $proAry['column'];
            $default = $proAry['default'];

            // 实体属性对应值
            $proValue = self::getEntityProValue($entity, $proName);

            // insert逻辑
            if ($type === 1 && $id === $proName && $default === $proValue) {
                continue;
            }

            // update逻辑
            if ($type === 2 && null === $proValue) {
                continue;
            }

            // delete和find逻辑
            if ($type === 3 && $default === $proValue) {
                continue;
            }

            // 属性值验证
            self::validate($proAry, $proValue);

            // id值赋值
            if ($idColumn === $column) {
                $idValue = $proValue;
            }

            $changeFields[$column] = $proValue;
        }

        // 如果是更新找到变化的字段
        if ($type === 2) {
            $oldFields    = $entity->getAttrs();
            $changeFields = array_diff($changeFields, $oldFields);
        }

        return [$table, $idColumn, $idValue, $changeFields];
    }

    /**
     * 属性值验证
     *
     * @param array $columnAry     属性字段验证规则
     * @param mixed $propertyValue 数组字段值
     *
     * @throws ValidatorException
     */
    private function validate(array $columnAry, $propertyValue)
    {
        // 验证信息
        $column    = $columnAry['column'];
        $length    = $columnAry['length'] ?? -1;
        $validates = $columnAry['validates'] ?? [];
        $type      = $columnAry['type'] ?? Types::STRING;
        $required  = $columnAry['required'] ?? false;

        // 必须传值验证
        if ($propertyValue === null && $required) {
            throw new ValidatorException('数据字段验证失败，column=' . $column . '字段必须设置值');
        }

        // 类型验证器
        $validator = [
            'name'  => ucfirst($type),
            'value' => [$length],
        ];

        // 所有验证器
        array_unshift($validates, $validator);

        // 循环验证，一个验证不通过，验证失败
        foreach ($validates as $validate) {
            $name     = $validate['name'];
            $params   = $validate['value'];
            $beanName = 'Validator' . $name;

            // 验证器未定义
            if (!BeanFactory::hasBean($beanName)) {
                App::warning('验证器不存在，beanName=' . $beanName);
                continue;
            }

            /* @var ValidatorInterface $objValidator */
            $objValidator = App::getBean($beanName);
            $objValidator->validate($column, $propertyValue, $params);
        }
    }

    /**
     * 实体属性对应的值
     *
     * @param object $entity  实体对象
     * @param string $proName 属性名称
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function getEntityProValue($entity, string $proName)
    {
        $proName      = explode('_', $proName);
        $proName      = array_map(function ($word) {
            return ucfirst($word);
        }, $proName);
        $proName      = implode('', $proName);
        $getterMethod = 'get' . $proName;
        if (!method_exists($entity, $getterMethod)) {
            throw new \InvalidArgumentException('实体对象属性getter方法不存在，properName=' . $proName);
        }
        $proValue = $entity->$getterMethod();

        return $proValue;
    }

    /**
     * @param object $entity
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    private static function getClassMetaData($entity): array
    {
        // 不是对象
        if (!\is_object($entity) && !class_exists($entity)) {
            throw new \InvalidArgumentException('实体不是对象');
        }

        // 对象实例不是实体
        $entities  = EntityCollector::getCollector();
        $className = \is_string($entity) ? $entity : \get_class($entity);
        if (!isset($entities[$className]['table']['name'])) {
            throw new \InvalidArgumentException('对象不是实体对象，className=' . $className);
        }

        return self::getTable($className);
    }

    /**
     * @param string $className
     *
     * @return array
     */
    private static function getTable(string $className): array
    {
        $entities   = EntityCollector::getCollector();
        $fields     = $entities[$className]['field'];
        $idProperty = $entities[$className]['table']['id'];
        $tableName  = $entities[$className]['table']['name'];
        $idColumn   = $entities[$className]['column'][$idProperty];

        return [$tableName, $idProperty, $idColumn, $fields];
    }
}
