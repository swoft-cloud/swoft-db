<?php

namespace Swoft\Db\Helper;

use Swoft\Db\Bean\Collector\EntityCollector;
use Swoft\Db\Types;

/**
 * EntityHelper
 */
class EntityHelper
{
    /**
     * @param array  $result
     * @param string $className
     *
     * @return array
     */
    public static function listToEntity(array $result, string $className): array
    {
        $entities = [];
        foreach ($result as $data) {
            if (!\is_array($data)) {
                continue;
            }
            $entities[] = self::arrayToEntity($data, $className);
        }

        return $entities;
    }

    /**
     * @param array  $data
     * @param string $className
     *
     * @return object
     */
    public static function arrayToEntity(array $data, string $className)
    {
        $attrs    = [];
        $object   = new $className();
        $entities = EntityCollector::getCollector();

        foreach ($data as $col => $value) {
            if (!isset($entities[$className]['column'][$col])) {
                continue;
            }

            $field        = $entities[$className]['column'][$col];
            $setterMethod = 'set' . ucfirst($field);

            $type  = $entities[$className]['field'][$field]['type'];
            $value = self::trasferTypes($type, $value);

            if (method_exists($object, $setterMethod)) {
                $attrs[$field] = $value;
                $object->$setterMethod($value);
            }
        }
        if (method_exists($object, 'setAttrs')) {
            $object->setAttrs($attrs);
        }

        return $object;
    }

    /**
     * @param $type
     * @param $value
     *
     * @return bool|float|int|string
     */
    public static function trasferTypes($type, $value)
    {
        if ($type === Types::INT || $type === Types::NUMBER) {
            $value = (int)$value;
        } elseif ($type === Types::STRING) {
            $value = (string)$value;
        } elseif ($type === Types::BOOLEAN) {
            $value = (bool)$value;
        } elseif ($type === Types::FLOAT) {
            $value = (float)$value;
        }

        return $value;
    }
}