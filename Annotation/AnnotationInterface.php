<?php

namespace Opstalent\ApiBundle\Annotation;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
interface AnnotationInterface
{
    /**
     * @return string
     */
    public function getAliasName() : string;
}
