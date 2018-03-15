<?php

namespace Swoft\Db\Helper;

/**
 * DbHelper
 */
class DbHelper
{
    /**
     * @return string
     */
    public static function getContextSqlKey(): string
    {
        return 'swoft-sql';
    }
}