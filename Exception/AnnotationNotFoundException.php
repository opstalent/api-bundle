<?php

namespace Opstalent\ApiBundle\Exception;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class AnnotationNotFoundException extends \UnexpectedValueException implements Exception
{
    /**
     * @param string $class
     * @param string $path
     */
    public function __construct(string $class, string $path)
    {
        parent::__construct(sprintf('Annotation "%s" not found in request arguments at path "%s"', $class, $path));
    }
}
