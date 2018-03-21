<?php

namespace Swoft\Db\Entity\Pgsql;

/**
 * MYSQL数据库字段映射关系
 *
 * @uses      Schema
 * @version   2017年11月14日
 * @author    caiwh <471113744@qq.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */

class Schema extends \Swoft\Db\Entity\Schema
{
    /**
     * @var array entity映射关系
     */
    public $dbSchema = [
        'int2'       => 'Types::INT',
        'int2vector' => 'Types::INT',
        'int4'       => 'Types::INT',
        'int8'       => 'Types::INT',
        'int'        => 'Types::INT',
        'json'       => 'Types::STRING',
        'varchar'    => 'Types::STRING',
        'text'       => 'Types::STRING',
        'date'       => 'Types::DATETIME',
        'money'      => 'Types::FLOAT',
        'boolean'    => 'Types::BOOLEAN',
    ];

    /**
     * @var array php映射关系
     */
    public $phpSchema = [
        'int2'       => self::TYPE_INT,
        'int2vector' => self::TYPE_INT,
        'int4'       => self::TYPE_INT,
        'int8'       => self::TYPE_INT,
        'int'        => self::TYPE_INT,
        'json'       => self::TYPE_STRING,
        'varchar'    => self::TYPE_STRING,
        'text'       => self::TYPE_STRING,
        'date'       => self::TYPE_STRING,
        'money'      => self::TYPE_STRING,
        'boolean'    => self::TYPE_BOOL,
    ];
}
