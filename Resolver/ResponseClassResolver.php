<?php

namespace Opstalent\ApiBundle\Resolver;

use Opstalent\ApiBundle\Annotation\Response;
use Opstalent\ApiBundle\Exception\AnnotationNotFoundException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ResponseClassResolver
{
    /**
     * @param Request $request
     * @return string
     * @throws AnnotationNotFoundException
     */
    public static function resolveByRequest(Request $request) : string
    {
        try {
            $annotation = static::getAnnotation($request);
        } catch (\TypeError $e) {
            throw new AnnotationNotFoundException(Response::class, 'response_transformer');
        }

        return $annotation->getClass();
    }

    protected static function getAnnotation(Request $request) : Response
    {
        return $request->attributes->get('_response_transformer');
    }
}
