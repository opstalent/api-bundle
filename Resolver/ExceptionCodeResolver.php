<?php

namespace Opstalent\ApiBundle\Resolver;

use Opstalent\ApiBundle\Exception\InnerCodeException;
use Opstalent\ApiBundle\Exception\InnerCodeExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ExceptionCodeResolver
{
    /**
     * @param \Throwable $exception
     * @return int
     */
    public static function resolveResponseCode(\Throwable $exception) : int
    {
        $code = $exception->getCode();
        if (array_key_exists($code, Response::$statusTexts)) {
            return $code;
        }

        if ($exception->getPrevious()) {
            return static::resolveResponseCode($exception->getPrevious());
        }

        return 500;
    }

    /**
     * @param \Throwable $exception
     * @return int
     */
    public static function resolveInnerResponseCode(\Throwable $exception) : int
    {

        if($exception instanceof InnerCodeExceptionInterface){
            $code = $exception->getInnerCode();
            return $code;
        }

        return 0;
    }
}
