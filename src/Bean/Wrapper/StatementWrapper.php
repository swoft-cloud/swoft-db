<?php

namespace Swoft\Db\Bean\Wrapper;

use Swoft\Bean\Wrapper\AbstractWrapper;
use Swoft\Db\Bean\Annotation\Statement;

/**
 * StatementWrapper
 */
class StatementWrapper extends AbstractWrapper
{
    /**
     * @var array
     */
    protected $classAnnotations
        = [
            Statement::class,
        ];

    /**
     * @param array $annotations
     *
     * @return bool
     */
    public function isParseClassAnnotations(array $annotations): bool
    {
        return isset($annotations[Statement::class]);
    }

    /**
     * @param array $annotations
     *
     * @return bool
     */
    public function isParsePropertyAnnotations(array $annotations): bool
    {
        return false;
    }

    /**
     * @param array $annotations
     *
     * @return bool
     */
    public function isParseMethodAnnotations(array $annotations): bool
    {
        return false;
    }
}