<?php

namespace Swoft\Db\Bean\Wrapper;

use Swoft\Bean\Annotation\Inject;
use Swoft\Bean\Wrapper\AbstractWrapperInterface;
use Swoft\Db\Bean\Annotation\Connect;

/**
 * The wrapper of connect
 *
 * @uses      ConnectWrapper
 * @version   2018年01月27日
 * @author    stelin <phpcrazy@126.com>
 * @copyright Copyright 2010-2016 swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class ConnectWrapper extends AbstractWrapperInterface
{
    /**
     * 类注解
     *
     * @var array
     */
    protected $classAnnotations
        = [
            Connect::class,
        ];

    /**
     * 属性注解
     *
     * @var array
     */
    protected $propertyAnnotations
        = [
            Inject::class,
        ];

    /**
     * 是否解析类注解
     *
     * @param array $annotations
     *
     * @return bool
     */
    public function isParseClassAnnotations(array $annotations)
    {
        return isset($annotations[Connect::class]);
    }

    /**
     * 是否解析属性注解
     *
     * @param array $annotations
     *
     * @return bool
     */
    public function isParsePropertyAnnotations(array $annotations)
    {
        return isset($annotations[Inject::class]);
    }

    /**
     * 是否解析方法注解
     *
     * @param array $annotations
     *
     * @return bool
     */
    public function isParseMethodAnnotations(array $annotations)
    {
        return false;
    }
}