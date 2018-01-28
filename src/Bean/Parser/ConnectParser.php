<?php

namespace Swoft\Db\Bean\Parser;

use Swoft\Bean\Annotation\Scope;
use Swoft\Bean\Parser\AbstractParserInterface;
use Swoft\Db\Bean\Annotation\Connect;
use Swoft\Db\Bean\Collector\ConnectCollector;

/**
 * The parser of connect annotation
 *
 * @uses      ConnectParser
 * @version   2018年01月27日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class ConnectParser extends AbstractParserInterface
{
    /**
     * Do parser
     *
     * @param string  $className
     * @param Connect $objectAnnotation
     * @param string  $propertyName
     * @param string  $methodName
     * @param null    $propertyValue
     *
     * @return null
     */
    public function parser(string $className, $objectAnnotation = null, string $propertyName = "", string $methodName = "", $propertyValue = null)
    {
        ConnectCollector::collect($className, $objectAnnotation, $propertyName, $methodName, $propertyValue);

        return null;
    }

}