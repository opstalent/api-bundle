<?php

namespace Opstalent\ApiBundle\Resolver;

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
}
